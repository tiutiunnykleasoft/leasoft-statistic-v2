<?php

namespace App\Http\Controllers\Events;

final class urlVerification extends AbstractEvent
{
    public function respond()
    {
        echo $this->payload['challenge'];
    }
}
