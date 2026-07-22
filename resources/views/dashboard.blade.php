@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Infrastructure overview')

@section('content')
    <section class="grid gap-5 pt-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Registered assets', 'value' => $metrics['assets'], 'detail' => 'Across all lifecycle states', 'style' => 'bg-indigo-50 text-indigo-600', 'icon' => 'asset'],
            ['label' => 'Asset groups', 'value' => $metrics['groups'], 'detail' => 'Configurable classifications', 'style' => 'bg-violet-50 text-violet-600', 'icon' => 'layers'],
            ['label' => 'Categories', 'value' => $metrics['categories'], 'detail' => 'With category-specific fields', 'style' => 'bg-emerald-50 text-emerald-600', 'icon' => 'category'],
            ['label' => 'Active locations', 'value' => $metrics['locations'], 'detail' => 'Organization locations available', 'style' => 'bg-orange-50 text-orange-600', 'icon' => 'location'],
        ] as $metric)
            <article class="eims-card flex items-center justify-between p-5">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $metric['label'] }}</p>
                    <p class="mt-1 text-3xl font-black tracking-tight text-eims-ink">{{ number_format($metric['value']) }}</p>
                    <p class="mt-2 text-xs font-medium text-slate-400">{{ $metric['detail'] }}</p>
                </div>
                <div class="grid size-11 place-items-center rounded-lg {{ $metric['style'] }}"><x-eims-icon :name="$metric['icon']" class="size-5" /></div>
            </article>
        @endforeach
    </section>

    <section class="mt-6 grid gap-6 {{ $showClassifications ? 'xl:grid-cols-[1.6fr_1fr]' : '' }}">
        @if($showClassifications)
        <article class="eims-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <div>
                    <h2 class="font-extrabold text-eims-ink">Infrastructure groups</h2>
                </div>
                <a href="{{ route('assets.index') }}" class="rounded-lg bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-600">View registry</a>
            </div>
            <div class="grid gap-3 p-5 sm:grid-cols-2">
                @foreach ($groups as $group)
                    <div class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 transition hover:border-indigo-100 hover:bg-indigo-50/40">
                        <x-classification-icon :icon="$group->icon" :name="$group->name" :color="$group->color" />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-eims-ink">{{ $group->name }}</p>
                            <p class="text-xs text-slate-400">Ready for registration</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>
        @endif

        <article class="eims-card p-6">
            <p class="text-xs font-bold uppercase tracking-wider text-indigo-500">Start a workflow</p>
            <h2 class="mt-2 text-xl font-black tracking-tight text-eims-ink">What would you like to do?</h2>
            <p class="mt-2 text-sm leading-6 text-slate-400">Open the most common EIMS workflows from one place.</p>
            <div class="mt-6 space-y-3">
                @if(auth()->user()->hasPermission('assets.create'))<a href="{{ route('assets.create') }}" class="flex w-full items-center gap-3 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-700 px-4 py-3 text-left text-sm font-bold text-white shadow-lg shadow-indigo-500/20"><x-eims-icon name="asset" /> Register an asset</a>@endif
                <a href="{{ route('assets.scan') }}" class="flex w-full items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-left text-sm font-bold text-slate-600 hover:border-indigo-200 hover:bg-indigo-50"><x-eims-icon name="scan" class="size-5 text-indigo-500" /> Scan an EIMS label</a>
                @if(auth()->user()->hasPermission('requests.create'))<a href="{{ route('requests.create') }}" class="flex w-full items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-left text-sm font-bold text-slate-600 hover:border-purple-200 hover:bg-purple-50"><x-eims-icon name="request" class="size-5 text-purple-500" /> Submit a property request</a>@endif
            </div>
        </article>
    </section>

    @if($showClassifications)
        <section class="eims-card mt-6 overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-6 py-5">
                <div><h2 class="font-extrabold text-eims-ink">Asset categories</h2><p class="mt-1 text-xs text-slate-400">Available classifications for registering and managing infrastructure.</p></div>
                <span class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold text-slate-500">{{ $categories->count() }} available</span>
            </div>
            <div class="grid gap-3 p-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($categories as $category)
                    <div class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 transition hover:border-indigo-100 hover:bg-indigo-50/40">
                        <x-classification-icon :icon="$category->icon" :name="$category->name" :color="$category->group_color" />
                        <div class="min-w-0 flex-1"><p class="truncate text-sm font-bold text-eims-ink">{{ $category->name }}</p><p class="truncate text-xs text-slate-400">{{ $category->group_name }}</p></div>
                        <span class="rounded-md bg-slate-50 px-2 py-1 font-mono text-[10px] font-bold text-slate-500">{{ $category->code }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection
