<?php

declare(strict_types=1);

if (!defined('PRIVOFIT_FRONT')) {
    require dirname(__DIR__, 2) . '/index.php';
    exit;
}

use App\Controllers\Api\V1\VisitsController;
use App\Middleware\AuthMiddleware;

$router = app()->router();

$router->get('/api/v1/visits', [VisitsController::class, 'visits'], [AuthMiddleware::class]);
