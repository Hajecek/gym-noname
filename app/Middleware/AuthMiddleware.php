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
            $mfaGate = str_starts_with($path, '/admin')
                || str_starts_with($path, '/user/sprava')
                || str_starts_with($path, '/user/studio');
            if ($mfaGate && $user && in_array($user['role'], $required, true) && (int) $user['mfa_enabled'] !== 1) {
                if (!str_starts_with($path, '/user/zabezpeceni/mfa') && $path !== '/odhlaseni' && !str_starts_with($path, '/api/v1/auth/')) {
                    if ($request->wantsJson()) {
                        throw new HttpException(403, 'Pro tento účet je nutné nastavit MFA.');
                    }
                    header('Location: ' . $app->url('/user/zabezpeceni/mfa'));
                    exit;
                }
            }
            return;
        }
        $idle = Session::pull('logged_out_reason') === 'idle';
        if ($request->wantsJson()) {
            throw new HttpException(401, $idle ? 'Odhlásili jsme tě z důvodu bezpečnosti.' : 'Nejste přihlášeni.');
        }
        if ($idle) {
            header('Location: ' . $app->url('/odhlaseno'));
            exit;
        }
        $intended = $request->path();
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
        if ($query !== '') {
            $intended .= '?' . $query;
        }
        Session::set('intended', $intended);
        Session::flash('error', 'Pro pokračování se prosím přihlaste.');
        header('Location: ' . $app->url('/prihlaseni'));
        exit;
    }
}
