<?php

declare(strict_types=1);

namespace App\Support;

use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

final class QrSvg
{
    public static function render(string $payload): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'svgAddXmlHeader' => true,
            'eccLevel' => QRCode::ECC_M,
            'addQuietzone' => true,
            'quietzoneSize' => 4,
            'drawLightModules' => true,
            'connectPaths' => true,
            'svgUseFillAttributes' => true,
        ]);

        return (new QRCode($options))->render($payload);
    }
}
