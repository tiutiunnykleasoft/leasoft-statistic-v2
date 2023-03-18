<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Commands\Command;
use App\Http\SlackClient\SlackClient;
use Illuminate\Http\Request;

class CommandHandler extends Controller
{
    private Command $handler;

    public function dispatch(Request $request, SlackClient $slackClient)
    {
        $command = $request->get('command');
        $command = explode('/', $command);
        $commandString = implode('|', $command);
        $className = ucwords($commandString, '|');
        $className = ucfirst(str_replace('|', '', $className));
        $className = implode('\\', ["App\Http\Controllers\Commands", $className]);

        $this->handler = new $className($request->toArray(),  $slackClient);
        $this->handler->respond();
    }
}
