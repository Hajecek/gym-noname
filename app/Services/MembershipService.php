<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\Database;
use App\Core\HttpException;
use App\Support\Clock;

final class MembershipService
{
    public function __construct(private readonly Database $db)
    {
    }

    public function plans(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM membership_plans';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, price ASC';
        return $this->db->fetchAll($sql);
    }

    public function activeForUser(int $userId): ?array
    {
        $this->expireOverdue($userId);
        return $this->db->fetch(
            "SELECT m.*, p.name AS plan_name, p.type AS plan_type, p.max_guests
             FROM memberships m
             INNER JOIN membership_plans p ON p.id = m.plan_id
             WHERE m.user_id = :uid AND m.status = 'active'
               AND (m.ends_at IS NULL OR m.ends_at > :now)
             ORDER BY m.ends_at IS NULL, m.ends_at DESC
             LIMIT 1",
            ['uid' => $userId, 'now' => Clock::utc()]
        );
    }

    public function remainingEntries(int $userId): ?int
    {
        $membership = $this->activeForUser($userId);
        if (!$membership) {
            return 0;
        }
        if ($membership['entries_remaining'] === null) {
            return null;
        }
        return (int) $membership['entries_remaining'];
    }

    public function assignPlan(int $userId, int $planId, string $status = 'active', ?int $actorId = null): array
    {
        $plan = $this->db->fetch('SELECT * FROM membership_plans WHERE id = :id', ['id' => $planId]);
        if (!$plan) {
            throw new HttpException(404, 'Tarif nebyl nalezen.');
        }
        $starts = Clock::nowUtc();
        $ends = $plan['duration_days'] ? $starts->modify('+' . (int) $plan['duration_days'] . ' days') : null;
        $id = (int) $this->db->insert('memberships', [
            'public_id' => Crypto::uuid(),
            'user_id' => $userId,
            'plan_id' => $planId,
            'status' => $status,
            'entries_remaining' => $plan['entries'],
            'credit_remaining' => $plan['type'] === 'credit' ? $plan['price'] : null,
            'starts_at' => $starts->format('Y-m-d H:i:s'),
            'ends_at' => $ends?->format('Y-m-d H:i:s'),
            'created_at' => Clock::utc(),
            'updated_at' => Clock::utc(),
        ]);
        $this->db->insert('membership_transactions', [
            'membership_id' => $id,
            'type' => 'purchase',
            'entries_delta' => (int) ($plan['entries'] ?? 0),
            'note' => 'Aktivace tarifu ' . $plan['name'],
            'created_by' => $actorId,
            'created_at' => Clock::utc(),
        ]);
        return $this->db->fetch('SELECT * FROM memberships WHERE id = :id', ['id' => $id]) ?? [];
    }

    public function consumeEntry(int $membershipId, ?int $actorId = null): void
    {
        $membership = $this->db->fetch('SELECT * FROM memberships WHERE id = :id FOR UPDATE', ['id' => $membershipId]);
        if (!$membership || $membership['status'] !== 'active') {
            throw new HttpException(400, 'Členství není aktivní.');
        }
        if ($membership['entries_remaining'] !== null) {
            if ((int) $membership['entries_remaining'] < 1) {
                throw new HttpException(400, 'Nemáte zbývající vstupy.');
            }
            $this->db->update('memberships', [
                'entries_remaining' => (int) $membership['entries_remaining'] - 1,
            ], 'id = :id', ['id' => $membershipId]);
            $this->db->insert('membership_transactions', [
                'membership_id' => $membershipId,
                'type' => 'entry_use',
                'entries_delta' => -1,
                'created_by' => $actorId,
                'created_at' => Clock::utc(),
            ]);
        }
    }

    public function expireOverdue(?int $userId = null): array
    {
        $sql = "SELECT DISTINCT user_id FROM memberships WHERE status = 'active' AND ends_at IS NOT NULL AND ends_at < :now";
        $params = ['now' => Clock::utc()];
        if ($userId) {
            $sql .= ' AND user_id = :uid';
            $params['uid'] = $userId;
        }
        $rows = $this->db->fetchAll($sql, $params);
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row['user_id'];
        }
        if ($ids === []) {
            return [];
        }
        $update = "UPDATE memberships SET status = 'expired' WHERE status = 'active' AND ends_at IS NOT NULL AND ends_at < :now";
        $updateParams = ['now' => Clock::utc()];
        if ($userId) {
            $update .= ' AND user_id = :uid';
            $updateParams['uid'] = $userId;
        }
        $this->db->query($update, $updateParams);
        return $ids;
    }

    public function history(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT m.*, p.name AS plan_name, p.type AS plan_type
             FROM memberships m
             INNER JOIN membership_plans p ON p.id = m.plan_id
             WHERE m.user_id = :uid
             ORDER BY m.created_at DESC',
            ['uid' => $userId]
        );
    }
}
