@extends('layouts.app')
@section('title','Reports · EIMS')
@section('heading','Reports and exports')
@section('content')
@php($cards = [
    ['assets','Registered assets','asset','bg-indigo-50 text-indigo-600','All recorded infrastructure'],
    ['available','Available assets','layers','bg-violet-50 text-violet-600','Ready for allocation'],
    ['assigned','Assigned assets','assignment','bg-emerald-50 text-emerald-600','Under active custody'],
    ['maintenance','Open maintenance','maintenance','bg-amber-50 text-amber-600','Cases requiring action'],
    ['inspections','Scheduled inspections','inspection','bg-sky-50 text-sky-600','Awaiting assessment'],
    ['disposals','Active disposals','disposal','bg-rose-50 text-rose-600','Controlled retirement cases'],
])
<div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
    @foreach($cards as [$key,$label,$icon,$color,$note])<article class="eims-card p-6"><div class="flex items-start justify-between"><div><p class="text-xs font-black uppercase tracking-wider text-slate-400">{{ $label }}</p><p class="mt-3 text-4xl font-black text-eims-ink">{{ number_format($stats[$key]) }}</p><p class="mt-2 text-sm text-slate-400">{{ $note }}</p></div><span class="grid size-11 place-items-center rounded-lg {{ $color }}"><x-eims-icon :name="$icon" class="size-5" /></span></div></article>@endforeach
</div>
<section class="eims-card mt-6 overflow-hidden"><div class="border-b border-slate-100 bg-gradient-to-r from-purple-50/70 to-blue-50/60 p-6"><p class="eims-section-title">Reporting centre</p><h2 class="mt-1 text-xl font-extrabold text-eims-ink">Available reports</h2><p class="mt-1 text-sm text-slate-400">Explore operational records or export filtered information.</p></div><div class="grid gap-5 p-6 md:grid-cols-2"><a href="{{ route('reports.assets') }}" class="group rounded-2xl border border-indigo-100 bg-white p-5 shadow-sm transition hover:border-indigo-200"><x-eims-icon name="report" class="size-6 text-indigo-600" /><h3 class="mt-4 font-extrabold text-eims-ink group-hover:text-indigo-700">Complete asset register</h3><p class="mt-2 text-sm leading-6 text-slate-500">Filter by category, department, location, lifecycle and condition; export the exact results to CSV.</p><p class="mt-4 text-sm font-bold text-indigo-600">Open report →</p></a>@if(auth()->user()->hasPermission('audit.view'))<a href="{{ route('audit.index') }}" class="group rounded-2xl border border-purple-100 bg-white p-5 shadow-sm transition hover:border-purple-200"><x-eims-icon name="audit" class="size-6 text-purple-600" /><h3 class="mt-4 font-extrabold text-eims-ink group-hover:text-purple-700">Immutable audit history</h3><p class="mt-2 text-sm leading-6 text-slate-500">Review lifecycle events, actors, timestamps and recorded before-and-after context.</p><p class="mt-4 text-sm font-bold text-purple-700">Open audit history →</p></a>@endif</div></section>
@endsection
