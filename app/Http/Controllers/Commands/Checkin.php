<?php

namespace App\Http\Controllers\Commands;

use App\Http\SlackClient\BlocksConstructorKit;
use App\Http\SlackClient\HttpMethod;
use App\Models\UserSlack;
use Guzzle\Http\Client;
use GuzzleHttp\Exception\GuzzleException;

class Checkin extends AbstractCommand
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

    public function respond()
    {
        http_response_code(200);

        $this->slackClient->send(
            method: HttpMethod::POST,
            path: $this->payload['response_url'],
            data: [
                'text' => 'Checkin request received successfully, please, wait',
                'replace_original' => true,
            ],
            options: [
                'absolute-path' => true,
                'content-type' => 'application/json',
                'data-type' => 'json'
            ]
        );

        $user = UserSlack::findOrFail($this->payload['user_id']);
        $userToken = $user->toArray()['bot_token'];

        $oldest = strtotime('first day of previous month');
        $oldest = strtotime('midnight', $oldest);

        $latest = strtotime('first day of this month');
        $latest = strtotime('midnight', $latest);

        $channelHistory = $this->slackClient->conversationsHistory(
            token: $userToken,
            channel: $this->payload['channel_id'],
            oldest: $oldest,
            latest: $latest
        );

        $statisticHash = [];
        foreach ($channelHistory as $key => $history) {
            if ($this->isThisDailyReminder($history) && isset($history['bot_id'])) {
                $messageTs = $history['ts'];
                $replies = $this->slackClient->conversationsReplies(
                    token: $userToken,
                    channel: $this->payload['channel_id'],
                    ts: $messageTs
                );
                $replyHash = [];
                foreach ($replies as $kek => $reply) {
                    if (isset($reply['bot_id'])) continue;
                    $checkinInterval = $reply['ts'] - $history['ts'];

                    if ($checkinInterval > 60 * 60 * 24 * 2) continue;
                    if (isset($replyHash[$reply['user']])) continue;

                    $replyHash[$reply['user']][] = $checkinInterval;
                }
                foreach ($replyHash as $user => $results) {
                    $statisticHash[$user] = array_merge($statisticHash[$user] ?? [], $results);
                }
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

        $monthName = date('F', $oldest);
        $monthEmoji = self::MONTH_EMOJI[date('n', $oldest)];
        $blocks = [
            BlocksConstructorKit::header(":$monthEmoji: $monthName checkin statistic"),
        ];
        $position = 0;
        foreach ($checkinArray as $user => $values) {
            if ($values['count'] <= 5) continue;

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

        $this->slackClient->send(
            method: HttpMethod::POST,
            path: $this->payload['response_url'],
            data: [
                'blocks' => $blocks
            ],
            options: [
                'absolute-path' => true,
                'content-type' => 'application/json',
                'data-type' => 'json'
            ]
        );
//        $this->slackClient->chatPostMessage(
//            token: $userToken,
//            channel: $this->payload['channel_id'],
//            blocks: $blocks
//        );
        return true;
    }

    protected function isThisDailyReminder($message): bool
    {
        $reminderText = "Reminder: Please post your daily check-in: :+1:What went well yesterday? :no_entry_sign:What was in your way
yesterday? :rocket:What are your goals for today?";
        return similar_text($reminderText, $message['text']) == "157";
    }
}
