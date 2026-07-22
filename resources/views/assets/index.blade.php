@extends('layouts.app')

@section('title', 'Asset Registry')
@section('heading', 'Asset registry')

@section('content')
<div class="pt-4">
    @if (session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="eims-card overflow-hidden">
        <div class="flex flex-col gap-4 border-b border-slate-100 p-5 lg:flex-row lg:items-center">
            <div><h2 class="font-extrabold text-eims-ink">Registered property</h2><p class="mt-1 text-xs text-slate-400">Search by EIMS tag, serial number, barcode or property name.</p></div>
            <form method="GET" class="eims-filter-panel ml-auto flex flex-col gap-3 sm:flex-row sm:items-end">
                <label class="eims-filter-label">Search assets<input name="search" value="{{ $search }}" class="eims-filter-field min-w-56" placeholder="Tag, serial, barcode or name"></label>
                <label class="eims-filter-label">Category<select name="category" class="eims-filter-field min-w-48">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)<option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>@endforeach
                </select></label>
                <button class="eims-filter-button">Apply filters</button>
                @if(request()->hasAny(['search','category']))<a href="{{ route('assets.index') }}" class="eims-filter-reset">Clear</a>@endif
            </form>
            <a href="{{ route('assets.create') }}" class="rounded-xl bg-gradient-to-r from-indigo-600 to-purple-700 px-5 py-2.5 text-center text-sm font-extrabold text-white shadow-lg shadow-indigo-500/20">Register asset</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-400"><tr><th class="px-6 py-4">Asset</th><th class="px-6 py-4">Category</th><th class="px-6 py-4">Location</th><th class="px-6 py-4">Condition</th><th class="px-6 py-4">Status</th><th class="px-6 py-4"></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($assets as $asset)
                        <tr class="transition hover:bg-indigo-50/30">
                            <td class="px-6 py-4"><p class="font-extrabold text-eims-ink">{{ $asset->name }}</p><p class="mt-1 font-mono text-xs text-indigo-500">{{ $asset->asset_tag }}</p></td>
                            <td class="px-6 py-4"><p class="font-semibold text-slate-600">{{ $asset->category->name }}</p><p class="text-xs text-slate-400">{{ $asset->category->group->name }}</p></td>
                            <td class="px-6 py-4">{{ $asset->location?->name ?? 'Not assigned' }}</td>
                            <td class="px-6 py-4"><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold capitalize text-emerald-600">{{ str_replace('_', ' ', $asset->condition) }}</span></td>
                            <td class="px-6 py-4"><span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold capitalize text-indigo-600">{{ str_replace('_', ' ', $asset->lifecycle_status) }}</span></td>
                            <td class="px-6 py-4 text-right"><a href="{{ route('assets.show', $asset) }}" class="font-bold text-indigo-600">View →</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-16 text-center"><p class="text-lg font-extrabold text-eims-ink">No assets found</p><p class="mt-2 text-sm text-slate-400">Register the first property or adjust the current filters.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($assets->hasPages()) <div class="border-t border-slate-100 p-5">{{ $assets->links() }}</div> @endif
    </div>
</div>
@endsection
