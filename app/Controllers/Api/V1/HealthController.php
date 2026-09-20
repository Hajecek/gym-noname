<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

final class HealthController extends Controller
{
    public function health(): never
    {
        $this->jsonOk([
            'status' => 'ok',
            'app' => 'PRIVOFIT',
            'time' => gmdate('c'),
        ], 'Služba běží.');
    }
}
