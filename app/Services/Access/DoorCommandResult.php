<?php

declare(strict_types=1);

namespace App\Services\Access;

final class DoorCommandResult
{
    public function __construct(
        public readonly bool $accepted,
        public readonly string $status,
        public readonly ?string $lockState = null,
        public readonly ?string $doorState = null,
        public readonly ?string $errorCode = null,
        public readonly string $message = '',
        public readonly bool $physicalOpenConfirmed = false,
    ) {
    }
}
