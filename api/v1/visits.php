<?php

declare(strict_types=1);

use App\Controllers\Api\V1\VisitsController;
use App\Middleware\AuthMiddleware;

$router = app()->router();

$router->get('/api/v1/visits', [VisitsController::class, 'visits'], [AuthMiddleware::class]);
