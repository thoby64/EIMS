<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Property verification · EIMS</title>@vite(['resources/css/app.css'])</head>
<body class="eims-brand-sheen flex min-h-screen items-center justify-center p-5">
    <main class="w-full max-w-lg rounded-3xl bg-white p-7 shadow-2xl sm:p-10">
        <div class="eims-logo-tile mx-auto w-28"><img src="{{ asset('branding/sjut-crest.png') }}" alt="Institution crest" class="eims-crest"></div>
        <div class="mt-6 text-center"><div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-2 text-xs font-extrabold text-emerald-700"><span class="size-2 rounded-full bg-emerald-500"></span> Genuine EIMS property record</div><h1 class="mt-5 text-2xl font-black tracking-tight text-eims-ink">University-owned property</h1><p class="mt-2 text-sm leading-6 text-slate-400">This label is linked to an active record in the Enterprise Infrastructure Management System.</p></div>
        <dl class="mt-7 divide-y divide-slate-100 rounded-2xl border border-slate-100 px-5">
            <div class="flex items-center justify-between gap-4 py-4"><dt class="text-xs font-bold text-slate-400">Asset tag</dt><dd class="font-mono text-sm font-black text-indigo-600">{{ $asset->asset_tag }}</dd></div>
            <div class="flex items-center justify-between gap-4 py-4"><dt class="text-xs font-bold text-slate-400">Property type</dt><dd class="text-right text-sm font-bold text-eims-ink">{{ $asset->category->name }}</dd></div>
            <div class="flex items-center justify-between gap-4 py-4"><dt class="text-xs font-bold text-slate-400">Infrastructure group</dt><dd class="text-right text-sm font-bold text-eims-ink">{{ $asset->category->group->name }}</dd></div>
            <div class="flex items-center justify-between gap-4 py-4"><dt class="text-xs font-bold text-slate-400">Record status</dt><dd class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold capitalize text-indigo-600">{{ str_replace('_',' ',$asset->lifecycle_status) }}</dd></div>
        </dl>
        <p class="mt-6 rounded-xl bg-amber-50 px-4 py-3 text-center text-xs leading-5 text-amber-700">For privacy and security, custodian, exact location, value, serial number and maintenance records are available only to authorized EIMS users.</p>
        <p class="mt-7 text-center text-[10px] font-bold uppercase tracking-[0.15em] text-slate-300">EIMS · Secure property verification</p>
    </main>
</body></html>
