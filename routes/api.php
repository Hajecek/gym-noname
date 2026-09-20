<?php

declare(strict_types=1);

use App\Controllers\Api\V1\ApiController;
use App\Controllers\Api\V1\MobileController;
use App\Middleware\AuthMiddleware;

$router = app()->router();

$router->get('/api/v1/health', [ApiController::class, 'health']);

$router->post('/api/v1/auth/register', [MobileController::class, 'register']);
$router->post('/api/v1/auth/login', [MobileController::class, 'login']);
$router->post('/api/v1/auth/logout', [MobileController::class, 'logout'], [AuthMiddleware::class]);
$router->post('/api/v1/auth/refresh', [MobileController::class, 'refresh']);
$router->post('/api/v1/auth/forgot-password', [MobileController::class, 'forgot']);
$router->post('/api/v1/auth/change-password', [MobileController::class, 'password'], [AuthMiddleware::class]);
$router->post('/api/v1/auth/reset-password', [ApiController::class, 'reset']);
$router->post('/api/v1/auth/verify-email', [ApiController::class, 'verifyEmail']);
$router->post('/api/v1/auth/resend-verification', [ApiController::class, 'resendVerification'], [AuthMiddleware::class]);

$router->get('/api/v1/users/me', [MobileController::class, 'me'], [AuthMiddleware::class]);
$router->delete('/api/v1/users/me', [MobileController::class, 'deleteAccount'], [AuthMiddleware::class]);
$router->patch('/api/v1/users/me', [ApiController::class, 'updateMe'], [AuthMiddleware::class]);
$router->post('/api/v1/users/me/avatar', [ApiController::class, 'avatar'], [AuthMiddleware::class]);
$router->delete('/api/v1/users/me/avatar', [ApiController::class, 'deleteAvatar'], [AuthMiddleware::class]);
$router->patch('/api/v1/users/me/password', [ApiController::class, 'password'], [AuthMiddleware::class]);
$router->get('/api/v1/users/me/sessions', [ApiController::class, 'sessions'], [AuthMiddleware::class]);
$router->delete('/api/v1/users/me/sessions/{id}', [ApiController::class, 'deleteSession'], [AuthMiddleware::class]);

$router->get('/api/v1/gym', [MobileController::class, 'gym']);
$router->get('/api/v1/visits', [MobileController::class, 'visits'], [AuthMiddleware::class]);
$router->get('/api/v1/inbox', [MobileController::class, 'inbox'], [AuthMiddleware::class]);
$router->post('/api/v1/devices/push', [MobileController::class, 'push'], [AuthMiddleware::class]);

$router->get('/api/v1/reservations/availability', [ApiController::class, 'availability']);
$router->get('/api/v1/reservations/slots', [MobileController::class, 'slots'], [AuthMiddleware::class]);
$router->post('/api/v1/reservations/quote', [MobileController::class, 'quote'], [AuthMiddleware::class]);
$router->post('/api/v1/reservations/pay', [MobileController::class, 'pay'], [AuthMiddleware::class]);
$router->get('/api/v1/reservations', [MobileController::class, 'reservations'], [AuthMiddleware::class]);
$router->post('/api/v1/reservations', [MobileController::class, 'reserve'], [AuthMiddleware::class]);
$router->get('/api/v1/reservations/{id}', [ApiController::class, 'showReservation'], [AuthMiddleware::class]);
$router->post('/api/v1/reservations/{id}/cancel', [MobileController::class, 'cancel'], [AuthMiddleware::class]);

$router->get('/api/v1/memberships/plans', [MobileController::class, 'offers']);
$router->get('/api/v1/memberships/me', [MobileController::class, 'membership'], [AuthMiddleware::class]);

$router->get('/api/v1/access/eligibility', [MobileController::class, 'eligibility'], [AuthMiddleware::class]);
$router->get('/api/v1/access/commands', [MobileController::class, 'doorStatus'], [AuthMiddleware::class]);
$router->post('/api/v1/access/open', [MobileController::class, 'openDoor'], [AuthMiddleware::class]);
$router->get('/api/v1/access/status', [ApiController::class, 'accessStatus'], [AuthMiddleware::class]);
