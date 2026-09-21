<?php

declare(strict_types=1);

if (!defined('PRIVOFIT_FRONT')) {
    require dirname(__DIR__, 2) . '/index.php';
    exit;
}

use App\Controllers\Api\V1\GymController;

$router = app()->router();

$router->get('/api/v1/live', [GymController::class, 'live']);
