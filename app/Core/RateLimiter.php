<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\Clock;

final class RateLimiter
{
    public function __construct(private readonly Database $db)
    {
    }

    public function tooMany(string $bucket, string $identifier, int $limit, int $minutes): bool
    {
        $since = Clock::nowUtc()->modify('-' . $minutes . ' minutes')->format('Y-m-d H:i:s');
        $count = (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM rate_limit_events WHERE bucket = :b AND identifier = :i AND created_at >= :since',
            ['b' => $bucket, 'i' => $identifier, 'since' => $since]
        );
        return $count >= $limit;
    }

    public function hit(string $bucket, string $identifier): void
    {
        $this->db->insert('rate_limit_events', [
            'bucket' => $bucket,
            'identifier' => $identifier,
            'created_at' => Clock::utc(),
        ]);
    }

    public function attempt(string $bucket, string $identifier, int $limit, int $minutes): bool
    {
        if ($this->tooMany($bucket, $identifier, $limit, $minutes)) {
            return false;
        }
        $this->hit($bucket, $identifier);
        return true;
    }
}
