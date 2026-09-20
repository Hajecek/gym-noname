<?php

declare(strict_types=1);

if (!defined('PRIVOFIT_FRONT')) {
    require dirname(__DIR__, 2) . '/index.php';
    exit;
}

use App\Controllers\Api\V1\AccessController;
use App\Middleware\AuthMiddleware;

$router = app()->router();

$router->get('/api/v1/access/eligibility', [AccessController::class, 'eligibility'], [AuthMiddleware::class]);
$router->get('/api/v1/access/commands', [AccessController::class, 'commands'], [AuthMiddleware::class]);
$router->post('/api/v1/access/open', [AccessController::class, 'open'], [AuthMiddleware::class]);
$router->get('/api/v1/access/status', [AccessController::class, 'status'], [AuthMiddleware::class]);
