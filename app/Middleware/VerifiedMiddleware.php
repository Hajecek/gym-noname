<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Session;

final class VerifiedMiddleware
{
    public function handle(Request $request, Application $app): void
    {
        $user = $app->auth()->user();
        if (!$user) {
            throw new HttpException(401, 'Nejste přihlášeni.');
        }
        if (!empty($user['email_verified_at'])) {
            return;
        }
        if ($request->wantsJson()) {
            throw new HttpException(403, 'Nejprve ověřte svou e-mailovou adresu.');
        }
        Session::flash('error', 'Nejprve ověřte svou e-mailovou adresu. Zkontrolujte schránku.');
        header('Location: ' . $app->url('/app/overeni'));
        exit;
    }
}
