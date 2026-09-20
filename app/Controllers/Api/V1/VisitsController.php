<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

final class VisitsController extends Controller
{
    public function visits(): never
    {
        $this->send($this->api()->visits($this->requireUser()));
    }
}
