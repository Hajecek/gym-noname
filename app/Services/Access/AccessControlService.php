<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\RateLimiter;
use App\Services\MailService;
use App\Services\SettingsService;
use App\Support\Clock;

final class AccessControlService
{
    public function __construct(
        private readonly Database $db,
        private readonly SettingsService $settings,
        private readonly RateLimiter $limiter,
        private readonly MailService $mail,
        private readonly DoorProviderInterface $provider,
    ) {
    }

    public static function make(Database $db): self
    {
        $settings = new SettingsService($db);
        $configured = (string) env_value('DOOR_PROVIDER', 'mock');
        $provider = $configured === 'nuki' && (string) env_value('NUKI_API_TOKEN', '') !== ''
            ? new NukiDoorProvider(
                (string) env_value('NUKI_API_BASE', 'https://api.nuki.io'),
                (string) env_value('NUKI_API_TOKEN', ''),
                (int) env_value('NUKI_TIMEOUT', 15),
                (string) env_value('NUKI_ACTION', 'unlatch'),
            )
            : new MockDoorProvider();

        return new self($db, $settings, new RateLimiter($db), new MailService($db), $provider);
    }

    public function canAttempt(array $user): array
    {
        $reasons = $this->authorizationReasons($user);
        $reservation = $this->currentEligibleReservation($user);
        return [
            'allowed' => $reasons === [] && $reservation !== null,
            'reasons' => $reasons,
            'reservation' => $reservation,
            'provider' => $this->provider->name(),
            'test_mode' => $this->provider->name() === 'mock',
        ];
    }

    public function open(array $user, string $ip): array
    {
        $reasons = $this->authorizationReasons($user);
        if ($reasons !== []) {
            $this->log($user, null, null, 'denied', 'not_sent', $reasons[0], $ip);
            throw new HttpException(403, $this->messageFor($reasons[0]));
        }

        $reservation = $this->currentEligibleReservation($user);
        if (!$reservation) {
            $this->log($user, null, null, 'denied', 'not_sent', 'no_reservation', $ip);
            throw new HttpException(403, $this->messageFor('no_reservation'));
        }

        $door = $this->db->fetch(
            'SELECT * FROM doors WHERE room_id = :rid AND is_active = 1 LIMIT 1',
            ['rid' => (int) $reservation['room_id']]
        );
        if (!$door) {
            $this->log($user, $reservation, null, 'denied', 'not_sent', 'no_door', $ip);
            throw new HttpException(403, 'Vstupní systém není nakonfigurován.');
        }

        $permission = $this->db->fetch(
            'SELECT * FROM access_permissions
             WHERE user_id = :uid AND door_id = :did AND reservation_id = :rid
               AND valid_from <= :now AND valid_until >= :now2',
            [
                'uid' => (int) $user['id'],
                'did' => (int) $door['id'],
                'rid' => (int) $reservation['id'],
                'now' => Clock::utc(),
                'now2' => Clock::utc(),
            ]
        );
        if (!$permission) {
            $this->log($user, $reservation, $door, 'denied', 'not_sent', 'no_permission', $ip);
            throw new HttpException(403, 'Nemáte oprávnění k těmto dveřím.');
        }

        $limit = (array) config('security.rate_limits.access_open', ['limit' => 8, 'minutes' => 5]);
        if (!$this->limiter->attempt('access-open', (string) $user['id'] . ':' . $door['id'], (int) $limit['limit'], (int) $limit['minutes'])) {
            $this->log($user, $reservation, $door, 'denied', 'not_sent', 'rate_limited', $ip);
            throw new HttpException(429, 'Příliš mnoho pokusů o otevření. Počkejte chvíli.');
        }

        $lockUntil = Clock::nowUtc()->modify('+8 seconds')->format('Y-m-d H:i:s');
        $locked = $this->db->fetch('SELECT * FROM door_command_locks WHERE door_id = :id FOR UPDATE', ['id' => (int) $door['id']]);
        if ($locked && $locked['locked_until'] > Clock::utc()) {
            $this->log($user, $reservation, $door, 'denied', 'conflict', 'door_busy', $ip);
            throw new HttpException(409, 'Dveře právě zpracovávají jiný příkaz.');
        }
        if ($locked) {
            $this->db->update('door_command_locks', ['locked_until' => $lockUntil], 'door_id = :id', ['id' => (int) $door['id']]);
        } else {
            $this->db->insert('door_command_locks', ['door_id' => (int) $door['id'], 'locked_until' => $lockUntil]);
        }

        $result = $this->provider->open($door);
        $this->db->update('doors', [
            'last_known_state' => $result->lockState,
            'last_known_door_state' => $result->doorState,
            'last_checked_at' => Clock::utc(),
        ], 'id = :id', ['id' => (int) $door['id']]);

        $this->log(
            $user,
            $reservation,
            $door,
            $result->accepted ? 'granted' : 'denied',
            $result->status,
            $result->errorCode,
            $ip,
            $result->lockState,
            $result->doorState
        );

        if (!$result->accepted) {
            $this->notifyAdminsIfNeeded($door, $result);
            throw new HttpException(502, $result->message !== '' ? $result->message : 'Příkaz k otevření se nepodařilo odeslat.');
        }

        return [
            'accepted' => true,
            'physical_open_confirmed' => $result->physicalOpenConfirmed,
            'test_mode' => $this->provider->name() === 'mock',
            'lock_state' => $result->lockState,
            'door_state' => $result->doorState,
            'message' => $result->physicalOpenConfirmed
                ? 'Dveře byly odemčeny.'
                : ($this->provider->name() === 'mock'
                    ? 'Testovací režim: příkaz byl autorizován, fyzické dveře nebyly ovládány.'
                    : 'Příkaz byl odeslán zámku. Fyzické otevření se ověřuje ze stavu zařízení.'),
        ];
    }

    public function doorStatus(): array
    {
        $door = $this->db->fetch('SELECT * FROM doors WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
        if (!$door) {
            return ['configured' => false, 'provider' => $this->provider->name(), 'test_mode' => $this->provider->name() === 'mock'];
        }
        $status = $this->provider->status($door);
        $this->db->update('doors', [
            'last_known_state' => $status->lockState,
            'last_known_door_state' => $status->doorState,
            'last_battery_percent' => $status->batteryPercent,
            'battery_critical' => $status->batteryCritical ? 1 : 0,
            'last_online_at' => $status->online ? Clock::utc() : $door['last_online_at'],
            'last_checked_at' => Clock::utc(),
        ], 'id = :id', ['id' => (int) $door['id']]);

        if (!$status->online || $status->batteryCritical) {
            $this->notifyAdminsIfNeeded($door, null, $status);
        }

        return [
            'configured' => true,
            'provider' => $status->provider,
            'test_mode' => $status->provider === 'mock',
            'online' => $status->online,
            'lock_state' => $status->lockState,
            'door_state' => $status->doorState,
            'battery_percent' => $status->batteryPercent,
            'battery_critical' => $status->batteryCritical,
            'mode' => $status->mode,
        ];
    }

    /** @return list<string> */
    private function authorizationReasons(array $user): array
    {
        $reasons = [];
        if (empty($user['email_verified_at'])) {
            $reasons[] = 'unverified';
        }
        if ($user['status'] !== 'active') {
            $reasons[] = 'inactive';
        }
        return $reasons;
    }

    private function currentEligibleReservation(array $user): ?array
    {
        $early = $this->settings->int('access.early_minutes', 5);
        $late = $this->settings->int('access.late_minutes', 5);
        return $this->db->fetch(
            "SELECT r.* FROM reservations r
             WHERE r.user_id = :uid AND r.status = 'confirmed'
               AND DATE_SUB(r.starts_at, INTERVAL {$early} MINUTE) <= :now
               AND DATE_ADD(r.ends_at, INTERVAL {$late} MINUTE) >= :now2
             ORDER BY r.starts_at ASC LIMIT 1",
            ['uid' => (int) $user['id'], 'now' => Clock::utc(), 'now2' => Clock::utc()]
        );
    }

    private function log(array $user, ?array $reservation, ?array $door, string $auth, string $command, ?string $reason, string $ip, ?string $lock = null, ?string $doorState = null): void
    {
        $this->db->insert('access_logs', [
            'user_id' => (int) $user['id'],
            'reservation_id' => $reservation['id'] ?? null,
            'door_id' => $door['id'] ?? null,
            'authorization_result' => $auth,
            'command_result' => $command,
            'lock_state' => $lock,
            'door_state' => $doorState,
            'denial_reason' => $reason,
            'error_code' => $reason,
            'ip_address' => $ip,
            'created_at' => Clock::utc(),
        ]);
    }

    private function messageFor(string $reason): string
    {
        return match ($reason) {
            'unverified' => 'Nejprve ověřte e-mailovou adresu.',
            'inactive' => 'Účet není aktivní.',
            'no_reservation' => 'Nemáte právě platnou rezervaci.',
            'no_permission' => 'Nemáte oprávnění k těmto dveřím.',
            'rate_limited' => 'Příliš mnoho pokusů.',
            default => 'Vstup nebyl povolen.',
        };
    }

    private function notifyAdminsIfNeeded(array $door, ?DoorCommandResult $result = null, ?DoorStatus $status = null): void
    {
        $admins = $this->db->fetchAll("SELECT email, first_name FROM users WHERE role = 'admin' AND status = 'active'");
        $body = 'Upozornění vstupního systému PRIVOFIT.';
        if ($status && !$status->online) {
            $body = 'Zámek ' . $door['name'] . ' je offline.';
        } elseif ($status && $status->batteryCritical) {
            $body = 'Kritický stav baterie zámku ' . $door['name'] . '.';
        } elseif ($result && !$result->accepted) {
            $body = 'Opakovaný neúspěšný pokus o otevření dveří ' . $door['name'] . '.';
        }
        foreach ($admins as $admin) {
            $this->mail->queue('security-alert', $admin['email'], [
                'subject' => 'PRIVOFIT – stav vstupního systému',
                'first_name' => $admin['first_name'],
                'body' => $body,
            ]);
        }
    }
}
