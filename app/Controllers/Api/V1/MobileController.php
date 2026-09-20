<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\Auth\AuthService;
use App\Services\Auth\MfaRequiredException;
use App\Services\Auth\ValidationException;
use App\Services\Mobile\MobileApiService;

final class MobileController extends Controller
{
    private function api(): MobileApiService
    {
        return MobileApiService::make($this->app->db());
    }

    private function auth(): AuthService
    {
        return AuthService::make($this->app->db());
    }

    public function login(Request $request): never
    {
        try {
            $user = $this->auth()->login(
                $this->str($request, 'identifier', 'email'),
                (string) $request->input('password', ''),
                $request,
                false,
                $request->input('totp') !== null ? (string) $request->input('totp') : null
            );
            $this->send($this->api()->issueSession($user, $request));
        } catch (MfaRequiredException) {
            $this->jsonError('Vyžadován TOTP kód.', 401, ['mfa' => true]);
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

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

    public function deleteAccount(): never
    {
        try {
            $this->auth()->deleteAccount($this->requireUser());
            $this->send(['ok' => true]);
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function me(): never
    {
        $this->send($this->api()->memberPayload($this->requireUser()));
    }

    public function gym(): never
    {
        $this->send($this->api()->gymInfo());
    }

    public function offers(): never
    {
        $this->send($this->api()->offers());
    }

    public function membership(): never
    {
        $this->send($this->api()->membership($this->requireUser()));
    }

    public function visits(): never
    {
        $this->send($this->api()->visits($this->requireUser()));
    }

    public function reservations(): never
    {
        $this->send($this->api()->reservations($this->requireUser()));
    }

    public function slots(): never
    {
        $this->send($this->api()->slots());
    }

    public function quote(Request $request): never
    {
        try {
            $this->send($this->api()->quote($this->requireUser(), $this->stringList($request, 'slotIDs', 'slot_ids')));
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function reserve(Request $request): never
    {
        try {
            $slot = $this->str($request, 'slotID', 'slot_id');
            $requestId = $this->str($request, 'requestID', 'request_id');
            $this->send($this->api()->reserve($this->requireUser(), $slot, $requestId), 201);
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function pay(Request $request): never
    {
        try {
            $applePay = (array) ($request->input('applePay') ?? $request->input('apple_pay') ?? []);
            $this->send($this->api()->payAndReserve(
                $this->requireUser(),
                $this->stringList($request, 'slotIDs', 'slot_ids'),
                $this->str($request, 'requestID', 'request_id'),
                $applePay
            ));
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function cancel(Request $request, array $params): never
    {
        try {
            $this->api()->cancel(
                $this->requireUser(),
                (string) $params['id'],
                $this->str($request, 'requestID', 'request_id')
            );
            $this->send(['ok' => true]);
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function inbox(): never
    {
        $this->send($this->api()->inbox($this->requireUser()));
    }

    public function eligibility(): never
    {
        $this->send($this->api()->eligibility($this->requireUser()));
    }

    public function openDoor(Request $request): never
    {
        try {
            $this->send($this->api()->openDoor(
                $this->requireUser(),
                $this->str($request, 'doorID', 'door_id'),
                $this->str($request, 'requestID', 'request_id'),
                $request->ip()
            ));
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function doorStatus(Request $request): never
    {
        try {
            $operation = $request->input('operationID', $request->query('operationID', $request->query('operation_id')));
            $this->send($this->api()->doorStatus(
                $this->requireUser(),
                $this->str($request, 'requestID', 'request_id') !== ''
                    ? $this->str($request, 'requestID', 'request_id')
                    : (string) $request->query('requestID', $request->query('request_id', '')),
                is_string($operation) && $operation !== '' ? $operation : null
            ));
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function push(Request $request): never
    {
        $token = $this->str($request, 'token', 'token');
        $preferences = (array) ($request->input('preferences') ?? []);
        $this->api()->registerPush($this->requireUser(), $request, $token, $preferences);
        $this->send(['ok' => true]);
    }

    private function send(mixed $payload, int $status = 200): never
    {
        Response::json(is_array($payload) ? $payload : ['ok' => true], $status);
    }

    private function str(Request $request, string $camel, string $snake, string $default = ''): string
    {
        $value = $request->input($camel, $request->input($snake, $default));
        return is_string($value) ? trim($value) : $default;
    }

    /** @return list<string> */
    private function stringList(Request $request, string $camel, string $snake): array
    {
        $value = $request->input($camel, $request->input($snake, []));
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $out[] = $item;
            }
        }
        return $out;
    }
}
