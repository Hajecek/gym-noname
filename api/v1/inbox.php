<?php

declare(strict_types=1);

use App\Controllers\Api\V1\InboxController;
use App\Middleware\AuthMiddleware;

$router = app()->router();

$router->get('/api/v1/inbox', [InboxController::class, 'inbox'], [AuthMiddleware::class]);
