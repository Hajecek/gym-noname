<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Support\Clock;

final class SettingsService
{
    /** @var array<string, string|null>|null */
    private ?array $cache = null;

    public function __construct(private readonly Database $db)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();
        if (!array_key_exists($key, $all) || $all[$key] === null) {
            return $default;
        }
        $value = $all[$key];
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    public function set(string $key, mixed $value, bool $secret = false): void
    {
        $stored = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        $existing = $this->db->fetch('SELECT setting_key FROM app_settings WHERE setting_key = :k', ['k' => $key]);
        if ($existing) {
            $this->db->update('app_settings', [
                'setting_value' => $stored,
                'is_secret' => $secret ? 1 : 0,
            ], 'setting_key = :k', ['k' => $key]);
        } else {
            $this->db->insert('app_settings', [
                'setting_key' => $key,
                'setting_value' => $stored,
                'is_secret' => $secret ? 1 : 0,
            ]);
        }
        $this->cache = null;
    }

    /** @return array<string, string|null> */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }
        try {
            $rows = $this->db->fetchAll('SELECT setting_key, setting_value FROM app_settings');
        } catch (\Throwable) {
            return [];
        }
        $this->cache = [];
        foreach ($rows as $row) {
            $this->cache[$row['setting_key']] = $row['setting_value'];
        }
        return $this->cache;
    }

    public function int(string $key, int $default): int
    {
        return (int) $this->get($key, $default);
    }

    public function bumpLive(): int
    {
        $next = $this->int('live.revision', 0) + 1;
        $this->set('live.revision', (string) $next);
        try {
            AppPushService::make($this->db)->liveChanged($next);
        } catch (\Throwable) {
        }
        return $next;
    }

    public function liveRevision(): string
    {
        return (string) $this->int('live.revision', 0);
    }

    public function nowNote(): string
    {
        return Clock::utc();
    }
}
