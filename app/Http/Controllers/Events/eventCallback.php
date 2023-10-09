<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\API\CheckinRPC;
use App\Jobs\ProcessRpcCall;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class eventCallback extends AbstractEvent
{
    public function respond()
    {
        http_response_code(200);
        $functionName = $this->payload['event']['type'];
        return $this->$functionName($this->payload['event']);
    }

    public function message(array $eventPayload)
    {
        switch (true) {
            case preg_match('/^Reminder: Post monthly checkin.$/', $eventPayload['text']):
                return $this->getMonthlyCheckin($eventPayload);
            case preg_match('/^Reminder: Post weekly checkin.$/', $eventPayload['text']):
                return $this->getWeeklyCheckin($eventPayload);
            default:
                break;
        }
    }

    public function getWeeklyCheckin($payload)
    {
        $request = Request::createFromGlobals();

        ProcessRpcCall::dispatch(
            url: $request->getSchemeAndHttpHost() . '/api/checkin',
            requestData: [
                'channelId' => "C01D3LT34LB",
                'teamId' => $payload['team'],
                'type' => 'weekly'
            ]
        );

        http_response_code(200);
    }

    public function getMonthlyCheckin($payload)
    {
        $request = Request::createFromGlobals();

        ProcessRpcCall::dispatch(
            url: $request->getSchemeAndHttpHost() . '/api/checkin',
            requestData: [
                'channelId' => "C01D3LT34LB",
                'teamId' => $payload['team'],
                'type' => 'monthly'
            ]
        );

        http_response_code(200);
    }
}
