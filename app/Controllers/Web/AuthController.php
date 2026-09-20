<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Session;
use App\Services\Auth\AuthService;
use App\Services\Auth\MfaRequiredException;
use App\Services\Auth\MfaSetupRequiredException;
use App\Services\Auth\ValidationException;

final class AuthController extends Controller
{
    private function authService(): AuthService
    {
        return AuthService::make($this->app->db());
    }

    public function showRegister(): never
    {
        $this->view('auth/register', [
            'title' => 'Registrace | PRIVOFIT',
            'page' => 'register',
            'bodyClass' => 'standalone-registration',
            'errors' => Session::pull('errors', []),
        ], 'layouts/brand');
    }

    public function register(Request $request): never
    {
        $this->rememberOld($request);
        try {
            $this->authService()->register($request->all(), $request, $request->file('avatar'));
        } catch (ValidationException $e) {
            Session::set('errors', $e->errors);
            $this->flashError($e->getMessage());
            $this->redirect('/registrace');
        } catch (HttpException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('/registrace');
        } catch (\RuntimeException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('/registrace');
        }
        Session::forget('_old');
        $this->flashSuccess('Účet byl vytvořen. Poslali jsme vám ověřovací e-mail.');
        $this->redirect('/prihlaseni');
    }

    public function showLogin(): never
    {
        $this->view('auth/login', [
            'title' => 'Přihlášení | PRIVOFIT',
            'page' => 'login',
            'bodyClass' => 'standalone-login',
            'mfa' => false,
        ], 'layouts/brand');
    }

    public function login(Request $request): never
    {
        try {
            $pendingId = Session::get('mfa_pending_user_id');
            if (is_numeric($pendingId) && $request->input('totp')) {
                $user = $this->authService()->completeMfaLogin((int) $pendingId, (string) $request->input('totp'), $request, (bool) Session::get('mfa_pending_remember'));
                Session::forget('mfa_pending_user_id');
                Session::forget('mfa_pending_remember');
            } else {
                $user = $this->authService()->login(
                    (string) ($request->input('identifier') ?: $request->input('email', '')),
                    (string) $request->input('password', ''),
                    $request,
                    (bool) $request->input('remember'),
                    $request->input('totp') !== null ? (string) $request->input('totp') : null
                );
            }
            $this->app->auth()->setUser($user, (int) Session::get('auth_session_id'));
        } catch (MfaRequiredException $e) {
            Session::set('mfa_pending_user_id', (int) $e->user['id']);
            Session::set('mfa_pending_remember', (bool) $request->input('remember'));
            $this->view('auth/login', [
                'title' => 'Ověření přihlášení | PRIVOFIT',
                'page' => 'login',
                'bodyClass' => 'standalone-login',
                'mfa' => true,
                'email' => $e->user['email'],
                'remember' => (bool) $request->input('remember'),
            ], 'layouts/brand');
        } catch (MfaSetupRequiredException) {
            $this->redirect('/user/zabezpeceni/mfa');
        } catch (HttpException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('/prihlaseni');
        }
        $intended = Session::pull('intended', '/user');
        $this->redirect(is_string($intended) ? $intended : '/user');
    }

    public function logout(Request $request): never
    {
        $this->authService()->logout($request);
        $this->flashSuccess('Byli jste odhlášeni.');
        $this->redirect('/');
    }

    public function showForgot(): never
    {
        $this->view('auth/forgot', ['title' => 'Zapomenuté heslo | PRIVOFIT', 'page' => 'login'], 'layouts/brand');
    }

    public function forgot(Request $request): never
    {
        try {
            $this->authService()->forgotPassword((string) $request->input('email', ''), $request);
        } catch (HttpException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('/zapomenute-heslo');
        }
        $this->flashSuccess('Pokud účet existuje, poslali jsme pokyny k obnovení hesla.');
        $this->redirect('/prihlaseni');
    }

    public function showReset(Request $request): never
    {
        $this->view('auth/reset', [
            'title' => 'Nové heslo | PRIVOFIT',
            'page' => 'login',
            'token' => (string) $request->query('token', ''),
        ], 'layouts/brand');
    }

    public function reset(Request $request): never
    {
        try {
            $this->authService()->resetPassword(
                (string) $request->input('token', ''),
                (string) $request->input('password', ''),
                (string) $request->input('password_confirmation', '')
            );
        } catch (ValidationException $e) {
            Session::set('errors', $e->errors);
            $this->flashError($e->getMessage());
            $this->redirect('/obnoveni-hesla?token=' . urlencode((string) $request->input('token', '')));
        } catch (HttpException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('/zapomenute-heslo');
        }
        $this->flashSuccess('Heslo bylo změněno. Přihlaste se novým heslem.');
        $this->redirect('/prihlaseni');
    }

    public function verifyEmail(Request $request): never
    {
        try {
            $this->authService()->verifyEmail((string) $request->query('token', ''));
        } catch (HttpException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('/prihlaseni');
        }
        $this->flashSuccess('E-mail byl ověřen. Nyní se můžete přihlásit.');
        $this->redirect('/prihlaseni');
    }

    public function confirmEmailChange(Request $request): never
    {
        try {
            $this->authService()->confirmEmailChange((string) $request->query('token', ''));
        } catch (HttpException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('/user/profil');
        }
        $this->flashSuccess('E-mailová adresa byla změněna.');
        $this->redirect('/user/profil');
    }
}
