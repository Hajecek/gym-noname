<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

final class PasswordBreachService
{
    public function isCompromised(string $password): bool
    {
        if (!(bool) env_value('HIBP_ENABLED', true)) {
            return false;
        }
        if ((string) env_value('APP_ENV', 'local') === 'local') {
            return false;
        }

        $sha1 = strtoupper(sha1($password));
        $prefix = substr($sha1, 0, 5);
        $suffix = substr($sha1, 5);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Add-Padding: true\r\nUser-Agent: PRIVOFIT\r\n",
                'timeout' => 4,
            ],
        ]);

        $result = @file_get_contents('https://api.pwnedpasswords.com/range/' . $prefix, false, $context);
        if ($result === false) {
            Logger::warning('HIBP nedostupné, kontrola kompromitovaných hesel přeskočena.');
            return false;
        }

        foreach (preg_split('/\r\n|\r|\n/', $result) ?: [] as $line) {
            $parts = explode(':', $line);
            if (isset($parts[0]) && hash_equals($suffix, strtoupper(trim($parts[0])))) {
                return ((int) ($parts[1] ?? 0)) > 0;
            }
        }
        return false;
    }
}
