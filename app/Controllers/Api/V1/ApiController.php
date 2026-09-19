<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Services\Access\AccessControlService;
use App\Services\Auth\AuthService;
use App\Services\Auth\MfaRequiredException;
use App\Services\Auth\ValidationException;
use App\Services\AvatarService;
use App\Services\MembershipService;
use App\Services\ReservationService;

final class ApiController extends Controller
{
    private function authService(): AuthService
    {
        return AuthService::make($this->app->db());
    }

    public function health(): never
    {
        $this->jsonOk([
            'status' => 'ok',
            'app' => 'PRIVOFIT',
            'time' => gmdate('c'),
        ], 'Služba běží.');
    }

    public function register(Request $request): never
    {
        try {
            $user = $this->authService()->register($request->all(), $request, $request->file('avatar'));
            $this->jsonOk(['user' => $this->authService()->publicUser($user)], 'Účet byl vytvořen. Ověřte e-mail.', 201);
        } catch (ValidationException $e) {
            $this->jsonError($e->getMessage(), 422, $e->errors);
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function login(Request $request): never
    {
        try {
            $user = $this->authService()->login(
                (string) $request->input('email', ''),
                (string) $request->input('password', ''),
                $request,
                false,
                $request->input('totp') !== null ? (string) $request->input('totp') : null
            );
            $tokens = $this->authService()->issueApiTokens(
                $user,
                $request,
                (string) $request->input('device_name', 'Mobilní aplikace'),
                (string) $request->input('platform', 'other')
            );
            $this->jsonOk(['user' => $this->authService()->publicUser($user), 'tokens' => $tokens]);
        } catch (MfaRequiredException) {
            $this->jsonError('Vyžadován TOTP kód.', 401, ['mfa' => true]);
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function refresh(Request $request): never
    {
        try {
            $tokens = $this->authService()->refreshApiToken((string) $request->input('refresh_token', ''), $request);
            $this->jsonOk(['tokens' => $tokens]);
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function logout(Request $request): never
    {
        $refresh = (string) $request->input('refresh_token', '');
        if ($refresh !== '') {
            $this->app->db()->update(
                'api_refresh_tokens',
                ['revoked_at' => \App\Support\Clock::utc()],
                'token_hash = :h',
                ['h' => \App\Core\Crypto::hash($refresh)]
            );
        }
        $this->jsonOk(null, 'Odhlášení bylo dokončeno.');
    }

    public function forgot(Request $request): never
    {
        $this->authService()->forgotPassword((string) $request->input('email', ''), $request);
        $this->jsonOk(null, 'Pokud účet existuje, odeslali jsme pokyny.');
    }

    public function reset(Request $request): never
    {
        try {
            $this->authService()->resetPassword(
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
            $this->authService()->verifyEmail((string) $request->input('token', $request->query('token', '')));
            $this->jsonOk(null, 'E-mail byl ověřen.');
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function resendVerification(): never
    {
        $user = $this->requireUser();
        $this->authService()->sendVerification($user);
        $this->jsonOk(null, 'Ověřovací e-mail byl odeslán.');
    }

    public function me(): never
    {
        $this->jsonOk(['user' => $this->authService()->publicUser($this->requireUser())]);
    }

    public function updateMe(Request $request): never
    {
        try {
            $user = $this->authService()->updateProfile($this->requireUser(), $request->all());
            $this->jsonOk(['user' => $this->authService()->publicUser($user)]);
        } catch (ValidationException $e) {
            $this->jsonError($e->getMessage(), 422, $e->errors);
        }
    }

    public function avatar(Request $request): never
    {
        $file = $request->file('avatar');
        if (!$file) {
            $this->jsonError('Chybí soubor avatar.', 422);
        }
        try {
            $path = (new AvatarService($this->app->db()))->storeFromUpload($this->requireUser(), $file);
            $this->jsonOk(['avatar_url' => url('/uploads/avatars/' . $path)]);
        } catch (\RuntimeException $e) {
            $this->jsonError($e->getMessage(), 422);
        }
    }

    public function deleteAvatar(): never
    {
        (new AvatarService($this->app->db()))->delete($this->requireUser());
        $this->jsonOk(null, 'Avatar byl odstraněn.');
    }

    public function password(Request $request): never
    {
        try {
            $this->authService()->changePassword(
                $this->requireUser(),
                (string) $request->input('current_password', ''),
                (string) $request->input('password', ''),
                (string) $request->input('password_confirmation', '')
            );
            $this->jsonOk(null, 'Heslo bylo změněno.');
        } catch (ValidationException $e) {
            $this->jsonError($e->getMessage(), 422, $e->errors);
        }
    }

    public function sessions(): never
    {
        $this->jsonOk(['sessions' => $this->authService()->sessions((int) $this->requireUser()['id'])]);
    }

    public function deleteSession(Request $request, array $params): never
    {
        $this->authService()->revokeSession((int) $this->requireUser()['id'], (int) $params['id']);
        $this->jsonOk(null, 'Relace byla odhlášena.');
    }

    public function availability(Request $request): never
    {
        $date = (string) $request->query('date', \App\Support\Clock::nowLocal()->format('Y-m-d'));
        $this->jsonOk(ReservationService::make($this->app->db())->availability($date));
    }

    public function reservations(): never
    {
        $this->jsonOk(['reservations' => ReservationService::make($this->app->db())->forUser((int) $this->requireUser()['id'])]);
    }

    public function createReservation(Request $request): never
    {
        try {
            $reservation = ReservationService::make($this->app->db())->create(
                $this->requireUser(),
                (string) $request->input('start'),
                (int) $request->input('duration', 60),
                (int) $request->input('guests', 1)
            );
            $this->jsonOk(['reservation' => $reservation], 'Rezervace byla vytvořena.', 201);
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function showReservation(Request $request, array $params): never
    {
        try {
            $reservation = ReservationService::make($this->app->db())->owned($this->requireUser(), (string) $params['id']);
            $this->jsonOk(['reservation' => $reservation]);
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function cancelReservation(Request $request, array $params): never
    {
        try {
            ReservationService::make($this->app->db())->cancel($this->requireUser(), (string) $params['id']);
            $this->jsonOk(null, 'Rezervace byla zrušena.');
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function plans(): never
    {
        $this->jsonOk(['plans' => (new MembershipService($this->app->db()))->plans()]);
    }

    public function myMembership(): never
    {
        $this->jsonOk(['membership' => (new MembershipService($this->app->db()))->activeForUser((int) $this->requireUser()['id'])]);
    }

    public function openDoor(Request $request): never
    {
        try {
            $result = $this->app->db()->transaction(function () use ($request) {
                return AccessControlService::make($this->app->db())->open($this->requireUser(), $request->ip());
            });
            $this->jsonOk($result, $result['message']);
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function accessStatus(): never
    {
        $access = AccessControlService::make($this->app->db());
        $this->jsonOk([
            'authorization' => $access->canAttempt($this->requireUser()),
            'door' => $access->doorStatus(),
        ]);
    }
}
