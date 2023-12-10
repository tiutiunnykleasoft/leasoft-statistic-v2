<?php

namespace App\Http\Controllers;

use App\Http\SlackClient\BlocksConstructorKit;
use App\Http\SlackClient\SlackClient;
use App\Http\SlackClient\SlackException;
use App\Models\UserSlack;
use DateInterval;
use DateTime;
use DateTimeZone;
use PHPUnit\Util\Exception;

class checkinBuilder
{
    const POSITION_EMOJI = [
        1 => "🏆",
        2 => "🥈",
        3 => "🥉",
        4 => "🦾",
        "avg" => "👍",
        "last" => "😴",
        'time' => "🕒"
    ];

    const MONTH_EMOJI = [
        1 => 'snowman_without_snow',
        2 => 'snowman',
        3 => 'seedling',
        4 => 'tulip',
        5 => 'rainbow',
        6 => 'sun_with_face',
        7 => 'umbrella_on_ground',
        8 => 'umbrella_with_rain_drops',
        9 => 'ear_of_rice',
        10 => 'maple_leaf',
        11 => 'fallen_leaf',
        12 => 'snowflake'
    ];

    private SlackClient $slackClient;

    public function __construct(
        private readonly string $channelId,
        private readonly string $teamId,
    )
    {
        $this->slackClient = app(SlackClient::class);
        $botLine = UserSlack::where('team_id', $this->teamId)->first();
        $botToken = $botLine->toArray()['bot_token'];
        $this->slackClient->setToken($botToken);
    }

    private function getWeekCheckinTitle($oldest, $latest)
    {
        return sprintf("%s - %s", date('d M', $oldest), date('d M', $latest));
    }

    private function getMonthlyCheckinTitle($emoji, $monthName)
    {
        return sprintf(":%s: %s checkin statistic", $emoji, $monthName);
    }

    public function constructBlock(string $oldest, string $latest, string $type, array $checkinArray, $step = 1): array
    {
        $monthName = date('F', $oldest);
        $monthEmoji = self::MONTH_EMOJI[date('n', $oldest)];
        $blocks = [
            BlocksConstructorKit::header($type === 'weekly' ? $this->getWeekCheckinTitle(
                oldest: $oldest,
                latest: $latest,
            ) : $this->getMonthlyCheckinTitle(
                emoji: $monthEmoji,
                monthName: $monthName
            )),
        ];
        $position = 0;

        switch ($step) {
            case 1:
                $checkinArray = array_splice($checkinArray, 0, 3);
                break;
            case 2:
                $position = 3;
                $blocks = [];
                $checkinArray = array_splice($checkinArray, 3);
                break;
            default:
                break;

        }
        foreach ($checkinArray as $user => $values) {
//            if ($values['count'] <= 5) continue;

            $position += 1;
            if (count($checkinArray) <= 3) {
                $emoji = self::POSITION_EMOJI[$position];
            } else if ($position == count($checkinArray)) {
                $emoji = self::POSITION_EMOJI['last'];
            } elseif ($position > 4) {
                $emoji = self::POSITION_EMOJI['avg'];
            } else {
                $emoji = self::POSITION_EMOJI[$position];
            }

            $blocks[] =
                BlocksConstructorKit::section(
                    BlocksConstructorKit::markdown(sprintf("%s %-20s - %s %-11s (%-8s)",
                        $emoji,
                        "<@$user>",
                        self::POSITION_EMOJI['time'],
                        $values['averageString'],
                        $values['count'] > 1 ? "${values['count']} checkins" : "${values['count']} checkin"))
                );
        }
        return $blocks;
    }

    public function getStatisticHash($oldest, $latest): array
    {
        $checkInReminderMessages = $this->getAllCheckinReminderMessages(
            oldest: $oldest,
            latest: $latest
        );
        $statisticHash = [];

        foreach ($checkInReminderMessages as $message) {
            $messageTs = $message['ts'];
            $replies = $this->slackClient->conversationsReplies(
                channel: $this->channelId,
                ts: $messageTs
            );
            $replyHash = [];
            foreach ($replies as $kek => $reply) {
                if (isset($reply['bot_id'])) continue;

                $userInfo = $this->slackClient->userDetails($reply['user']);
                $tz_offset = $userInfo['user']['tz_offset']; // Часовой пояс пользователя в секундах

                // Создание объекта DateTime для времени сообщения
                $messageDateTime = new DateTime("@" . $reply['ts']);
                $messageDateTime->setTimezone(new DateTimeZone(timezone_name_from_abbr("", $tz_offset, 0)));

                // Создание объекта DateTime для 9 утра в часовом поясе пользователя
                $startOfWorkDay = clone $messageDateTime;
                $startOfWorkDay->setTime(9, 0, 0); // Установка времени на 9 утра

                // Конвертация обоих времен в Unix timestamp
                $local_message_time = $messageDateTime->getTimestamp();
                $start_of_work_day_time = $startOfWorkDay->getTimestamp();

                $checkinInterval = abs($local_message_time - $start_of_work_day_time);
//                if ($checkinInterval > 60 * 60 * 24 * 2) continue;
                if (isset($replyHash[$reply['user']])) continue;

                $replyHash[$reply['user']][] = $checkinInterval;
            }

            foreach ($replyHash as $user => $results) {
                $statisticHash[$user] = array_merge($statisticHash[$user] ?? [], $results);
            }
        }

        $checkinArray = [];
        foreach ($statisticHash as $userId => $value) {

            $secondsInHour = 3600;
            $diffInSeconds = array_sum($value) / count($value);
            $hours = floor($diffInSeconds / $secondsInHour);
            $minutes = floor(($diffInSeconds % $secondsInHour) / 60);
            $seconds = $diffInSeconds % 60;
            $formattedHours = sprintf('%02d', $hours);
            $formattedMinutes = sprintf('%02d', $minutes);
            $formattedSeconds = sprintf('%02d', $seconds);

            $checkinArray[$userId] = [
                'averageString' => "$formattedHours:$formattedMinutes:$formattedSeconds",
                'averageNumber' => $diffInSeconds,
                'count' => count($value)
            ];
        }

        uasort($checkinArray, function ($a, $b) {
            if ($a['averageNumber'] == $b['averageNumber'])
                return 0;
            return ($a['averageNumber'] < $b['averageNumber']) ? -1 : 1;
        });

        return $checkinArray;
    }

    private function getAllCheckinReminderMessages(
        string $oldest,
        string $latest,
    ): array
    {
        $allMessages = $this->slackClient->conversationsHistory(
            channel: $this->channelId,
            oldest: $oldest,
            latest: $latest
        );
        $reminderMessages = [];
        foreach ($allMessages as $message) {
            if ($this->isThisDailyReminder($message) && isset($message['bot_id'])) {
                $reminderMessages[] = $message;
            }
        }
        return $reminderMessages;
    }

    public static function isThisDailyReminder($message): bool
    {
        $reminderText = "Reminder: Please post your daily check-in: :+1:What went well yesterday? :no_entry_sign:What was in your way
yesterday? :rocket:What are your goals for today?";
        return similar_text($reminderText, $message['text']) == "157";
    }
}
