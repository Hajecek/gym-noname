<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    public static function log(string $level, string $message, array $context = []): void
    {
        $line = sprintf(
            "[%s] %s: %s %s\n",
            gmdate('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context !== [] ? json_encode(self::redact($context), JSON_UNESCAPED_UNICODE) : ''
        );
        self::append('app-' . gmdate('Y-m-d') . '.log', $line);
    }

    public static function append(string $filename, string $line): void
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return;
        }
        $path = $dir . '/' . ltrim($filename, '/');
        if (is_file($path) && !is_writable($path)) {
            return;
        }
        @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    private static function redact(array $context): array
    {
        $sensitive = ['password', 'token', 'secret', 'authorization', 'api_token', 'nuki', 'card', 'cvv'];
        foreach ($context as $key => $value) {
            $lower = strtolower((string) $key);
            foreach ($sensitive as $needle) {
                if (str_contains($lower, $needle)) {
                    $context[$key] = '[redacted]';
                }
            }
        }
        return $context;
    }
}
