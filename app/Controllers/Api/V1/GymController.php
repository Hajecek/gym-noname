<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Request;

final class GymController extends Controller
{
    public function gym(Request $request, array $params = []): never
    {
        $this->send($this->api()->gymInfo());
    }

    public function gyms(Request $request, array $params = []): never
    {
        $this->send($this->api()->gyms());
    }

    public function live(Request $request, array $params = []): never
    {
        $this->send($this->api()->live());
    }
}
