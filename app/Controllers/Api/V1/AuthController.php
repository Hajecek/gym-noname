<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\HttpException;
use App\Core\Request;
use App\Services\Auth\AppleOAuth;
use App\Services\Auth\MfaRequiredException;
use App\Services\Auth\ValidationException;

final class AuthController extends Controller
{
    public function register(Request $request): never
    {
        try {
            $user = $this->auth()->registerFromApp($request->all(), $request);
            $this->send($this->api()->issueSession($user, $request), 201);
        } catch (ValidationException $e) {
            $this->jsonError($e->getMessage(), 422, $e->errors);
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function login(Request $request): never
    {
        try {
            $user = $this->auth()->loginFromApp(
                $this->str($request, 'identifier', 'email'),
                (string) $request->input('password', ''),
                $request,
                $request->input('totp') !== null ? (string) $request->input('totp') : null
            );
            $this->send($this->api()->issueSession($user, $request));
        } catch (MfaRequiredException) {
            $this->jsonError('Vyžadován TOTP kód.', 401, ['mfa' => true]);
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function google(Request $request): never
    {
        try {
            $this->auth()->assertOAuthAttempt($request);
            $user = $this->auth()->consumeMobileLoginTicket($this->str($request, 'ticket', 'ticket'));
            $this->send($this->api()->issueSession($user, $request));
        } catch (HttpException $e) {
            $status = $e->status === 403 ? 422 : $e->status;
            $this->jsonError($e->getMessage(), $status);
        }
    }

    public function apple(Request $request): never
    {
        $this->oauthSession($request, function () use ($request): array {
            $given = $request->input('givenName', $request->input('given_name'));
            $family = $request->input('familyName', $request->input('family_name'));
            return AppleOAuth::profileFromMobile(
                $this->str($request, 'identityToken', 'identity_token'),
                $this->str($request, 'rawNonce', 'raw_nonce'),
                $this->str($request, 'authorizationCode', 'code'),
                is_string($given) ? $given : null,
                is_string($family) ? $family : null
            );
        }, 'apple');
    }

    /** @param callable(): array{sub:string,email:string,given_name:string,family_name:string,name:string} $profile */
    private function oauthSession(Request $request, callable $profile, string $provider): never
    {
        try {
            $this->auth()->assertOAuthAttempt($request);
            $totp = $request->input('totp');
            $user = $this->auth()->loginFromAppWithOAuth(
                $provider,
                $profile(),
                $request,
                is_string($totp) && $totp !== '' ? $totp : null
            );
            $this->send($this->api()->issueSession($user, $request));
        } catch (MfaRequiredException) {
            $this->jsonError('Vyžadován ověřovací kód.', 422, ['mfa' => true]);
        } catch (HttpException $e) {
            $status = $e->status === 403 ? 422 : $e->status;
            $this->jsonError($e->getMessage(), $status);
        }
    }

    public function refresh(Request $request): never
    {
        try {
            $tokens = $this->auth()->refreshApiToken($this->str($request, 'refreshToken', 'refresh_token'), $request);
            $this->send($this->api()->sessionPayload($tokens));
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function logout(Request $request): never
    {
        $this->api()->revokeCurrentDevice($request);
        $refresh = $this->str($request, 'refreshToken', 'refresh_token');
        if ($refresh !== '') {
            $this->app->db()->update(
                'api_refresh_tokens',
                ['revoked_at' => \App\Support\Clock::utc()],
                'token_hash = :h',
                ['h' => \App\Core\Crypto::hash($refresh)]
            );
        }
        $this->send(['ok' => true]);
    }

    public function forgot(Request $request): never
    {
        try {
            $this->auth()->forgotPasswordFromApp($this->str($request, 'identifier', 'email'), $request);
            $this->send(['ok' => true]);
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function password(Request $request): never
    {
        try {
            $new = $this->str($request, 'newPassword', 'password');
            $this->auth()->changePassword(
                $this->requireUser(),
                $this->str($request, 'currentPassword', 'current_password'),
                $new,
                (string) $request->input('password_confirmation', $new)
            );
            $this->send(['ok' => true]);
        } catch (ValidationException $e) {
            $this->jsonError($e->getMessage(), 422, $e->errors);
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function reset(Request $request): never
    {
        try {
            $this->auth()->resetPassword(
                (string) $request->input('token', ''),
                (string) $request->input('password', ''),
                (string) $request->input('password_confirmation', '')
            );
            $this->jsonOk(null, 'Heslo bylo změněno.');
        } catch (ValidationException $e) {
            $this->jsonError($e->getMessage(), 422, $e->errors);
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function verifyEmail(Request $request): never
    {
        try {
            $this->auth()->verifyEmail((string) $request->input('token', $request->query('token', '')));
            $this->jsonOk(null, 'E-mail byl ověřen.');
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function resendVerification(): never
    {
        $this->auth()->sendVerification($this->requireUser());
        $this->jsonOk(null, 'Ověřovací e-mail byl odeslán.');
    }
}
