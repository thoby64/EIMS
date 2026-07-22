@extends('layouts.app')
@section('title', 'Manage department · EIMS')
@section('heading', 'Manage department')
@section('content')
<div class="mx-auto max-w-4xl space-y-5 pt-4">
    @if(session('success'))<div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('administration.departments.update',$department) }}" class="eims-standard-form">@csrf @method('PATCH')
        <header class="eims-form-heading flex flex-wrap items-start justify-between gap-4"><div><h2>Department information</h2><p>Update the department details below.</p></div><div class="rounded-xl bg-indigo-50 px-4 py-3 text-xs font-bold text-indigo-700">{{ $department->users_count }} staff · {{ $department->assets_count }} assets</div></header>
        <div class="mt-7 grid gap-5 md:grid-cols-2">
            <label class="text-sm font-bold text-slate-600">Department name<input name="name" value="{{ old('name',$department->name) }}" required class="mt-2 w-full rounded-xl border-slate-200"></label>
            <label class="text-sm font-bold text-slate-600">Department code<input name="code" value="{{ old('code',$department->code) }}" required maxlength="30" pattern="[A-Za-z0-9-]+" class="mt-2 w-full rounded-xl border-slate-200 uppercase"></label>
            <label class="text-sm font-bold text-slate-600 md:col-span-2">Description<textarea name="description" rows="4" class="mt-2 w-full rounded-xl border-slate-200">{{ old('description',$department->description) }}</textarea></label>
            <label class="flex items-center gap-3 text-sm font-bold text-slate-600"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$department->is_active)) class="rounded border-slate-300 text-indigo-600"> Active and available for future selections</label>
        </div>
        <div class="mt-7 flex justify-end gap-3"><a href="{{ route('administration.departments.index') }}" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-500">Back to departments</a><button class="rounded-xl bg-gradient-to-r from-indigo-600 to-purple-700 px-6 py-3 text-sm font-extrabold text-white shadow-lg">Save changes</button></div>
    </form>
</div>
@endsection
