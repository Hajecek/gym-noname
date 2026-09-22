<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Response;

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

    public function pass(): never
    {
        $user = $this->requireUser();
        $bytes = $this->api()->membershipPass($user);
        Response::send($bytes, 'application/vnd.apple.pkpass');
    }
}
