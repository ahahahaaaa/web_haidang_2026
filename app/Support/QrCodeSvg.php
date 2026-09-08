<?php

namespace App\Support;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeSvg
{
    public static function render(string $content, int $size = 192): string
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle(
                    max(120, $size),
                    2,
                    null,
                    null,
                    Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(15, 23, 42)),
                ),
                new SvgImageBackEnd,
            ),
        ))->writeString($content);

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }
}
