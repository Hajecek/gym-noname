<?php

declare(strict_types=1);

namespace App\Support;

final class Clock
{
    public static function utc(?int $timestamp = null): string
    {
        $dt = new \DateTimeImmutable('@' . ($timestamp ?? time()));
        return $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    public static function nowUtc(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public static function nowLocal(): \DateTimeImmutable
    {
        return self::nowUtc()->setTimezone(new \DateTimeZone(self::displayTimezone()));
    }

    public static function displayTimezone(): string
    {
        return (string) (function_exists('config') ? config('app.display_timezone', 'Europe/Prague') : 'Europe/Prague');
    }

    public static function toUtc(\DateTimeInterface $local): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($local)->setTimezone(new \DateTimeZone('UTC'));
    }

    public static function toLocal(string $utc): \DateTimeImmutable
    {
        $dt = new \DateTimeImmutable($utc, new \DateTimeZone('UTC'));
        return $dt->setTimezone(new \DateTimeZone(self::displayTimezone()));
    }

    public static function format(string $utc, string $pattern = 'd. m. Y H:i'): string
    {
        return self::toLocal($utc)->format($pattern);
    }

    public static function parseLocal(string $dateTime): \DateTimeImmutable
    {
        return new \DateTimeImmutable($dateTime, new \DateTimeZone(self::displayTimezone()));
    }

    public static function iso(?string $utc): ?string
    {
        if ($utc === null || $utc === '') {
            return null;
        }
        return (new \DateTimeImmutable($utc, new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z');
    }
}
