@extends('layouts.app')
@section('title', 'Departments · EIMS')
@section('heading', 'Administration')
@section('content')
<div class="space-y-5 pt-4">
    <nav class="flex gap-2">
        <a href="{{ route('administration.users.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600">Staff accounts</a>
        <a href="{{ route('administration.departments.index') }}" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white">Departments</a>
    </nav>

    @if(session('success'))<div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('success') }}</div>@endif

    <section class="eims-card overflow-hidden">
        <div class="flex flex-col gap-4 border-b border-slate-100 p-6 lg:flex-row lg:items-center">
            <div><h2 class="font-extrabold text-eims-ink">Department register</h2><p class="mt-1 text-sm text-slate-400">Manage the organizational departments used for staff and asset custody.</p></div>
            <form method="GET" class="eims-filter-panel ml-auto flex flex-col gap-3 sm:flex-row sm:items-end">
                <label class="eims-filter-label">Search departments<input name="search" value="{{ request('search') }}" placeholder="Name or code" class="eims-filter-field min-w-56"></label>
                <label class="eims-filter-label">Availability<select name="status" class="eims-filter-field min-w-44"><option value="">All departments</option><option value="active" @selected(request('status')==='active')>Active</option><option value="inactive" @selected(request('status')==='inactive')>Inactive</option></select></label>
                <button class="eims-filter-button">Apply filters</button>
                @if(request()->hasAny(['search','status']))<a href="{{ route('administration.departments.index') }}" class="eims-filter-reset">Clear</a>@endif
            </form>
            <a href="{{ route('administration.departments.create') }}" class="rounded-xl bg-gradient-to-r from-indigo-600 to-purple-700 px-5 py-3 text-center text-sm font-extrabold text-white shadow-lg">Add department</a>
        </div>

        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-400"><tr><th class="px-6 py-4">Department</th><th class="px-6 py-4">Description</th><th class="px-6 py-4">Staff</th><th class="px-6 py-4">Assets</th><th class="px-6 py-4">Status</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">@forelse($departments as $department)<tr><td class="px-6 py-4"><p class="font-extrabold text-eims-ink">{{ $department->name }}</p><p class="mt-1 font-mono text-xs font-bold text-indigo-500">{{ $department->code }}</p></td><td class="max-w-md px-6 py-4 text-slate-500">{{ $department->description ?: 'No description recorded' }}</td><td class="px-6 py-4 font-bold">{{ $department->users_count }}</td><td class="px-6 py-4 font-bold">{{ $department->assets_count }}</td><td class="px-6 py-4"><span class="rounded-full px-3 py-1 text-xs font-bold {{ $department->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $department->is_active ? 'Active' : 'Inactive' }}</span></td><td class="px-6 py-4 text-right"><a href="{{ route('administration.departments.edit',$department) }}" class="font-bold text-indigo-600">Manage →</a></td></tr>@empty<tr><td colspan="6" class="p-16 text-center"><p class="font-extrabold text-eims-ink">No departments found</p><p class="mt-2 text-sm text-slate-400">Adjust the filters or add a new department.</p></td></tr>@endforelse</tbody>
        </table></div>
        @if($departments->hasPages())<div class="border-t p-5">{{ $departments->links() }}</div>@endif
    </section>
</div>
@endsection
