<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Auth\AuthService;
use App\Services\Auth\ValidationException;
use App\Services\AvatarService;
use App\Services\Billing\PaymentService;
use App\Services\MembershipService;
use App\Services\ReservationService;

final class ProfileController extends Controller
{
    public function index(): never
    {
        $user = $this->requireUser();
        $auth = AuthService::make($this->app->db());
        $this->view('app/profile', [
            'title' => 'Profil',
            'sessions' => $auth->sessions((int) $user['id']),
            'currentSession' => $this->app->auth()->sessionRowId(),
            'membership' => (new MembershipService($this->app->db()))->activeForUser((int) $user['id']),
            'reservations' => ReservationService::make($this->app->db())->forUser((int) $user['id']),
            'payments' => (new PaymentService($this->app->db()))->forUser((int) $user['id']),
            'errors' => Session::pull('errors', []),
        ]);
    }

    public function membership(): never
    {
        $user = $this->requireUser();
        $service = new MembershipService($this->app->db());
        $this->view('app/membership', [
            'title' => 'Členství',
            'plans' => $service->plans(),
            'current' => $service->activeForUser((int) $user['id']),
            'history' => $service->history((int) $user['id']),
        ]);
    }

    public function update(Request $request): never
    {
        $user = $this->requireUser();
        try {
            AuthService::make($this->app->db())->updateProfile($user, $request->all());
            $this->flashSuccess('Profil byl uložen.');
        } catch (ValidationException $e) {
            Session::set('errors', $e->errors);
            $this->flashError($e->getMessage());
        }
        $this->redirect('/app/profil');
    }

    public function password(Request $request): never
    {
        $user = $this->requireUser();
        try {
            AuthService::make($this->app->db())->changePassword(
                $user,
                (string) $request->input('current_password', ''),
                (string) $request->input('password', ''),
                (string) $request->input('password_confirmation', ''),
                $this->app->auth()->sessionRowId()
            );
            $this->flashSuccess('Heslo bylo změněno. Ostatní relace byly odhlášeny.');
        } catch (ValidationException $e) {
            Session::set('errors', $e->errors);
            $this->flashError($e->getMessage());
        }
        $this->redirect('/app/profil');
    }

    public function email(Request $request): never
    {
        $user = $this->requireUser();
        try {
            AuthService::make($this->app->db())->requestEmailChange($user, (string) $request->input('email', ''));
            $this->flashSuccess('Na novou adresu jsme odeslali ověřovací odkaz.');
        } catch (ValidationException $e) {
            $this->flashError($e->getMessage());
        }
        $this->redirect('/app/profil');
    }

    public function avatar(Request $request): never
    {
        $user = $this->requireUser();
        $file = $request->file('avatar');
        if (!$file) {
            $this->flashError('Vyberte obrázek.');
            $this->redirect('/app/profil');
        }
        try {
            (new AvatarService($this->app->db()))->storeFromUpload($user, $file, [
                'x' => $request->input('avatar_x'),
                'y' => $request->input('avatar_y'),
                'size' => $request->input('avatar_size'),
            ]);
            $this->flashSuccess('Profilový obrázek byl uložen.');
        } catch (\RuntimeException $e) {
            $this->flashError($e->getMessage());
        }
        $this->redirect('/app/profil');
    }

    public function deleteAvatar(): never
    {
        $user = $this->requireUser();
        (new AvatarService($this->app->db()))->delete($user);
        $this->flashSuccess('Profilový obrázek byl odstraněn.');
        $this->redirect('/app/profil');
    }

    public function revokeSession(Request $request, array $params): never
    {
        $user = $this->requireUser();
        AuthService::make($this->app->db())->revokeSession((int) $user['id'], (int) $params['id']);
        $this->flashSuccess('Relace byla odhlášena.');
        $this->redirect('/app/profil');
    }

    public function logoutAll(): never
    {
        $user = $this->requireUser();
        AuthService::make($this->app->db())->logoutAll((int) $user['id'], $this->app->auth()->sessionRowId());
        $this->flashSuccess('Všechna ostatní zařízení byla odhlášena.');
        $this->redirect('/app/profil');
    }

    public function notifications(Request $request): never
    {
        $user = $this->requireUser();
        $this->app->db()->query(
            'INSERT INTO notification_preferences (user_id, email_reservations, email_reminders, email_membership, email_marketing, email_security)
             VALUES (:id, :a, :b, :c, :d, 1)
             ON DUPLICATE KEY UPDATE email_reservations = VALUES(email_reservations), email_reminders = VALUES(email_reminders),
                email_membership = VALUES(email_membership), email_marketing = VALUES(email_marketing)',
            [
                'id' => (int) $user['id'],
                'a' => $request->input('email_reservations') ? 1 : 0,
                'b' => $request->input('email_reminders') ? 1 : 0,
                'c' => $request->input('email_membership') ? 1 : 0,
                'd' => $request->input('email_marketing') ? 1 : 0,
            ]
        );
        $this->flashSuccess('Nastavení oznámení bylo uloženo.');
        $this->redirect('/app/profil');
    }

    public function export(): never
    {
        $user = $this->requireUser();
        $payload = [
            'user' => AuthService::make($this->app->db())->publicUser($user),
            'reservations' => ReservationService::make($this->app->db())->forUser((int) $user['id']),
            'memberships' => (new MembershipService($this->app->db()))->history((int) $user['id']),
            'payments' => (new PaymentService($this->app->db()))->forUser((int) $user['id']),
            'exported_at' => gmdate('c'),
        ];
        Response::download('privofit-export.json', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 'application/json');
    }

    public function requestDeletion(): never
    {
        $user = $this->requireUser();
        $this->app->db()->update('users', ['deletion_requested_at' => \App\Support\Clock::utc()], 'id = :id', ['id' => (int) $user['id']]);
        $this->flashSuccess('Žádost o výmaz byla zaznamenána. Ozveme se podle lhůt ochrany osobních údajů.');
        $this->redirect('/app/profil');
    }

    public function showMfa(): never
    {
        $user = $this->requireUser();
        $setup = AuthService::make($this->app->db())->beginTotpSetup($user);
        $this->view('app/mfa', [
            'title' => 'Dvoufaktorové ověření',
            'setup' => $setup,
        ]);
    }

    public function confirmMfa(Request $request): never
    {
        $user = $this->requireUser();
        try {
            $codes = AuthService::make($this->app->db())->confirmTotp($user, (string) $request->input('code', ''));
            Session::set('recovery_codes', $codes);
            $this->flashSuccess('MFA je aktivní. Uložte si záložní kódy.');
            $this->redirect('/app/zabezpeceni/mfa/kody');
        } catch (HttpException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('/app/zabezpeceni/mfa');
        }
    }

    public function recoveryCodes(): never
    {
        $this->requireUser();
        $codes = Session::pull('recovery_codes', []);
        $this->view('app/mfa-codes', [
            'title' => 'Záložní kódy',
            'codes' => is_array($codes) ? $codes : [],
        ]);
    }
}
