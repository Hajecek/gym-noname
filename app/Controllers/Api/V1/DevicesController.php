<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Request;

final class DevicesController extends Controller
{
    public function push(Request $request): never
    {
        $token = $this->str($request, 'token', 'token');
        $preferences = (array) ($request->input('preferences') ?? []);
        $this->api()->registerPush($this->requireUser(), $request, $token, $preferences);
        $this->send(['ok' => true]);
    }
}
