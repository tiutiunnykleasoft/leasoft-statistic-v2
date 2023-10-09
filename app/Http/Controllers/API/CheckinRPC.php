<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\checkinBuilder;
use App\Http\Controllers\Controller;
use App\Http\SlackClient\SlackClient;
use App\Models\UserSlack;
use DateTime;
use Illuminate\Http\Request;

class CheckinRPC extends Controller
{
    public function handleRpc(Request $request)
    {
        $channelId = $request->get('channelId');
        $teamId = $request->get('teamId');
        $type = $request->get('type');

        $checkinBuilder = new checkinBuilder(
            channelId: $channelId,
            teamId: $teamId
        );

        switch ($type) {
            case 'weekly' :
                $oldest = strtotime(date('Y-m-d 00:00:00', strtotime('last week monday')));
                $latest = strtotime(date('Y-m-d 23:59:59', strtotime('last week sunday')));
                break;
            case 'monthly' :
                $oldest = strtotime('first day of previous month');
                $oldest = strtotime('midnight', $oldest);

                $latest = strtotime('first day of this month');
                $latest = strtotime('midnight', $latest);
                break;
            default :
                break;
        }

        $response = $checkinBuilder->constructBlock(
            oldest: $oldest,
            latest: $latest,
            type: $type
        );

        /**
         * @var $client SlackClient
         */
        $client = app(SlackClient::class);
        $botLine = UserSlack::where('team_id', $teamId)->first();
        $botToken = $botLine->toArray()['bot_token'];
        $client->setToken($botToken);

        $client->chatPostMessage(
            //@TODO: Rework before release
            channel: $channelId,
//Test channel
//            channel: "G01GAKP04BV",
            text: 'Checkin',
            blocks: $response
        );
    }
}
