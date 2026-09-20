<?php

declare(strict_types=1);

use App\Controllers\Api\V1\AuthController;
use App\Middleware\AuthMiddleware;

$router = app()->router();

$router->post('/api/v1/auth/register', [AuthController::class, 'register']);
$router->post('/api/v1/auth/login', [AuthController::class, 'login']);
$router->post('/api/v1/auth/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);
$router->post('/api/v1/auth/refresh', [AuthController::class, 'refresh']);
$router->post('/api/v1/auth/forgot-password', [AuthController::class, 'forgot']);
$router->post('/api/v1/auth/change-password', [AuthController::class, 'password'], [AuthMiddleware::class]);
$router->post('/api/v1/auth/reset-password', [AuthController::class, 'reset']);
$router->post('/api/v1/auth/verify-email', [AuthController::class, 'verifyEmail']);
$router->post('/api/v1/auth/resend-verification', [AuthController::class, 'resendVerification'], [AuthMiddleware::class]);
