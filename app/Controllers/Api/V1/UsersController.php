<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Request;
use App\Services\Auth\ValidationException;
use App\Services\AvatarService;

final class UsersController extends Controller
{
    public function me(): never
    {
        $this->send($this->api()->memberPayload($this->requireUser()));
    }

    public function deleteAccount(): never
    {
        try {
            $this->auth()->deleteAccount($this->requireUser());
            $this->send(['ok' => true]);
        } catch (\App\Core\HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function updateMe(Request $request): never
    {
        try {
            $user = $this->auth()->updateProfile($this->requireUser(), $request->all());
            $this->jsonOk(['user' => $this->auth()->publicUser($user)]);
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
            $this->auth()->changePassword(
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
        $this->jsonOk(['sessions' => $this->auth()->sessions((int) $this->requireUser()['id'])]);
    }

    public function deleteSession(Request $request, array $params): never
    {
        $this->auth()->revokeSession((int) $this->requireUser()['id'], (int) $params['id']);
        $this->jsonOk(null, 'Relace byla odhlášena.');
    }
}
