<?php

namespace App\Services;

use App\Models\Asset;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Picqer\Barcode\BarcodeGeneratorSVG;
use RuntimeException;

class AssetLabelService
{
    public function qrSvg(Asset $asset, int $size = 260): string
    {
        $token = $asset->identifiers()->where('type', 'qr_token')->value('value');
        if (! $token) {
            throw new RuntimeException('The asset does not have a verification token.');
        }

        $renderer = new ImageRenderer(new RendererStyle($size, 12), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString(route('assets.verify', ['token' => $token]));
    }

    public function barcodeSvg(Asset $asset): string
    {
        return (new BarcodeGeneratorSVG)->getBarcode(
            $asset->asset_tag,
            BarcodeGeneratorSVG::TYPE_CODE_128,
            1.65,
            56,
        );
    }
}
