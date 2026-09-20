<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

final class GymController extends Controller
{
    public function gym(): never
    {
        $this->send($this->api()->gymInfo());
    }
}
