<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Services\AssetLabelService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AssetLabelController extends Controller
{
    public function show(Asset $asset): View
    {
        $asset->load(['category.group']);

        return view('assets.label', compact('asset'));
    }

    public function qr(Asset $asset, AssetLabelService $labels): Response
    {
        return response($labels->qrSvg($asset), 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="'.$asset->asset_tag.'-qr.svg"',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function barcode(Asset $asset, AssetLabelService $labels): Response
    {
        return response($labels->barcodeSvg($asset), 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="'.$asset->asset_tag.'-barcode.svg"',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
