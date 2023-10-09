<?php

namespace App\Http\Controllers;

use App\Http\SlackClient\BlocksConstructorKit;
use App\Http\SlackClient\SlackClient;
use App\Http\SlackClient\SlackException;
use App\Models\UserSlack;
use DateInterval;
use DateTime;
use PHPUnit\Util\Exception;

class checkinBuilder
{
    const POSITION_EMOJI = [
        1 => "🏆🥇",
        2 => "🥈",
        3 => "🥉",
        4 => "🦾",
        "avg" => "👍",
        "last" => "😴"
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

    private function getWeekCheckinTitle($monthName)
    {
        return sprintf("%s, weekly checkin statistic", $monthName);
    }

    private function getMonthlyCheckinTitle($emoji, $monthName)
    {
        return sprintf(":%s: %s checkin statistic", $emoji, $monthName);
    }

    public function constructBlock(string $oldest, string $latest, string $type): array
    {
        $monthName = date('F', $oldest);
        $monthEmoji = self::MONTH_EMOJI[date('n', $oldest)];
        $blocks = [
            BlocksConstructorKit::header($type === 'weekly' ? $this->getWeekCheckinTitle(
                monthName: $monthName
            ) : $this->getMonthlyCheckinTitle(
                emoji: $monthEmoji,
                monthName: $monthName
            )),
        ];
        $position = 0;
        $checkinArray = $this->getStatisticHash($oldest, $latest);
        foreach ($checkinArray as $user => $values) {
//            if ($values['count'] <= 5) continue;

            $position += 1;
            if ($position == count($checkinArray)) {
                $emoji = self::POSITION_EMOJI['last'];
            } elseif ($position > 4) {
                $emoji = self::POSITION_EMOJI['avg'];
            } else {
                $emoji = self::POSITION_EMOJI[$position];
            }

            $blocks[] =
                BlocksConstructorKit::section(
                    BlocksConstructorKit::markdown("$emoji <@$user> has an average checkin time of ${values['averageString']}, with only ${values['count']} checkins")
                );
        }
        return $blocks;
    }

    private function getStatisticHash($oldest, $latest): array
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

                $checkinDateTime = new DateTime("@".($reply['ts']));
                $messageDateTime = new DateTime("@".($messageTs));

                $tz_offset = $userInfo['user']['tz_offset'];

                $offsetInterval = new DateInterval("PT".abs($tz_offset)."S");

                if ($tz_offset > 0) {
                    $checkinDateTime->add($offsetInterval);
                    $messageDateTime->add($offsetInterval);
                } else {
                    $checkinDateTime->sub($offsetInterval);
                    $messageDateTime->sub($offsetInterval);
                }

                $local_checkin_time = strtotime($checkinDateTime->format('Y-m-d H:i:s'));
                $local_message_time = strtotime($messageDateTime->format('Y-m-d H:i:s'));

                $checkinInterval = $local_checkin_time - $local_message_time;
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
