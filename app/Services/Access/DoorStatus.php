<?php

declare(strict_types=1);

namespace App\Services\Access;

final class DoorStatus
{
    public function __construct(
        public readonly bool $online,
        public readonly ?string $lockState,
        public readonly ?string $doorState,
        public readonly ?int $batteryPercent,
        public readonly bool $batteryCritical,
        public readonly string $provider,
        public readonly string $mode,
        public readonly ?string $rawError = null,
    ) {
    }
}
