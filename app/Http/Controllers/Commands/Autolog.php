<?php

namespace App\Http\Controllers\Commands;

use App\Http\Controllers\checkinBuilder;
use App\Http\SlackClient\SlackClient;
use App\Models\UserSlack;
use function Symfony\Component\String\b;

class Autolog extends AbstractCommand
{

    public function respond()
    {
        /* @var $client SlackClient */
        $client = app(SlackClient::class);
        $userLine = UserSlack::where('user_id', $this->payload['user_id'])->first();
        $userToken = $userLine->toArray()['user_token'];
        $client->setToken($userToken);

        $oldest = strtotime('today');
        $oldest = strtotime('midnight', $oldest);

        $latest = strtotime('today');
        $latest = strtotime('11 p.m.', $latest);

        $history = $client->conversationsHistory(
            channel: $this->payload['channel_id'],
            oldest: $oldest,
            latest: $latest
        );

        foreach ($history as $message) {
            if (checkinBuilder::isThisDailyReminder($message)) {
                $replies = $client->conversationsReplies(
                    channel: $this->payload['channel_id'],
                    ts: $message['ts']
                );
                foreach ($replies as $reply) {
                    if ($reply['user'] == $this->payload['user_id']) {
                       //TODO: parse blocks to array to analyze and use in Jira API;
                        break;
                    }
                }
                break;
            }
        }
    }
}
