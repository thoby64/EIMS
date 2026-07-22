@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
<main class="relative min-h-screen overflow-hidden bg-slate-950 bg-cover bg-center" style="background-image: url('{{ asset('branding/dashboard.png') }}')">
    <div class="absolute inset-0 bg-gradient-to-br from-[#35104f]/95 via-[#54207d]/88 to-[#234ed8]/82"></div>
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_75%_12%,rgba(255,255,255,.16),transparent_25rem),linear-gradient(to_top,rgba(14,20,55,.28),transparent_45%)]"></div>

    <div class="relative mx-auto grid min-h-screen w-full max-w-[1500px] gap-8 px-5 py-6 sm:px-8 lg:grid-cols-[1.15fr_.85fr] lg:items-center lg:gap-16 lg:px-14 lg:py-10">
        <section class="flex min-w-0 flex-col text-white lg:min-h-[calc(100vh-5rem)] lg:justify-between">
            <div class="flex items-center gap-4">
                <div class="eims-logo-tile w-20 sm:w-24"><img src="{{ asset('branding/sjut-crest.png') }}" alt="Institution crest" class="eims-crest"></div>
                <div><p class="text-xl font-black tracking-wide">EIMS</p><p class="mt-1 text-xs font-semibold text-white/65">Enterprise Infrastructure Management System</p></div>
            </div>

            <div class="mt-10 max-w-2xl lg:my-auto lg:py-12">
                <p class="text-xs font-black uppercase tracking-[.22em] text-cyan-200 sm:text-sm">Infrastructure, clearly managed</p>
                <h1 class="mt-4 max-w-xl text-3xl font-black leading-tight tracking-tight sm:text-4xl lg:mt-6 lg:text-6xl">Know what you own. Know where it is. Know who is responsible.</h1>
                <p class="mt-5 hidden max-w-xl text-base leading-8 text-white/70 sm:block lg:mt-7 lg:text-lg">One trusted operational record for assets, requests, handovers, maintenance, inspections and accountability.</p>
            </div>

            <p class="hidden text-xs font-semibold text-white/50 lg:block">Secure organizational access · {{ now()->year }}</p>
        </section>

        <section class="flex items-center justify-center pb-5 lg:pb-0">
            <div class="w-full max-w-lg rounded-3xl border border-white/75 bg-white/95 p-6 shadow-[0_30px_90px_rgba(15,20,55,.34)] backdrop-blur-xl sm:p-9 lg:p-10">
                <div class="mb-8"><p class="text-sm font-bold text-indigo-600">Welcome back</p><h2 class="mt-2 text-3xl font-black tracking-tight text-eims-ink sm:text-4xl">Sign in to EIMS</h2><p class="mt-3 text-sm leading-6 text-slate-400">Enter your staff number or institutional email to continue.</p></div>

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="identity" class="mb-2 block text-sm font-bold text-slate-600">Staff number or email</label>
                        <input id="identity" name="identity" value="{{ old('identity') }}" required autofocus autocomplete="username" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3.5 text-sm text-eims-ink shadow-sm outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" placeholder="e.g. STAFF-001" aria-describedby="identity-error">
                        <div id="identity-error">@error('identity')<p class="mt-2 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror @error('login')<p class="mt-2 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror</div>
                    </div>
                    <div>
                        <label for="password" class="mb-2 block text-sm font-bold text-slate-600">Password</label>
                        <div class="relative"><input id="password" type="password" name="password" required autocomplete="current-password" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3.5 pr-12 text-sm text-eims-ink shadow-sm outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" placeholder="Enter your password" aria-describedby="password-help"><button type="button" data-password-toggle class="absolute inset-y-0 right-0 grid w-12 place-items-center text-slate-400 transition hover:text-indigo-600" aria-label="Show password"><svg data-eye-open class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg><svg data-eye-closed class="hidden size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 3 18 18M10.6 6.2A10.8 10.8 0 0 1 12 6c6.5 0 10 6 10 6a17 17 0 0 1-2.1 2.8M6.4 6.4C3.5 8.2 2 12 2 12s3.5 6 10 6a10.4 10.4 0 0 0 4.1-.8M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg></button></div>
                        <div id="password-help">@error('password')<p class="mt-2 text-xs font-semibold text-red-500">{{ $message }}</p>@enderror<p data-caps-lock class="mt-2 hidden text-xs font-semibold text-amber-600">Caps Lock is on.</p></div>
                    </div>
                    <label class="flex cursor-pointer items-center gap-3 text-sm font-semibold text-slate-500"><input type="checkbox" name="remember" value="1" class="peer sr-only"><span class="relative h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-indigo-600 after:absolute after:left-1 after:top-1 after:size-4 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-5"></span><span>Keep me signed in</span></label>
                    <button class="w-full rounded-xl bg-gradient-to-r from-indigo-600 to-purple-700 px-5 py-3.5 text-sm font-extrabold text-white shadow-lg shadow-indigo-500/20 transition hover:-translate-y-0.5 hover:shadow-xl">Sign in securely</button>
                </form>

                <div class="mt-7 flex items-start gap-3 border-t border-slate-100 pt-5 text-xs leading-5 text-slate-400"><svg class="mt-0.5 size-4 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3 4 6v5c0 5 3.4 8.5 8 10 4.6-1.5 8-5 8-10V6l-8-3Z"/><path d="m9 12 2 2 4-5"/></svg><p>Authorized organizational access only. Sign-in attempts and account activity are securely audited.</p></div>
            </div>
        </section>
    </div>
</main>

<script>
(() => {
    const password = document.getElementById('password');
    const toggle = document.querySelector('[data-password-toggle]');
    const caps = document.querySelector('[data-caps-lock]');
    toggle.addEventListener('click', () => {
        const showing = password.type === 'text';
        password.type = showing ? 'password' : 'text';
        toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        toggle.querySelector('[data-eye-open]').classList.toggle('hidden', !showing);
        toggle.querySelector('[data-eye-closed]').classList.toggle('hidden', showing);
        password.focus();
    });
    const updateCapsLock = event => caps.classList.toggle('hidden', !event.getModifierState('CapsLock'));
    password.addEventListener('keydown', updateCapsLock);
    password.addEventListener('keyup', updateCapsLock);
    password.addEventListener('blur', () => caps.classList.add('hidden'));
})();
</script>
@endsection
