<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetScanController extends Controller
{
    public function index(): View
    {
        return view('assets.scan');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:1000']]);
        $code = trim($validated['code']);
        if (filter_var($code, FILTER_VALIDATE_URL)) {
            $code = basename((string) parse_url($code, PHP_URL_PATH));
        }

        $asset = Asset::query()
            ->where('asset_tag', $code)
            ->orWhereHas('identifiers', fn ($query) => $query->where('value', $code))
            ->first();

        if (! $asset) {
            return back()->withInput()->withErrors(['code' => 'No EIMS asset matches the scanned or entered code.']);
        }

        return redirect()->route('assets.show', $asset);
    }
}
