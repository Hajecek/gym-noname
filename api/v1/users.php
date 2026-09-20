<?php

declare(strict_types=1);

if (!defined('PRIVOFIT_FRONT')) {
    require dirname(__DIR__, 2) . '/index.php';
    exit;
}

use App\Controllers\Api\V1\UsersController;
use App\Middleware\AuthMiddleware;

$router = app()->router();

$router->get('/api/v1/users/me', [UsersController::class, 'me'], [AuthMiddleware::class]);
$router->delete('/api/v1/users/me', [UsersController::class, 'deleteAccount'], [AuthMiddleware::class]);
$router->patch('/api/v1/users/me', [UsersController::class, 'updateMe'], [AuthMiddleware::class]);
$router->post('/api/v1/users/me/avatar', [UsersController::class, 'avatar'], [AuthMiddleware::class]);
$router->delete('/api/v1/users/me/avatar', [UsersController::class, 'deleteAvatar'], [AuthMiddleware::class]);
$router->patch('/api/v1/users/me/password', [UsersController::class, 'password'], [AuthMiddleware::class]);
$router->get('/api/v1/users/me/sessions', [UsersController::class, 'sessions'], [AuthMiddleware::class]);
$router->delete('/api/v1/users/me/sessions/{id}', [UsersController::class, 'deleteSession'], [AuthMiddleware::class]);
