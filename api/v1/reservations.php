<?php

declare(strict_types=1);

if (!defined('PRIVOFIT_FRONT')) {
    require dirname(__DIR__, 2) . '/index.php';
    exit;
}

use App\Controllers\Api\V1\ReservationsController;
use App\Middleware\AuthMiddleware;

$router = app()->router();

$router->get('/api/v1/reservations/availability', [ReservationsController::class, 'availability']);
$router->get('/api/v1/reservations/slots', [ReservationsController::class, 'slots'], [AuthMiddleware::class]);
$router->post('/api/v1/reservations/quote', [ReservationsController::class, 'quote'], [AuthMiddleware::class]);
$router->post('/api/v1/reservations/pay', [ReservationsController::class, 'pay'], [AuthMiddleware::class]);
$router->get('/api/v1/reservations', [ReservationsController::class, 'reservations'], [AuthMiddleware::class]);
$router->post('/api/v1/reservations', [ReservationsController::class, 'reserve'], [AuthMiddleware::class]);
$router->get('/api/v1/reservations/{id}', [ReservationsController::class, 'show'], [AuthMiddleware::class]);
$router->post('/api/v1/reservations/{id}/cancel', [ReservationsController::class, 'cancel'], [AuthMiddleware::class]);
