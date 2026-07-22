@extends('layouts.app')

@section('title', $asset->asset_tag)
@section('heading', 'Asset profile')

@section('content')
<div class="pt-4">
    @if (session('success')) <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('success') }}</div> @endif
    <div class="grid gap-6 xl:grid-cols-[1.6fr_1fr]">
        <div class="space-y-6">
            <section class="eims-card p-6">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                    <div class="grid size-16 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-700 text-xl font-black text-white shadow-lg">{{ $asset->category->code }}</div>
                    <div class="flex-1"><p class="font-mono text-xs font-bold text-indigo-500">{{ $asset->asset_tag }}</p><h2 class="mt-1 text-2xl font-black tracking-tight text-eims-ink">{{ $asset->name }}</h2><p class="mt-2 text-sm text-slate-400">{{ $asset->category->group->name }} · {{ $asset->category->name }}</p></div>
                    @if(auth()->user()->hasPermission('disposals.propose') && !in_array($asset->lifecycle_status,['disposed','retired','awaiting_disposal']))<a href="{{ route('disposals.create',$asset) }}" class="h-fit rounded-lg bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">Propose retirement</a>@endif
                    <div class="flex flex-wrap justify-end gap-2">@if(auth()->user()->hasPermission('assets.assign') && $asset->lifecycle_status==='in_stock')<a href="{{ route('assignments.create',['asset'=>$asset->public_id]) }}" class="rounded-lg bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Assign asset</a>@endif @if($asset->lifecycle_status==='assigned')<a href="{{ route('movements.create',$asset) }}" class="rounded-lg bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700">Request return or transfer</a>@endif @if(auth()->user()->hasPermission('assets.labels.print'))<a href="{{ route('assets.label',$asset) }}" class="rounded-lg border border-purple-200 px-3 py-1 text-xs font-bold text-purple-600">Print label</a>@endif @if(auth()->user()->hasPermission('assets.update') && $asset->isEditable())<a href="{{ route('assets.edit',$asset) }}" class="rounded-lg border border-indigo-200 px-3 py-1 text-xs font-bold text-indigo-600">Edit asset</a>@elseif(!$asset->isEditable())<span class="rounded-lg bg-amber-50 px-3 py-1 text-xs font-bold text-amber-600">Editing locked</span>@endif<span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold capitalize text-emerald-600">{{ str_replace('_',' ',$asset->condition) }}</span><span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold capitalize text-indigo-600">{{ str_replace('_',' ',$asset->lifecycle_status) }}</span></div>
                </div>
                @if($asset->description)<p class="mt-6 border-t border-slate-100 pt-5 text-sm leading-6 text-slate-500">{{ $asset->description }}</p>@endif
                <dl class="mt-6 grid gap-5 border-t border-slate-100 pt-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([['Serial number',$asset->serial_number],['Brand',$asset->brand],['Model',$asset->model],['Location',$asset->location?->name],['Custodian',$asset->custodian?->name],['Registered by',$asset->registrar->name]] as [$label,$value])<div><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $label }}</dt><dd class="mt-1 text-sm font-bold text-eims-ink">{{ $value ?: 'Not recorded' }}</dd></div>@endforeach
                </dl>
            </section>
            @if($asset->attributeValues->isNotEmpty())
            <section class="eims-card p-6"><h3 class="font-extrabold text-eims-ink">Category details</h3><dl class="mt-5 grid gap-5 sm:grid-cols-2">@foreach($asset->attributeValues as $value)<div><dt class="text-xs font-bold text-slate-400">{{ $value->definition->name }}</dt><dd class="mt-1 font-semibold text-eims-ink">{{ $value->text_value ?? $value->number_value ?? optional($value->date_value)->format('d M Y') ?? ($value->boolean_value ? 'Yes' : 'No') }} {{ $value->definition->unit }}</dd></div>@endforeach</dl></section>
            @endif
            @if($asset->customProperties->isNotEmpty())
            <section class="eims-card p-6"><h3 class="font-extrabold text-eims-ink">Additional properties</h3><dl class="mt-5 grid gap-5 sm:grid-cols-2">@foreach($asset->customProperties as $property)<div><dt class="text-xs font-bold text-slate-400">{{ $property->name }}</dt><dd class="mt-1 font-semibold text-eims-ink">{{ $property->value }}</dd></div>@endforeach</dl></section>
            @endif
        </div>
        <div class="space-y-6">
            <section class="eims-card p-6"><h3 class="font-extrabold text-eims-ink">Scannable identity</h3><p class="mt-2 text-xs leading-5 text-slate-400">The visual QR and barcode labels will be generated from these protected values.</p><div class="mt-5 space-y-3">@foreach($asset->identifiers as $identifier)<div class="rounded-xl bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ str_replace('_',' ',$identifier->type) }}</p><p class="mt-1 break-all font-mono text-xs font-bold text-eims-ink">{{ $identifier->type === 'qr_token' ? 'Protected verification token' : $identifier->value }}</p></div>@endforeach</div></section>
            <section class="eims-card p-6"><h3 class="font-extrabold text-eims-ink">Lifecycle history</h3><div class="mt-5 space-y-5">@foreach($asset->events as $event)<div class="relative border-l-2 border-indigo-100 pl-5"><span class="absolute -left-[6px] top-1 size-2.5 rounded-full bg-indigo-500 ring-4 ring-indigo-50"></span><p class="text-sm font-bold text-eims-ink">{{ $event->summary }}</p><p class="mt-1 text-xs text-slate-400">{{ $event->actor?->name }} · {{ $event->occurred_at->format('d M Y, H:i') }}</p></div>@endforeach</div></section>
        </div>
    </div>
</div>
@endsection
