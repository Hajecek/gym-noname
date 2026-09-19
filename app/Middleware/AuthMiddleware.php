<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Session;

final class AuthMiddleware
{
    public function handle(Request $request, Application $app): void
    {
        if ($app->auth()->check()) {
            $user = $app->auth()->user();
            $required = (array) config('security.mfa_required_roles', []);
            $path = $request->path();
            if ($user && in_array($user['role'], $required, true) && (int) $user['mfa_enabled'] !== 1) {
                if (!str_starts_with($path, '/app/zabezpeceni/mfa') && $path !== '/odhlaseni' && !str_starts_with($path, '/api/v1/auth/')) {
                    if ($request->wantsJson()) {
                        throw new HttpException(403, 'Pro tento účet je nutné nastavit MFA.');
                    }
                    header('Location: ' . $app->url('/app/zabezpeceni/mfa'));
                    exit;
                }
            }
            return;
        }
        if ($request->wantsJson()) {
            throw new HttpException(401, 'Nejste přihlášeni.');
        }
        Session::set('intended', $request->path());
        Session::flash('error', 'Pro pokračování se prosím přihlaste.');
        header('Location: ' . $app->url('/prihlaseni'));
        exit;
    }
}
