<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Events\Event;
use Illuminate\Http\Request;

class EventHandler extends Controller
{
    private Event $handler;

    public function dispatch(Request $request)
    {
        $payload = $request->toArray();
        $type = str_replace('_', ' ', $payload['type']);
        $type = ucwords($type);
        $className = lcfirst(str_replace(' ', '', $type));
        $className = implode('\\', ["App\Http\Controllers\Events", $className]);
        $this->handler = new $className($payload);
        $this->handler->respond();
    }
}
