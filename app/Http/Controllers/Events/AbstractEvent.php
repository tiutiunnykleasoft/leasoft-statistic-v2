<?php

namespace App\Http\Controllers\Events;

abstract class AbstractEvent implements Event
{
    public function __construct(protected array $payload)
    {

    }
}
