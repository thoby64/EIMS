<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $asset->asset_tag }} · EIMS Label</title>@vite(['resources/css/app.css'])
<style>@media print{.print-controls{display:none!important}body{background:white!important}.label-sheet{box-shadow:none!important;border:0!important;margin:0!important}@page{size:auto;margin:8mm}}</style></head>
<body class="min-h-screen bg-slate-100 p-5 sm:p-10">
    <div class="print-controls mx-auto mb-5 flex max-w-3xl justify-between"><a href="{{ route('assets.show',$asset) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600">← Asset profile</a><button onclick="window.print()" class="rounded-xl bg-gradient-to-r from-indigo-600 to-purple-700 px-5 py-2 text-sm font-extrabold text-white shadow-lg">Print EIMS label</button></div>
    <main class="label-sheet mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-8 shadow-2xl">
        <header class="flex items-center justify-between border-b-4 border-purple-700 pb-4"><div class="flex items-center gap-3"><div class="flex h-20 w-14 items-center justify-center overflow-hidden"><img src="{{ asset('branding/sjut-crest.png') }}" alt="Institution crest" class="eims-crest"></div><div><p class="text-xl font-black text-eims-ink">EIMS</p><p class="text-[10px] font-bold uppercase tracking-wider text-purple-700">Verified institutional property</p></div></div><p class="rounded-full bg-emerald-50 px-4 py-2 text-xs font-extrabold text-emerald-600">Verified asset</p></header>
        <div class="mt-7 grid items-center gap-7 sm:grid-cols-[1fr_190px]">
            <div><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Asset tag</p><h1 class="mt-1 font-mono text-2xl font-black text-eims-ink">{{ $asset->asset_tag }}</h1><p class="mt-4 text-xl font-extrabold text-eims-ink">{{ $asset->name }}</p><p class="mt-1 text-sm text-slate-400">{{ $asset->category->group->name }} · {{ $asset->category->name }}</p><img src="{{ route('assets.label.barcode',$asset) }}" class="mt-6 h-20 max-w-full" alt="Barcode for {{ $asset->asset_tag }}"><p class="mt-1 font-mono text-xs font-bold tracking-wider text-slate-600">{{ $asset->asset_tag }}</p></div>
            <div class="text-center"><img src="{{ route('assets.label.qr',$asset) }}" class="mx-auto size-44" alt="Verification QR code"><p class="mt-2 text-xs font-bold text-slate-500">Scan to verify ownership</p></div>
        </div>
        <footer class="mt-7 border-t border-slate-100 pt-4 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-400">Enterprise Infrastructure Management System · Do not remove this label</footer>
    </main>
</body></html>
