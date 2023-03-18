<?php

namespace App\Http\Controllers\Commands;

use App\Http\SlackClient\SlackClient;

abstract class AbstractCommand implements Command
{
    public function __construct(
        protected array       $payload,
        protected SlackClient $slackClient
    )
    {
    }
}
