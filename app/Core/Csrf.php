<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf');
        if (!is_string($token) || $token === '') {
            $token = Crypto::token(32);
            Session::set('_csrf', $token);
        }
        return $token;
    }

    public static function verify(?string $token): bool
    {
        $expected = Session::get('_csrf');
        return is_string($expected) && is_string($token) && hash_equals($expected, $token);
    }

    public static function verifyRequest(Request $request): bool
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }
        if ($request->isApi() && $request->bearerToken()) {
            return true;
        }
        $token = $request->input('_csrf')
            ?? $request->header('X-CSRF-TOKEN')
            ?? $request->header('X-Csrf-Token');
        return self::verify(is_string($token) ? $token : null);
    }
}
