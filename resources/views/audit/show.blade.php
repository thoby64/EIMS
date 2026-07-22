@extends('layouts.app')
@section('title','Audit event details · EIMS')
@section('heading','Audit event details')
@section('content')
<div class="mx-auto max-w-6xl space-y-6 pt-4">
    <div><a href="{{ route('audit.index') }}" class="text-sm font-bold text-indigo-600">← Back to audit logs</a></div>
    <section class="eims-card relative overflow-hidden p-6 sm:p-8">
        <div class="pointer-events-none absolute -right-16 -top-20 size-56 rounded-full bg-indigo-100/50 blur-2xl"></div>
        <div class="pointer-events-none absolute -left-20 top-24 size-44 rounded-full bg-purple-100/40 blur-2xl"></div>
        <div class="relative flex flex-wrap items-start justify-between gap-5">
            <div class="flex items-start gap-4">
                <span class="grid size-11 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-600"><x-eims-icon name="audit" class="size-6" /></span>
                <div><p class="font-mono text-[11px] font-bold tracking-wide text-indigo-500">{{ $event->public_id }}</p><h2 class="mt-2 text-2xl font-black capitalize text-eims-ink">{{ str_replace('_',' ',$event->action) }}</h2><p class="mt-1 text-sm text-slate-400">{{ ucfirst($event->event_type) }} event · {{ $event->occurred_at->diffForHumans() }}</p></div>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-xs font-extrabold shadow-sm {{ $event->outcome==='success' ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-red-100 bg-red-50 text-red-700' }}"><span class="size-2 rounded-full {{ $event->outcome==='success' ? 'bg-emerald-500' : 'bg-red-500' }}"></span>{{ ucfirst($event->outcome) }}</span>
        </div>
        <dl class="relative mt-7 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">@foreach([['Date and time',$event->occurred_at->format('d M Y, H:i:s')],['Actor',$event->actor?->name ?? $event->actor_identity ?? 'Guest/System'],['Module',$event->module ? ucfirst($event->module) : 'Not classified'],['IP address',$event->ip_address ?: 'Not recorded'],['Request',trim(($event->http_method ?: '').' '.($event->path ?: '')) ?: 'Not applicable'],['HTTP status',$event->http_status ?: 'Not recorded'],['Route',$event->route_name ?: 'Not recorded'],['Record',class_basename($event->auditable_type).($event->auditable_id ? ' #'.$event->auditable_id : '')]] as [$label,$value])<div class="rounded-2xl border border-slate-100 bg-white/80 p-4 shadow-sm backdrop-blur"><dt class="text-[10px] font-black uppercase tracking-[.08em] text-slate-400">{{ $label }}</dt><dd class="mt-2 break-words text-sm font-bold text-eims-ink">{{ $value ?: 'Not recorded' }}</dd></div>@endforeach</dl>
    </section>
    @if($event->old_values || $event->new_values)<div class="grid gap-6 lg:grid-cols-2">@if($event->old_values)<section class="eims-card p-6"><h3 class="font-extrabold text-eims-ink">Previous values</h3><p class="mb-5 mt-1 text-sm text-slate-400">Information before this action.</p><x-audit-data :data="$event->old_values" /></section>@endif @if($event->new_values)<section class="eims-card p-6"><h3 class="font-extrabold text-eims-ink">Recorded values</h3><p class="mb-5 mt-1 text-sm text-slate-400">Information saved by this action.</p><x-audit-data :data="$event->new_values" /></section>@endif</div>@endif
    <section class="eims-card p-6"><h3 class="font-extrabold text-eims-ink">Request context</h3><p class="mb-5 mt-1 text-sm text-slate-400">Supporting information captured with the event.</p><x-audit-data :data="$event->context ?? []" /></section>
    <section class="eims-card p-6"><h3 class="font-extrabold text-eims-ink">Client information</h3><dl class="mt-4 grid gap-4 md:grid-cols-2"><div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-bold text-slate-400">Identity used</dt><dd class="mt-1 break-words text-sm font-semibold">{{ $event->actor_identity ?: 'Not recorded' }}</dd></div><div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-bold text-slate-400">Browser / client</dt><dd class="mt-1 break-words text-sm font-semibold">{{ $event->user_agent ?: 'Not recorded' }}</dd></div></dl></section>
</div>
@endsection
