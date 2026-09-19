<?php

declare(strict_types=1);

namespace App\Services;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

final class PhoneService
{
    public function normalize(?string $phone, string $region = 'CZ'): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }
        $util = PhoneNumberUtil::getInstance();
        try {
            $number = $util->parse($phone, $region);
            if (!$util->isValidNumber($number)) {
                return null;
            }
            return $util->format($number, PhoneNumberFormat::E164);
        } catch (NumberParseException) {
            return null;
        }
    }
}
