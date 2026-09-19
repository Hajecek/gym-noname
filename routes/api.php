<?php

declare(strict_types=1);

use App\Controllers\Api\V1\ApiController;
use App\Middleware\AuthMiddleware;

$router = app()->router();

$router->get('/api/v1/health', [ApiController::class, 'health']);
$router->post('/api/v1/auth/register', [ApiController::class, 'register']);
$router->post('/api/v1/auth/login', [ApiController::class, 'login']);
$router->post('/api/v1/auth/logout', [ApiController::class, 'logout']);
$router->post('/api/v1/auth/refresh', [ApiController::class, 'refresh']);
$router->post('/api/v1/auth/forgot-password', [ApiController::class, 'forgot']);
$router->post('/api/v1/auth/reset-password', [ApiController::class, 'reset']);
$router->post('/api/v1/auth/verify-email', [ApiController::class, 'verifyEmail']);
$router->post('/api/v1/auth/resend-verification', [ApiController::class, 'resendVerification'], [AuthMiddleware::class]);

$router->get('/api/v1/users/me', [ApiController::class, 'me'], [AuthMiddleware::class]);
$router->patch('/api/v1/users/me', [ApiController::class, 'updateMe'], [AuthMiddleware::class]);
$router->post('/api/v1/users/me/avatar', [ApiController::class, 'avatar'], [AuthMiddleware::class]);
$router->delete('/api/v1/users/me/avatar', [ApiController::class, 'deleteAvatar'], [AuthMiddleware::class]);
$router->patch('/api/v1/users/me/password', [ApiController::class, 'password'], [AuthMiddleware::class]);
$router->get('/api/v1/users/me/sessions', [ApiController::class, 'sessions'], [AuthMiddleware::class]);
$router->delete('/api/v1/users/me/sessions/{id}', [ApiController::class, 'deleteSession'], [AuthMiddleware::class]);

$router->get('/api/v1/reservations/availability', [ApiController::class, 'availability']);
$router->get('/api/v1/reservations', [ApiController::class, 'reservations'], [AuthMiddleware::class]);
$router->post('/api/v1/reservations', [ApiController::class, 'createReservation'], [AuthMiddleware::class]);
$router->get('/api/v1/reservations/{id}', [ApiController::class, 'showReservation'], [AuthMiddleware::class]);
$router->post('/api/v1/reservations/{id}/cancel', [ApiController::class, 'cancelReservation'], [AuthMiddleware::class]);

$router->get('/api/v1/memberships/plans', [ApiController::class, 'plans']);
$router->get('/api/v1/memberships/me', [ApiController::class, 'myMembership'], [AuthMiddleware::class]);

$router->post('/api/v1/access/open', [ApiController::class, 'openDoor'], [AuthMiddleware::class]);
$router->get('/api/v1/access/status', [ApiController::class, 'accessStatus'], [AuthMiddleware::class]);
