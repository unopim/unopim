<?php

namespace Webkul\Publication\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Renders the QR carrier for a target string. Shared by the public live carrier and admin issuance so both print the same code for the same target.
 */
class CarrierSvg
{
    public function render(string $target): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(256), new SvgImageBackEnd));

        return $writer->writeString($target);
    }
}
