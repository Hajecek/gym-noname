<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

final class InboxController extends Controller
{
    public function inbox(): never
    {
        $this->send($this->api()->inbox($this->requireUser()));
    }
}
