<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;

final class AdminMiddleware
{
    public function handle(Request $request, Application $app): void
    {
        (new RoleMiddleware('admin'))->handle($request, $app);
    }
}
