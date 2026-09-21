<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\Clock;

final class Auth
{
    private ?array $user = null;
    private ?int $sessionRowId = null;

    public function __construct(private readonly Database $db)
    {
    }

    public function hydrate(Request $request): void
    {
        if ($request->bearerToken()) {
            $this->hydrateBearer($request->bearerToken());
            return;
        }
        if ($request->isApi()) {
            return;
        }

        Session::start();
        $userId = Session::get('user_id');
        $sessionId = Session::get('auth_session_id');
        if (!is_numeric($userId) || !is_numeric($sessionId)) {
            return;
        }

        $row = $this->db->fetch(
            'SELECT s.id AS session_row_id, s.last_activity_at, s.expires_at, s.revoked_at, u.*
             FROM user_sessions s
             INNER JOIN users u ON u.id = s.user_id
             WHERE s.id = :sid AND s.user_id = :uid AND u.deleted_at IS NULL',
            ['sid' => (int) $sessionId, 'uid' => (int) $userId]
        );
        if (!$row || $row['revoked_at'] !== null || (string) $row['expires_at'] <= Clock::utc() || $this->idleExpired($row)) {
            if ($row && $row['revoked_at'] === null) {
                $this->db->update('user_sessions', ['revoked_at' => Clock::utc()], 'id = :id', ['id' => (int) $row['session_row_id']]);
            }
            $this->dropWebSession(true);
            return;
        }

        if (!$this->isPresenceCheck($request)) {
            $this->db->query(
                'UPDATE user_sessions SET last_activity_at = :now WHERE id = :id',
                ['now' => Clock::utc(), 'id' => (int) $row['session_row_id']]
            );
        }
        $this->sessionRowId = (int) $row['session_row_id'];
        unset($row['session_row_id'], $row['last_activity_at'], $row['expires_at'], $row['revoked_at']);
        $this->user = $row;
    }

    private function idleExpired(array $row): bool
    {
        $minutes = max(5, (int) config('security.session.idle_minutes', 1440));
        $last = new \DateTimeImmutable((string) $row['last_activity_at'], new \DateTimeZone('UTC'));
        return $last->modify('+' . $minutes . ' minutes') <= Clock::nowUtc();
    }

    private function isPresenceCheck(Request $request): bool
    {
        return $request->path() === '/user/pritomnost';
    }

    private function dropWebSession(bool $security): void
    {
        Session::forget('user_id');
        Session::forget('auth_session_id');
        if ($security) {
            Session::set('logged_out_reason', 'idle');
        }
    }

    private function hydrateBearer(string $token): void
    {
        $payload = Crypto::verifyPayload($token);
        if (!$payload || ($payload['typ'] ?? '') !== 'access' || ($payload['exp'] ?? 0) < time()) {
            return;
        }
        $user = $this->db->fetch(
            'SELECT * FROM users WHERE public_id = :pid AND deleted_at IS NULL',
            ['pid' => (string) $payload['sub']]
        );
        if ($user) {
            $this->user = $user;
        }
    }

    public function user(): ?array
    {
        return $this->user;
    }

    public function id(): ?int
    {
        return $this->user ? (int) $this->user['id'] : null;
    }

    public function check(): bool
    {
        return $this->user !== null;
    }

    public function role(): ?string
    {
        return $this->user['role'] ?? null;
    }

    public function hasRole(string ...$roles): bool
    {
        return $this->user !== null && in_array($this->user['role'], $roles, true);
    }

    public function can(string $permission): bool
    {
        if (!$this->user) {
            return false;
        }
        $row = $this->db->fetch(
            'SELECT p.slug
             FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             INNER JOIN roles r ON r.id = rp.role_id
             WHERE r.slug = :role AND p.slug = :perm',
            ['role' => $this->user['role'], 'perm' => $permission]
        );
        return $row !== null;
    }

    public function sessionRowId(): ?int
    {
        return $this->sessionRowId;
    }

    public function setUser(array $user, ?int $sessionRowId = null): void
    {
        $this->user = $user;
        $this->sessionRowId = $sessionRowId;
    }
}
