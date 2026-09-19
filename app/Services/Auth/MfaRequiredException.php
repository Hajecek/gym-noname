<?php

declare(strict_types=1);

namespace App\Services\Auth;

final class MfaRequiredException extends \RuntimeException
{
    public function __construct(public readonly array $user)
    {
        parent::__construct('Vyžadováno vícefaktorové ověření.');
    }
}
