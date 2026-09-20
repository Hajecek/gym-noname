<?php

declare(strict_types=1);

use App\Controllers\Api\V1\HealthController;

$router = app()->router();

$router->get('/api/v1/health', [HealthController::class, 'health']);
