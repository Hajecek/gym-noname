<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\Access\AccessControlService;
use App\Services\AppPushService;
use App\Services\AuditService;
use App\Services\Auth\AuthService;
use App\Services\Content\ContentService;
use App\Services\MembershipService;
use App\Services\ReservationService;
use App\Support\Clock;

final class AdminController extends Controller
{
    public function dashboard(): never
    {
        $db = $this->app->db();
        $todayStart = Clock::toUtc(Clock::nowLocal()->setTime(0, 0))->format('Y-m-d H:i:s');
        $todayEnd = Clock::toUtc(Clock::nowLocal()->setTime(0, 0)->modify('+1 day'))->format('Y-m-d H:i:s');
        $stats = [
            'active_members' => (int) $db->fetchColumn("SELECT COUNT(*) FROM memberships WHERE status = 'active'"),
            'today_reservations' => (int) $db->fetchColumn("SELECT COUNT(*) FROM reservations WHERE starts_at >= :a AND starts_at < :b AND status IN ('confirmed','pending_payment')", ['a' => $todayStart, 'b' => $todayEnd]),
            'current' => ReservationService::make($db)->occupancyNow(),
            'revenue' => (string) $db->fetchColumn("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'paid' AND paid_at >= :a", ['a' => Clock::nowUtc()->modify('-30 days')->format('Y-m-d H:i:s')]),
            'entries' => (int) $db->fetchColumn("SELECT COUNT(*) FROM access_logs WHERE authorization_result = 'granted' AND created_at >= :a", ['a' => $todayStart]),
            'failed_access' => (int) $db->fetchColumn("SELECT COUNT(*) FROM access_logs WHERE authorization_result = 'denied' AND created_at >= :a", ['a' => $todayStart]),
            'door' => AccessControlService::make($db)->doorStatus(),
            'interest' => 0,
        ];
        try {
            $stats['interest'] = (int) $db->fetchColumn('SELECT COUNT(*) FROM interest_signups');
        } catch (\PDOException) {
        }
        $this->view('admin/dashboard', ['title' => 'Přehled správy', 'stats' => $stats]);
    }

    public function users(Request $request): never
    {
        $q = trim((string) $request->query('q', ''));
        $sql = "SELECT u.*,
                       m.status AS membership_status,
                       p.name AS membership_name,
                       p.type AS membership_type
                FROM users u
                LEFT JOIN memberships m ON m.id = (
                    SELECT m2.id FROM memberships m2
                    WHERE m2.user_id = u.id AND m2.status = 'active'
                      AND (m2.ends_at IS NULL OR m2.ends_at > UTC_TIMESTAMP())
                    ORDER BY m2.ends_at IS NULL DESC, m2.ends_at DESC
                    LIMIT 1
                )
                LEFT JOIN membership_plans p ON p.id = m.plan_id
                WHERE u.deleted_at IS NULL";
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (u.email LIKE :q OR u.username LIKE :q2 OR u.first_name LIKE :q3 OR u.last_name LIKE :q4)';
            $like = '%' . $q . '%';
            $params = ['q' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }
        $sql .= ' ORDER BY u.created_at DESC LIMIT 200';
        $this->view('admin/users', [
            'title' => 'Zákazníci',
            'users' => $this->app->db()->fetchAll($sql, $params),
            'q' => $q,
            'pageScripts' => ['js/customers.js'],
        ]);
    }

    public function userShow(Request $request, array $params): never
    {
        $user = $this->app->db()->fetch('SELECT * FROM users WHERE public_id = :id AND deleted_at IS NULL', ['id' => $params['id']]);
        if (!$user) {
            throw new HttpException(404, 'Uživatel nebyl nalezen.');
        }
        $memberships = new MembershipService($this->app->db());
        $this->view('admin/user-show', [
            'title' => $user['first_name'] . ' ' . $user['last_name'],
            'customer' => $user,
            'activeMembership' => $memberships->activeForUser((int) $user['id']),
            'memberships' => $memberships->history((int) $user['id']),
            'reservations' => ReservationService::make($this->app->db())->forUser((int) $user['id']),
            'payments' => $this->app->db()->fetchAll('SELECT * FROM payments WHERE user_id = :id ORDER BY created_at DESC LIMIT 30', ['id' => (int) $user['id']]),
            'access' => $this->app->db()->fetchAll('SELECT * FROM access_logs WHERE user_id = :id ORDER BY created_at DESC LIMIT 50', ['id' => (int) $user['id']]),
            'plans' => array_values(array_filter(
                $memberships->plans(true),
                static fn (array $plan): bool => ($plan['type'] ?? '') !== 'credit'
            )),
            'pageScripts' => ['js/customers.js'],
        ]);
    }

    public function userUpdate(Request $request, array $params): never
    {
        $actor = $this->requireUser();
        $user = $this->findCustomer($params['id']);
        $status = (string) $request->input('status', $user['status']);
        if (!in_array($status, ['pending', 'active', 'blocked'], true)) {
            throw new HttpException(422, 'Neplatný stav.');
        }
        $this->setCustomerStatus($actor, $user, $status, (string) $request->input('blocked_reason', ''), $request->ip());
        $this->flashSuccess('Stav účtu byl uložen.');
        $this->redirect('/user/sprava/zakaznici/' . $user['public_id']);
    }

    public function userBlock(Request $request, array $params): never
    {
        $actor = $this->requireUser();
        $user = $this->findCustomer($params['id']);
        if ((int) $user['id'] === (int) $actor['id']) {
            throw new HttpException(422, 'Nemůžeš zablokovat vlastní účet.');
        }
        $reason = trim((string) $request->input('blocked_reason', 'Zablokováno administrátorem'));
        $this->setCustomerStatus($actor, $user, 'blocked', $reason !== '' ? $reason : 'Zablokováno administrátorem', $request->ip());
        $this->flashSuccess('Účet byl zablokován.');
        $this->redirectCustomerAction($request, $user);
    }

    public function userUnblock(Request $request, array $params): never
    {
        $actor = $this->requireUser();
        $user = $this->findCustomer($params['id']);
        $this->setCustomerStatus($actor, $user, 'active', '', $request->ip());
        $this->flashSuccess('Účet byl odblokován.');
        $this->redirectCustomerAction($request, $user);
    }

    public function userDelete(Request $request, array $params): never
    {
        $actor = $this->requireUser();
        $user = $this->findCustomer($params['id']);
        if ((int) $user['id'] === (int) $actor['id']) {
            throw new HttpException(422, 'Nemůžeš smazat vlastní účet.');
        }
        if ($user['role'] === 'admin') {
            $admins = (int) $this->app->db()->fetchColumn("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active' AND deleted_at IS NULL");
            if ($admins <= 1) {
                throw new HttpException(422, 'Nelze smazat posledního aktivního administrátora.');
            }
        }
        AuthService::make($this->app->db())->deleteAccount($user);
        (new AuditService($this->app->db()))->log((int) $actor['id'], 'user.delete', 'user', $user['id'], $user['status'], 'deleted', $request->ip());
        $this->flashSuccess('Účet byl smazán.');
        $this->redirect('/user/sprava/zakaznici');
    }

    private function findCustomer(string $publicId): array
    {
        $user = $this->app->db()->fetch('SELECT * FROM users WHERE public_id = :id AND deleted_at IS NULL', ['id' => $publicId]);
        if (!$user) {
            throw new HttpException(404, 'Uživatel nebyl nalezen.');
        }
        return $user;
    }

    private function setCustomerStatus(array $actor, array $user, string $status, string $reason, string $ip): void
    {
        $this->app->db()->update('users', [
            'status' => $status,
            'blocked_at' => $status === 'blocked' ? Clock::utc() : null,
            'blocked_reason' => $status === 'blocked' ? $reason : null,
            'updated_at' => Clock::utc(),
        ], 'id = :id', ['id' => (int) $user['id']]);
        (new AuditService($this->app->db()))->log((int) $actor['id'], 'user.status', 'user', $user['id'], $user['status'], $status, $ip);
        if ($status !== (string) $user['status']) {
            AppPushService::make($this->app->db())->accountStatusChanged($user, $status);
        }
    }

    private function redirectCustomerAction(Request $request, array $user): never
    {
        $back = (string) $request->input('redirect', '');
        if ($back === 'list') {
            $this->redirect('/user/sprava/zakaznici');
        }
        $this->redirect('/user/sprava/zakaznici/' . $user['public_id']);
    }

    public function userRole(Request $request, array $params): never
    {
        $actor = $this->requireUser();
        if ($actor['role'] !== 'admin') {
            throw new HttpException(403, 'Role smí měnit pouze administrátor.');
        }
        $user = $this->findCustomer($params['id']);
        $role = (string) $request->input('role');
        if (!in_array($role, ['user', 'admin'], true)) {
            throw new HttpException(422, 'Neplatná role.');
        }
        if ($user['role'] === 'admin' && $role !== 'admin') {
            $admins = (int) $this->app->db()->fetchColumn("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active' AND deleted_at IS NULL");
            if ($admins <= 1) {
                throw new HttpException(422, 'Nelze odebrat roli poslednímu aktivnímu administrátorovi.');
            }
        }
        $this->app->db()->update('users', ['role' => $role], 'id = :id', ['id' => (int) $user['id']]);
        (new AuditService($this->app->db()))->log((int) $actor['id'], 'user.role', 'user', $user['id'], $user['role'], $role, $request->ip());
        $this->flashSuccess('Role byla změněna.');
        $this->redirect('/user/sprava/zakaznici/' . $user['public_id']);
    }

    public function assignMembership(Request $request, array $params): never
    {
        $actor = $this->requireUser();
        $user = $this->findCustomer($params['id']);
        $planId = (int) $request->input('plan_id');
        if ($planId <= 0) {
            throw new HttpException(422, 'Vyber tarif.');
        }
        (new MembershipService($this->app->db()))->assignPlan((int) $user['id'], $planId, 'active', (int) $actor['id']);
        AppPushService::make($this->app->db())->membershipAssigned((int) $user['id']);
        $this->flashSuccess('Členství bylo přiřazeno.');
        $this->redirect('/user/sprava/zakaznici/' . $user['public_id']);
    }

    public function content(): never
    {
        $content = new ContentService($this->app->db());
        $this->view('admin/content', [
            'title' => 'Obsah webu',
            'hero' => $content->page('home.hero'),
            'terms' => $content->page('obchodni-podminky'),
            'privacy' => $content->page('ochrana-udaju'),
            'faqs' => $content->allFaqs(),
            'contact' => $content->contact(),
        ]);
    }

    public function saveContent(Request $request): never
    {
        $content = new ContentService($this->app->db());
        $content->savePage('home.hero', (string) $request->input('hero_title'), (string) $request->input('hero_body'), $this->app->auth()->id());
        $content->savePage('obchodni-podminky', 'Obchodní podmínky', (string) $request->input('terms'), $this->app->auth()->id());
        $content->savePage('ochrana-udaju', 'Ochrana osobních údajů', (string) $request->input('privacy'), $this->app->auth()->id());
        $this->app->settings()->set('contact.address', (string) $request->input('address'));
        $this->app->settings()->set('contact.email', (string) $request->input('email'));
        $this->app->settings()->set('contact.phone', (string) $request->input('phone'));
        $this->app->settings()->set('contact.hours', (string) $request->input('hours'));
        $this->app->settings()->set('contact.map_embed', (string) $request->input('map_embed'));
        $this->flashSuccess('Obsah byl uložen.');
        bump_live();
        $this->redirect('/user/sprava/obsah');
    }

    public function saveFaq(Request $request): never
    {
        $this->app->db()->insert('faq_items', [
            'question' => (string) $request->input('question'),
            'answer' => (string) $request->input('answer'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_published' => 1,
        ]);
        $this->flashSuccess('FAQ položka byla přidána.');
        bump_live();
        $this->redirect('/user/sprava/obsah');
    }

    public function plans(): never
    {
        $this->view('admin/plans', [
            'title' => 'Členství a ceník',
            'plans' => (new MembershipService($this->app->db()))->plans(false),
        ]);
    }

    public function savePlan(Request $request): never
    {
        $id = (int) $request->input('id', 0);
        $data = [
            'name' => (string) $request->input('name'),
            'description' => (string) $request->input('description'),
            'type' => (string) $request->input('type', 'single'),
            'price' => (string) $request->input('price', '0'),
            'entries' => $request->input('entries') !== '' ? (int) $request->input('entries') : null,
            'duration_days' => $request->input('duration_days') !== '' ? (int) $request->input('duration_days') : null,
            'max_guests' => (int) $request->input('max_guests', 0),
            'is_active' => $request->input('is_active') ? 1 : 0,
            'sort_order' => (int) $request->input('sort_order', 0),
        ];
        if ($id) {
            $this->app->db()->update('membership_plans', $data, 'id = :id', ['id' => $id]);
        } else {
            $this->app->db()->insert('membership_plans', $data + [
                'public_id' => \App\Core\Crypto::uuid(),
                'slug' => strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string) $request->input('name')) ?? 'plan'),
                'currency' => 'CZK',
            ]);
        }
        $this->flashSuccess('Tarif byl uložen.');
        bump_live();
        $this->redirect('/user/sprava/tarify');
    }

    public function access(): never
    {
        $this->view('admin/access', [
            'title' => 'Vstupní systém',
            'status' => AccessControlService::make($this->app->db())->doorStatus(),
            'doors' => $this->app->db()->fetchAll('SELECT * FROM doors'),
            'logs' => $this->app->db()->fetchAll('SELECT l.*, u.username FROM access_logs l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.created_at DESC LIMIT 80'),
        ]);
    }

    public function testOpen(Request $request): never
    {
        $actor = $this->requireUser();
        $password = (string) $request->input('password', '');
        if (!AuthService::make($this->app->db()) || !\App\Core\Crypto::verifyPassword($password, (string) $actor['password_hash'])) {
            $this->flashError('Opětovné ověření selhalo.');
            $this->redirect('/user/sprava/vstup');
        }
        $this->flashSuccess('Testovací režim je aktivní. Ostré otevření se spustí až po konfiguraci Nuki.');
        $this->redirect('/user/sprava/vstup');
    }

    public function settings(): never
    {
        if (!$this->app->auth()->hasRole('admin')) {
            throw new HttpException(403);
        }
        $this->view('admin/settings', [
            'title' => 'Nastavení systému',
            'audit' => $this->app->db()->fetchAll('SELECT a.*, u.username FROM admin_audit_logs a LEFT JOIN users u ON u.id = a.actor_id ORDER BY a.created_at DESC LIMIT 100'),
        ]);
    }

    public function saveSettings(Request $request): never
    {
        if (!$this->app->auth()->hasRole('admin')) {
            throw new HttpException(403);
        }
        foreach (['reservation.slot_minutes', 'reservation.min_minutes', 'reservation.max_minutes', 'reservation.buffer_minutes', 'reservation.cancellation_hours', 'access.early_minutes', 'access.late_minutes'] as $key) {
            $this->app->settings()->set($key, $request->input($key));
        }
        (new AuditService($this->app->db()))->log($this->app->auth()->id(), 'settings.update', 'app_settings', null, null, $request->all(), $request->ip());
        $this->flashSuccess('Nastavení bylo uloženo.');
        bump_live();
        $this->redirect('/user/sprava/nastaveni');
    }

    public function interest(Request $request): never
    {
        $q = trim((string) $request->query('q', ''));
        $sql = 'SELECT * FROM interest_signups';
        $params = [];
        if ($q !== '') {
            $sql .= ' WHERE email LIKE :q';
            $params['q'] = '%' . $q . '%';
        }
        $sql .= ' ORDER BY created_at DESC LIMIT 500';
        $this->view('admin/interest', [
            'title' => 'Předobjednávky',
            'signups' => $this->app->db()->fetchAll($sql, $params),
            'q' => $q,
            'total' => (int) $this->app->db()->fetchColumn('SELECT COUNT(*) FROM interest_signups'),
        ]);
    }

    public function exportInterest(): never
    {
        $rows = $this->app->db()->fetchAll(
            'SELECT email, source, ip_address, created_at FROM interest_signups ORDER BY created_at DESC'
        );
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new HttpException(500, 'Export se nepodařilo připravit.');
        }
        fputcsv($handle, ['email', 'zdroj', 'ip', 'vytvořeno']);
        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['email'],
                $row['source'],
                $row['ip_address'] ?? '',
                $row['created_at'],
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);
        Response::download('privofit-zajem.csv', $csv, 'text/csv; charset=UTF-8');
    }
}
