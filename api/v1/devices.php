<?php

declare(strict_types=1);

if (!defined('PRIVOFIT_FRONT')) {
    require dirname(__DIR__, 2) . '/index.php';
    exit;
}

use App\Controllers\Api\V1\DevicesController;
use App\Middleware\AuthMiddleware;

$router = app()->router();

$router->post('/api/v1/devices/push', [DevicesController::class, 'push'], [AuthMiddleware::class]);
