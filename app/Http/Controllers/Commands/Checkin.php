<?php

namespace App\Http\Controllers\Commands;

use App\Http\Controllers\checkinBuilder;
use App\Http\SlackClient\BlocksConstructorKit;
use App\Http\SlackClient\HttpMethod;
use App\Jobs\ProcessRpcCall;
use App\Models\UserSlack;
use Illuminate\Http\Request;

class Checkin extends AbstractCommand
{
    public function respond()
    {
        $request = Request::createFromGlobals();
        ProcessRpcCall::dispatch(
            url: $request->getSchemeAndHttpHost() . '/api/checkin',
            requestData: [
                'channelId' => $this->payload['channel_id'],
                'teamId' => $this->payload['team_id']
            ]
        );

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
        http_response_code(200);
    }
}
