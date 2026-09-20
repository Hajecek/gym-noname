<?php

declare(strict_types=1);

use App\Controllers\Api\V1\GymController;

$router = app()->router();

$router->get('/api/v1/gym', [GymController::class, 'gym']);
