<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $showClassifications = auth()->user()->roles()
            ->whereIn('slug', ['system-administrator', 'procurement-officer', 'maintenance-officer', 'maintenance-review-officer'])
            ->exists();

        return view('dashboard', [
            'metrics' => [
                'assets' => DB::table('assets')->whereNull('deleted_at')->count(),
                'groups' => DB::table('asset_groups')->where('is_active', true)->count(),
                'categories' => DB::table('asset_categories')->where('is_active', true)->count(),
                'locations' => DB::table('locations')->where('is_active', true)->count(),
            ],
            'showClassifications' => $showClassifications,
            'groups' => $showClassifications ? DB::table('asset_groups')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['name', 'code', 'icon', 'color']) : collect(),
            'categories' => $showClassifications ? DB::table('asset_categories')
                ->join('asset_groups', 'asset_groups.id', '=', 'asset_categories.asset_group_id')
                ->where('asset_categories.is_active', true)
                ->orderBy('asset_groups.sort_order')
                ->orderBy('asset_categories.sort_order')
                ->get([
                    'asset_categories.name',
                    'asset_categories.code',
                    'asset_categories.icon',
                    'asset_categories.tracking_mode',
                    'asset_groups.name as group_name',
                    'asset_groups.icon as group_icon',
                    'asset_groups.color as group_color',
                ]) : collect(),
        ]);
    }
}
