<?php

declare(strict_types=1);

namespace App\Services\Billing;

/**
 * Navýší cenu o poplatek ověřený na účtu Stripe: 3,15 % + 6,50 Kč.
 * Po stržení poplatku zůstane celá cena vstupu.
 *
 * @phpstan-type Quote array{net:string,fee:string,charge:string,netMinor:int,feeMinor:int,chargeMinor:int}
 */
final class StripeFee
{
    public static function basisPoints(): int
    {
        $percent = (float) env_value('STRIPE_FEE_PERCENT', '3.15');
        $bps = (int) round($percent * 100);
        return max(0, min(9999, $bps));
    }

    public static function fixedMinor(): int
    {
        $fixed = (float) env_value('STRIPE_FEE_FIXED_CZK', '6.50');
        return max(0, (int) round($fixed * 100));
    }

    /** @return array{basisPoints:int,fixedMinor:int} */
    public static function rates(): array
    {
        return [
            'basisPoints' => self::basisPoints(),
            'fixedMinor' => self::fixedMinor(),
        ];
    }

    /**
     * @return Quote
     */
    public static function cover(string|float|int $netAmount): array
    {
        $netMinor = max(0, (int) round(((float) $netAmount) * 100));
        $bps = self::basisPoints();
        $fixed = self::fixedMinor();
        if ($netMinor === 0 || ($bps === 0 && $fixed === 0)) {
            return self::pack($netMinor, 0);
        }

        $charge = $netMinor + $fixed;
        if ($bps > 0) {
            $denom = 10000 - $bps;
            $numer = ($netMinor + $fixed) * 10000;
            $charge = intdiv($numer + $denom - 1, $denom);
        }
        while ($charge > $netMinor && self::netOf($charge - 1, $bps, $fixed) >= $netMinor) {
            $charge--;
        }
        while (self::netOf($charge, $bps, $fixed) < $netMinor) {
            $charge++;
        }

        return self::pack($netMinor, $charge - $netMinor);
    }

    /** @return Quote */
    private static function pack(int $netMinor, int $feeMinor): array
    {
        return [
            'net' => self::money($netMinor),
            'fee' => self::money($feeMinor),
            'charge' => self::money($netMinor + $feeMinor),
            'netMinor' => $netMinor,
            'feeMinor' => $feeMinor,
            'chargeMinor' => $netMinor + $feeMinor,
        ];
    }

    private static function money(int $minor): string
    {
        return number_format($minor / 100, 2, '.', '');
    }

    private static function netOf(int $amountMinor, int $bps, int $fixedMinor): int
    {
        return $amountMinor - self::processorFee($amountMinor, $bps, $fixedMinor);
    }

    private static function processorFee(int $amountMinor, int $bps, int $fixedMinor): int
    {
        $product = $amountMinor * $bps;
        $percent = intdiv($product, 10000);
        if ($product % 10000 >= 5000) {
            $percent++;
        }
        return $percent + $fixedMinor;
    }
}
