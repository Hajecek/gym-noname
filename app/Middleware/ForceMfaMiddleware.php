<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;

final class ForceMfaMiddleware
{
    public function handle(Request $request, Application $app): void
    {
        $user = $app->auth()->user();
        if (!$user) {
            return;
        }
        $required = (array) config('security.mfa_required_roles', []);
        if (!in_array($user['role'], $required, true) || (int) $user['mfa_enabled'] === 1) {
            return;
        }
        $path = $request->path();
        if (str_starts_with($path, '/app/zabezpeceni/mfa') || $path === '/odhlaseni') {
            return;
        }
        if ($request->wantsJson()) {
            throw new \App\Core\HttpException(403, 'Pro tento účet je nutné nastavit MFA.');
        }
        header('Location: ' . $app->url('/app/zabezpeceni/mfa'));
        exit;
    }
}
