<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Services\Access\AccessControlService;
use App\Services\MembershipService;
use App\Services\ReservationService;
use App\Services\SettingsService;

final class DashboardController extends Controller
{
    public function index(): never
    {
        $user = $this->requireUser();
        $reservations = ReservationService::make($this->app->db());
        $memberships = new MembershipService($this->app->db());
        $access = AccessControlService::make($this->app->db());
        $current = $reservations->current((int) $user['id']);
        $canOpen = $access->canAttempt($user);

        $this->view('user/dashboard', [
            'title' => 'Domů',
            'membership' => $memberships->activeForUser((int) $user['id']),
            'upcoming' => $reservations->upcoming((int) $user['id']),
            'remaining' => $memberships->remainingEntries((int) $user['id']),
            'occupancy' => $reservations->occupancyNow(),
            'historyCount' => count($reservations->forUser((int) $user['id'])),
            'current' => $current,
            'canOpen' => $canOpen['allowed'],
            'plans' => $memberships->plans(),
        ]);
    }

    public function presence(): never
    {
        $this->requireUser();
        $this->jsonOk(['ok' => true]);
    }

    public function verifyNotice(): never
    {
        $this->view('user/verify-notice', ['title' => 'Ověření e-mailu']);
    }

    public function resendVerification(Request $request): never
    {
        $user = $this->requireUser();
        if (!empty($user['email_verified_at'])) {
            $this->redirect('/user');
        }
        \App\Services\Auth\AuthService::make($this->app->db())->sendVerification($user);
        $this->flashSuccess('Ověřovací e-mail byl znovu odeslán.');
        $this->redirect('/user/overeni');
    }

    public function access(): never
    {
        $user = $this->requireUser();
        $access = AccessControlService::make($this->app->db());
        $state = $access->canAttempt($user);
        $visit = $state['reservation'] ?? null;
        if (is_array($visit)) {
            $room = $this->app->db()->fetch('SELECT name FROM rooms WHERE id = :id', ['id' => (int) ($visit['room_id'] ?? 0)]);
            $visit['room_name'] = $room['name'] ?? 'Studio';
        }
        $settings = new SettingsService($this->app->db());
        $this->view('user/access', [
            'title' => 'Vstup',
            'state' => $state,
            'visit' => $visit,
            'upcoming' => ReservationService::make($this->app->db())->upcoming((int) $user['id']),
            'earlyMinutes' => $settings->int('access.early_minutes', 5),
            'lateMinutes' => $settings->int('access.late_minutes', 5),
            'pageScripts' => ['js/access.js'],
            'verified' => !empty($user['email_verified_at']),
        ]);
    }

    public function openDoor(Request $request): never
    {
        $user = $this->requireUser();
        try {
            $result = $this->app->db()->transaction(function () use ($user, $request) {
                return AccessControlService::make($this->app->db())->open($user, $request->ip());
            });
            $this->flashSuccess($result['message']);
        } catch (HttpException $e) {
            $this->flashError($e->getMessage());
        }
        $this->redirect('/user/vstup');
    }
}
