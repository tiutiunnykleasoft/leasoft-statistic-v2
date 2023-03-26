<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\checkinBuilder;
use App\Http\Controllers\Controller;
use App\Http\SlackClient\SlackClient;
use App\Models\UserSlack;
use Illuminate\Http\Request;

class CheckinRPC extends Controller
{
    public function handleRpc(Request $request)
    {
        $channelId = $request->get('channelId');
        $teamId = $request->get('teamId');

        $checkinBuilder = new checkinBuilder(
            channelId: $channelId,
            teamId: $teamId
        );

        $oldest = strtotime('first day of previous month');
        $oldest = strtotime('midnight', $oldest);

        $latest = strtotime('first day of this month');
        $latest = strtotime('midnight', $latest);
        $response = $checkinBuilder->constructBlock(
            $oldest,
            $latest
        );

        /**
         * @var $client SlackClient
         */
        $client = app(SlackClient::class);
        $botLine = UserSlack::where('team_id', $teamId)->first();
        $botToken = $botLine->toArray()['bot_token'];
        $client->setToken($botToken);

        $client->chatPostMessage(
            channel: $channelId,
            text: 'Checkin',
            blocks: $response
        );
    }
}
