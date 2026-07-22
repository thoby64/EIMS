@extends('layouts.app')

@section('title', 'Scan Asset')
@section('heading', 'Scan and verify')

@section('content')
<div class="mx-auto max-w-3xl pt-4">
    <section class="eims-card overflow-hidden">
        <div class="bg-gradient-to-br from-indigo-600 to-purple-700 px-6 py-8 text-white"><p class="text-xs font-bold uppercase tracking-[0.18em] text-cyan-200">Fast asset lookup</p><h2 class="mt-2 text-2xl font-black">Scan an EIMS label</h2><p class="mt-2 max-w-xl text-sm leading-6 text-white/70">Use a phone camera, hardware barcode scanner, or enter an asset tag manually.</p></div>
        <div class="p-6">
            @vite('resources/js/scanner.js')
            <div class="overflow-hidden rounded-2xl bg-slate-950"><video data-scanner-video class="hidden aspect-video w-full object-cover" muted playsinline></video><div data-scanner-placeholder class="flex min-h-52 items-center justify-center p-8 text-center"><div><div class="mx-auto grid size-16 place-items-center rounded-2xl border border-white/10 bg-white/5 text-3xl text-white">⌁</div><p data-scanner-status class="mt-4 text-sm font-semibold text-white/60">Camera scanning is ready when you are.</p></div></div></div>
            <div class="mt-4 flex justify-center gap-3"><button type="button" data-start-scanner class="rounded-xl bg-gradient-to-r from-indigo-600 to-purple-700 px-5 py-3 text-sm font-extrabold text-white shadow-lg">Start camera scanner</button><button type="button" data-stop-scanner class="hidden rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-500">Stop scanner</button></div>

            <div class="my-7 flex items-center gap-3"><span class="h-px flex-1 bg-slate-100"></span><span class="text-xs font-bold uppercase tracking-wider text-slate-400">or enter a code</span><span class="h-px flex-1 bg-slate-100"></span></div>
            <form data-scan-form method="POST" action="{{ route('assets.scan.lookup') }}" class="flex flex-col gap-3 sm:flex-row">@csrf<input data-scan-code name="code" value="{{ old('code') }}" required autofocus class="flex-1 rounded-xl border border-slate-200 px-4 py-3 font-mono text-sm outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" placeholder="EIMS tag, barcode or QR verification URL"><button class="rounded-xl border border-indigo-200 bg-indigo-50 px-5 py-3 text-sm font-extrabold text-indigo-600">Find asset</button></form>
            @error('code')<p class="mt-3 text-sm font-semibold text-red-500">{{ $message }}</p>@enderror
            <p class="mt-5 rounded-xl bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-700">Camera access requires HTTPS or localhost. USB barcode scanners can type directly into the code field without camera permission.</p>
        </div>
    </section>
</div>
@endsection
