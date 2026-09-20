<?php

declare(strict_types=1);

namespace App\Support;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

final class QrSvg
{
    public static function render(string $payload, bool $xmlHeader = true): string
    {
        $options = new QROptions();
        $options->outputBase64 = false;
        $options->svgAddXmlHeader = $xmlHeader;
        $options->eccLevel = QRCode::ECC_M;
        $options->addQuietzone = true;
        $options->quietzoneSize = 4;

        return self::toSvgMarkup((string) (new QRCode($options))->render($payload));
    }

    public static function inline(string $payload): string
    {
        return self::render($payload, false);
    }

    public static function dataUri(string $payload): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode(self::inline($payload));
    }

    private static function toSvgMarkup(string $raw): string
    {
        $raw = trim($raw);
        if (str_contains($raw, '<svg')) {
            return $raw;
        }
        if (preg_match('#^data:image/svg\+xml;base64,([A-Za-z0-9+/]+=*)$#', $raw, $match) === 1) {
            $decoded = base64_decode($match[1], true);
            if (is_string($decoded) && str_contains($decoded, '<svg')) {
                return $decoded;
            }
        }
        throw new \RuntimeException('QR kód se nepodařilo vykreslit.');
    }
}
