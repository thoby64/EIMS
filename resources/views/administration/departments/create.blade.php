@extends('layouts.app')
@section('title', 'Add department · EIMS')
@section('heading', 'Add department')
@section('content')
<div class="mx-auto max-w-4xl pt-4">
    @if($errors->any())<div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('administration.departments.store') }}" class="eims-standard-form">@csrf
        <header class="eims-form-heading"><h2>Department information</h2><p>Enter the department details below. Fields marked with an asterisk are required.</p></header>
        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <label>Department name *<input name="name" value="{{ old('name') }}" required class="mt-2 w-full"></label>
            <label>Department code *<input name="code" value="{{ old('code') }}" required maxlength="30" pattern="[A-Za-z0-9-]+" placeholder="e.g. FIN" class="mt-2 w-full uppercase"><span class="mt-1 block text-xs font-normal text-slate-400">Letters, numbers and hyphens only.</span></label>
            <label class="md:col-span-2">Description<textarea name="description" rows="4" class="mt-2 w-full">{{ old('description') }}</textarea></label>
            <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="size-4 rounded border-slate-300 text-indigo-600"> Active and available for use</label>
        </div>
        <footer class="mt-7 flex justify-end gap-3 border-t border-slate-100 pt-5"><a href="{{ route('administration.departments.index') }}" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-500">Cancel</a><button class="rounded-xl bg-gradient-to-r from-indigo-600 to-purple-700 px-6 py-3 text-sm font-extrabold text-white shadow-lg">Create department</button></footer>
    </form>
</div>
@endsection
