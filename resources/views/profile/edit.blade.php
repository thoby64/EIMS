@extends('layouts.app')
@section('title','My profile · EIMS')
@section('heading','My profile')
@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    @if(session('success'))<div class="rounded-xl bg-emerald-50 p-4 text-sm font-bold text-emerald-700">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('profile.update') }}" class="eims-card p-6 sm:p-8">@csrf @method('PATCH')
        <div><p class="eims-section-title">Profile information</p><h2 class="mt-1 text-xl font-extrabold text-eims-ink">Personal and institutional details</h2><p class="mt-1 text-sm text-slate-400">Review and update your profile information.</p></div>
        <div class="mt-7 grid gap-5 md:grid-cols-2">
            <label class="text-sm font-bold text-slate-600">Full name<input name="name" value="{{ old('name',$user->name) }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-eims-ink"></label>
            <label class="text-sm font-bold text-slate-600">Phone number<input name="phone" value="{{ old('phone',$user->phone) }}" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-eims-ink"></label>
            <label class="text-sm font-bold text-slate-600">Primary location<input value="{{ $locationName ?? 'Not assigned' }}" readonly aria-readonly="true" class="mt-2 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-slate-500"></label>
            <label class="text-sm font-bold text-slate-600">Institutional email<input value="{{ $user->email }}" readonly aria-readonly="true" class="mt-2 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-slate-500"></label>
            <label class="text-sm font-bold text-slate-600">Staff number<input value="{{ $user->staff_number }}" readonly aria-readonly="true" class="mt-2 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-slate-500"></label>
            <label class="text-sm font-bold text-slate-600">Department<input value="{{ $user->department?->name ?? 'Not assigned' }}" readonly aria-readonly="true" class="mt-2 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-slate-500"></label>
            <label class="text-sm font-bold text-slate-600">System role<input value="{{ $user->roles->pluck('name')->join(', ') ?: 'Not assigned' }}" readonly aria-readonly="true" class="mt-2 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-slate-500"></label>
        </div>
        <div class="mt-6 flex justify-end"><button class="rounded-xl bg-gradient-to-r from-purple-700 to-blue-600 px-6 py-3 text-sm font-bold text-white shadow-lg">Save profile changes</button></div>
    </form>

    <form method="POST" action="{{ route('profile.password') }}" class="eims-card p-6 sm:p-8">@csrf @method('PATCH')
        <div><p class="eims-section-title">Account security</p><h2 class="mt-1 text-xl font-extrabold text-eims-ink">Change password</h2></div>
        <div class="mt-6 grid gap-5 md:grid-cols-3">
            <label class="text-sm font-bold text-slate-600">Current password<input type="password" name="current_password" required autocomplete="current-password" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3"></label>
            <label class="text-sm font-bold text-slate-600">New password<input type="password" name="password" required autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3"></label>
            <label class="text-sm font-bold text-slate-600">Confirm new password<input type="password" name="password_confirmation" required autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3"></label>
        </div>
        <div class="mt-6 flex justify-end"><button class="rounded-xl bg-gradient-to-r from-purple-700 to-blue-600 px-6 py-3 text-sm font-bold text-white shadow-lg">Change password</button></div>
    </form>
</div>
@endsection
