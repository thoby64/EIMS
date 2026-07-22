<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\View\View;

class PublicAssetVerificationController extends Controller
{
    public function __invoke(string $token): View
    {
        $asset = Asset::query()
            ->with(['category.group'])
            ->whereHas('identifiers', fn ($query) => $query->where('type', 'qr_token')->where('value', $token))
            ->firstOrFail();

        return view('assets.verify', compact('asset'));
    }
}
