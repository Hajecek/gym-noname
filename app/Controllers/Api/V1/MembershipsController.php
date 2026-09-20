<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

final class MembershipsController extends Controller
{
    public function plans(): never
    {
        $this->send($this->api()->offers());
    }

    public function me(): never
    {
        $this->send($this->api()->membership($this->requireUser()));
    }
}
