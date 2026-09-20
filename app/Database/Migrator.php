<?php

declare(strict_types=1);

namespace App\Database;

use App\Core\Database;
use App\Support\Clock;

final class Migrator
{
    public function __construct(private readonly Database $db, private readonly string $path)
    {
    }

    public function run(): array
    {
        $this->db->query(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                version VARCHAR(64) NOT NULL,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (version)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $applied = [];
        foreach ($this->db->fetchAll('SELECT version FROM schema_migrations') as $row) {
            $applied[$row['version']] = true;
        }
        $files = glob($this->path . '/*.sql') ?: [];
        sort($files);
        $ran = [];
        foreach ($files as $file) {
            $version = basename($file);
            if (isset($applied[$version])) {
                continue;
            }
            $sql = (string) file_get_contents($file);
            foreach ($this->statements($sql) as $statement) {
                $this->db->pdo()->exec($statement);
            }
            $this->db->insert('schema_migrations', [
                'version' => $version,
                'applied_at' => Clock::utc(),
            ]);
            $ran[] = $version;
        }
        return $ran;
    }

    /** @return list<string> */
    private function statements(string $sql): array
    {
        $parts = preg_split('/;\s*(?:--[^\n]*)?\s*$/m', $sql) ?: [];
        $statements = [];
        foreach ($parts as $part) {
            $lines = [];
            foreach (preg_split("/\r\n|\n|\r/", $part) ?: [] as $line) {
                $trimmed = ltrim($line);
                if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                    continue;
                }
                $lines[] = $line;
            }
            $statement = trim(implode("\n", $lines));
            if ($statement !== '') {
                $statements[] = $statement;
            }
        }
        return $statements;
    }
}
