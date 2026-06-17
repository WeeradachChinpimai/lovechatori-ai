<?php

namespace App\Support;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * Renders QR codes as inline SVG data URIs, server-side, so the front-of-store
 * pages don't depend on any external JS CDN at runtime.
 */
class Qr
{
    public static function dataUri(string $data, int $size = 150, array $rgb = [255, 127, 182]): string
    {
        $result = (new Builder(
            writer: new SvgWriter(),
            data: $data,
            size: $size,
            margin: 0,
            foregroundColor: new Color($rgb[0], $rgb[1], $rgb[2]),
        ))->build();

        return $result->getDataUri();
    }
}
