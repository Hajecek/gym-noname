<?php

declare(strict_types=1);

namespace App\Services\Auth;

final class MfaSetupRequiredException extends \RuntimeException
{
    public function __construct(public readonly array $user)
    {
        parent::__construct('Pro tento účet je nutné nastavit MFA.');
    }
}
