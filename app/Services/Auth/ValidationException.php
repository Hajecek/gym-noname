<?php

declare(strict_types=1);

namespace App\Services\Auth;

final class ValidationException extends \RuntimeException
{
    /** @param array<string, list<string>> $errors */
    public function __construct(public readonly array $errors, string $message)
    {
        parent::__construct($message);
    }
}
