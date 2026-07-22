<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDisposal;
use App\Models\AssetInspection;
use App\Models\Department;
use App\Models\Location;
use App\Models\MaintenanceCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index', ['stats' => ['assets' => Asset::count(), 'available' => Asset::where('lifecycle_status', 'in_stock')->count(), 'assigned' => Asset::where('lifecycle_status', 'assigned')->count(), 'maintenance' => MaintenanceCase::whereNotIn('status', ['closed'])->count(), 'inspections' => AssetInspection::where('status', 'scheduled')->count(), 'disposals' => AssetDisposal::whereNotIn('status', ['completed', 'review_rejected', 'approval_rejected'])->count()]]);
    }

    public function assets(Request $r): View
    {
        return view('reports.assets', ['assets' => $this->assetQuery($r)->paginate(25)->withQueryString(), 'categories' => AssetCategory::orderBy('name')->get(), 'departments' => Department::orderBy('name')->get(), 'locations' => Location::orderBy('name')->get()]);
    }

    public function exportAssets(Request $r): StreamedResponse
    {
        $rows = $this->assetQuery($r)->cursor();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Asset Tag', 'Name', 'Category', 'Status', 'Condition', 'Custodian', 'Department', 'Location', 'Serial Number', 'Acquired On']);
            foreach ($rows as $a) {
                fputcsv($out, [$this->safe($a->asset_tag), $this->safe($a->name), $this->safe($a->category->name), $a->lifecycle_status, $a->condition, $this->safe($a->custodian?->name), $this->safe($a->custodianDepartment?->name), $this->safe($a->location?->name), $this->safe($a->serial_number), $a->acquired_on?->toDateString()]);
            }fclose($out);
        }, 'eims-asset-register-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function assetQuery(Request $r): Builder
    {
        return Asset::query()->with(['category', 'custodian', 'custodianDepartment', 'location'])
            ->when($r->filled('category'), fn ($q) => $q->where('asset_category_id', $r->integer('category')))
            ->when($r->filled('department'), fn ($q) => $q->where(fn ($scope) => $scope
                ->where('custodian_department_id', $r->integer('department'))
                ->orWhereHas('custodian', fn ($user) => $user->where('department_id', $r->integer('department')))))
            ->when($r->filled('location'), fn ($q) => $q->where('location_id', $r->integer('location')))
            ->when($r->filled('status'), fn ($q) => $q->where('lifecycle_status', $r->string('status')))
            ->when($r->filled('condition'), fn ($q) => $q->where('condition', $r->string('condition')))
            ->when($r->filled('q'), fn ($q) => $q->where(fn ($search) => $search
                ->where('asset_tag', 'like', '%'.$r->string('q').'%')
                ->orWhere('name', 'like', '%'.$r->string('q').'%')
                ->orWhere('serial_number', 'like', '%'.$r->string('q').'%')))
            ->orderBy('asset_tag');
    }

    private function safe(?string $value): string
    {
        $value = $value ?? '';

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
