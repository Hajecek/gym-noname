<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;

final class GuestMiddleware
{
    public function handle(Request $request, Application $app): void
    {
        if (!$app->auth()->check()) {
            return;
        }
        header('Location: ' . $app->url('/user'));
        exit;
    }
}
