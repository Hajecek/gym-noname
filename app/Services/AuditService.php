<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\Database;
use App\Support\Clock;

final class AuditService
{
    public function __construct(private readonly Database $db)
    {
    }

    public function log(?int $actorId, string $action, string $entityType, string|int|null $entityId, mixed $before = null, mixed $after = null, ?string $ip = null): void
    {
        $this->db->insert('admin_audit_logs', [
            'actor_id' => $actorId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId !== null ? (string) $entityId : null,
            'before_json' => $before !== null ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            'after_json' => $after !== null ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => $ip,
            'created_at' => Clock::utc(),
        ]);
    }
}
