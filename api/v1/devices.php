<?php

declare(strict_types=1);

use App\Controllers\Api\V1\DevicesController;
use App\Middleware\AuthMiddleware;

$router = app()->router();

$router->post('/api/v1/devices/push', [DevicesController::class, 'push'], [AuthMiddleware::class]);
