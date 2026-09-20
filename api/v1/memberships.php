<?php

declare(strict_types=1);

use App\Controllers\Api\V1\MembershipsController;
use App\Middleware\AuthMiddleware;

$router = app()->router();

$router->get('/api/v1/memberships/plans', [MembershipsController::class, 'plans']);
$router->get('/api/v1/memberships/me', [MembershipsController::class, 'me'], [AuthMiddleware::class]);
