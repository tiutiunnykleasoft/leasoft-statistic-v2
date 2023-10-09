<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\checkinBuilder;
use App\Http\Controllers\Controller;
use App\Http\SlackClient\BlocksConstructorKit;
use App\Http\SlackClient\SlackClient;
use App\Models\UserSlack;
use DateTime;
use Illuminate\Http\Request;

class MessageRPC extends Controller
{
    public function handleRpc(Request $request)
    {
        $channelId = $request->get('channelId');
        $message = $request->get('message');
        $teamId = $request->get('teamId');

        $blocks = [BlocksConstructorKit::header('Feature announcement')];

        $blocks[] = BlocksConstructorKit::section(
            BlocksConstructorKit::markdown($message)
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
// Test channel
//            channel: "G01GAKP04BV",
            text: 'Checkin',
            blocks: $blocks
        );
    }
}
