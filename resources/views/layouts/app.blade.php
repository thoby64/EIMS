<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#5e72e4">
    <title>@yield('title', 'Dashboard') · EIMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <div data-sidebar-backdrop data-sidebar-toggle class="fixed inset-0 z-30 hidden bg-slate-950/40 backdrop-blur-sm lg:hidden"></div>

    <aside data-sidebar class="fixed inset-y-4 left-4 z-40 flex w-72 -translate-x-full flex-col rounded-2xl border border-white/80 bg-white/95 p-4 shadow-2xl backdrop-blur-xl transition-transform duration-300 lg:translate-x-0">
        <div class="relative flex min-h-24 items-center justify-start gap-4 px-4 py-3">
            <div class="eims-logo-tile eims-logo-tile-sidebar w-16"><img src="{{ asset('branding/sjut-crest.png') }}" alt="Institution crest" class="eims-crest"></div>
            <div class="eims-wordmark">EIMS</div>
            <button data-sidebar-toggle class="absolute right-0 top-1 grid size-9 place-items-center rounded-lg text-slate-400 lg:hidden" aria-label="Close navigation">×</button>
        </div>

        <div class="my-3 h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent"></div>

        <nav class="flex-1 space-y-1 overflow-y-auto py-2">
            <a href="{{ route('dashboard') }}" class="eims-nav-link {{ request()->routeIs('dashboard') ? 'eims-nav-link-active' : '' }}">
                <span class="eims-icon text-indigo-600"><x-eims-icon name="home" /></span><span>Dashboard</span>
            </a>
            <a href="{{ route('assets.index') }}" class="eims-nav-link {{ request()->routeIs('assets.*') ? 'eims-nav-link-active' : '' }}"><span class="eims-icon text-violet-600"><x-eims-icon name="asset" /></span><span>Asset Registry</span></a>
            <a href="{{ route('assets.scan') }}" class="eims-nav-link {{ request()->routeIs('assets.scan*') ? 'eims-nav-link-active' : '' }}"><span class="eims-icon text-sky-600"><x-eims-icon name="scan" /></span><span>Scan & Verify</span></a>
            @if(auth()->user()->hasPermission('assignments.view'))<a href="{{ route('assignments.index') }}" class="eims-nav-link {{ request()->routeIs('assignments.*') ? 'eims-nav-link-active' : '' }}"><span class="eims-icon text-emerald-600"><x-eims-icon name="assignment" /></span><span>Assignments</span></a>@endif
            <a href="{{ route('movements.index') }}" class="eims-nav-link {{ request()->routeIs('movements.*') ? 'eims-nav-link-active' : '' }}"><span class="eims-icon text-blue-600"><x-eims-icon name="transfer" /></span><span>Returns & transfers</span></a>
            @if(auth()->user()->hasPermission('handovers.confirm'))<a href="{{ route('handovers.pending') }}" class="eims-nav-link {{ request()->routeIs('handovers.*') ? 'eims-nav-link-active' : '' }}"><span class="eims-icon text-teal-600"><x-eims-icon name="handover" /></span><span>My handovers</span></a>@endif
            <a href="{{ route('requests.index') }}" class="eims-nav-link {{ request()->routeIs('requests.*') ? 'eims-nav-link-active' : '' }}"><span class="eims-icon text-orange-600"><x-eims-icon name="request" /></span><span>Asset Requests</span></a>
            <a href="{{ route('maintenance.index') }}" class="eims-nav-link {{ request()->routeIs('maintenance.*') ? 'eims-nav-link-active' : '' }}"><span class="eims-icon text-rose-600"><x-eims-icon name="maintenance" /></span><span>Maintenance</span></a>
            <a href="{{ route('inspections.index') }}" class="eims-nav-link {{ request()->routeIs('inspections.*') ? 'eims-nav-link-active' : '' }}"><span class="eims-icon text-slate-600"><x-eims-icon name="inspection" /></span><span>Inspections</span></a>
            <a href="{{ route('disposals.index') }}" class="eims-nav-link {{ request()->routeIs('disposals.*') ? 'eims-nav-link-active' : '' }}"><span class="eims-icon text-red-600"><x-eims-icon name="disposal" /></span><span>Retirement & disposal</span></a>

            <p class="px-4 pb-1 pt-6 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Management</p>
            @if(auth()->user()->hasPermission('reports.view'))<a href="{{ route('reports.index') }}" class="eims-nav-link {{ request()->routeIs('reports.*') ? 'eims-nav-link-active' : '' }}"><span class="eims-icon text-indigo-600"><x-eims-icon name="report" /></span><span>Reports</span></a>@endif
            @if(auth()->user()->hasPermission('audit.view'))<a href="{{ route('audit.index') }}" class="eims-nav-link {{ request()->routeIs('audit.*') ? 'eims-nav-link-active' : '' }}"><span class="eims-icon text-teal-600"><x-eims-icon name="audit" /></span><span>Audit trail</span></a>@endif
            @if(auth()->user()->hasPermission('access.manage'))<a href="{{ route('administration.users.index') }}" class="eims-nav-link {{ request()->routeIs('administration.*') ? 'eims-nav-link-active' : '' }}"><span class="eims-icon text-slate-600"><x-eims-icon name="admin" /></span><span>Administration</span></a>@endif
        </nav>

    </aside>

    <main class="min-h-screen lg:pl-[19rem]">
        <div class="eims-system-hero absolute inset-x-0 top-0 -z-10 h-96"></div>
        <header class="flex items-center gap-4 px-5 py-5 text-white md:px-8">
            <button data-sidebar-toggle class="grid size-10 place-items-center rounded-xl bg-white/15 backdrop-blur lg:hidden" aria-label="Open navigation">☰</button>
            <div>
                <p class="text-xs font-semibold text-white/60">Enterprise Infrastructure Management System</p>
                <h1 class="text-xl font-extrabold tracking-tight">@yield('heading', 'Dashboard')</h1>
            </div>
            <div class="ml-auto flex items-center gap-3">
                <a href="{{ route('assets.scan') }}" class="hidden items-center gap-2 rounded-xl bg-white/95 px-4 py-2.5 text-sm font-bold text-purple-700 shadow-lg transition hover:-translate-y-0.5 sm:flex"><x-eims-icon name="scan" class="size-4" /> Scan asset</a>
                @php($unreadCount = auth()->user()->unreadNotifications()->count())
                <details class="relative"><summary class="relative grid size-10 cursor-pointer list-none place-items-center rounded-xl bg-white/15 backdrop-blur" aria-label="Notifications"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>@if($unreadCount)<span class="absolute -right-1 -top-1 grid min-w-5 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-black text-white">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>@endif</summary><div class="absolute right-0 z-50 mt-3 w-80 overflow-hidden rounded-2xl bg-white text-slate-700 shadow-2xl"><div class="flex items-center justify-between border-b p-4"><strong class="text-sm text-eims-ink">Notifications</strong><a href="{{ route('notifications.index') }}" class="text-xs font-bold text-indigo-600">View all</a></div><div class="max-h-80 overflow-y-auto">@forelse(auth()->user()->notifications()->latest()->limit(5)->get() as $notification)<a href="{{ route('notifications.open',$notification->id) }}" class="block border-b p-4 hover:bg-slate-50 {{ $notification->read_at ? '' : 'bg-indigo-50/60' }}"><p class="text-sm font-bold text-eims-ink">{{ $notification->data['title'] ?? 'EIMS notification' }}</p><p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ $notification->data['message'] ?? '' }}</p><p class="mt-2 text-[10px] font-semibold text-slate-400">{{ $notification->created_at->diffForHumans() }}</p></a>@empty<p class="p-6 text-center text-sm text-slate-400">No notifications yet.</p>@endforelse</div></div></details>
                <details class="group relative" data-account-menu>
                    <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl border border-white/10 bg-white/10 px-2 py-1.5 backdrop-blur transition hover:bg-white/20" aria-label="Open account menu">
                        <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-white text-xs font-black text-purple-700 shadow-md">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="hidden text-left md:block"><span class="block max-w-40 truncate text-xs font-bold">{{ auth()->user()->name }}</span><span class="mt-0.5 block text-[10px] text-white/60">{{ auth()->user()->staff_number }} · My profile</span></span>
                        <svg class="hidden size-4 text-white/60 transition group-open:rotate-180 md:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                    </summary>
                    <div class="absolute right-0 z-50 mt-3 w-72 overflow-hidden rounded-2xl border border-white/70 bg-white text-slate-600 shadow-[0_24px_60px_rgba(26,33,66,.25)]">
                        <div class="bg-gradient-to-br from-purple-50 via-white to-indigo-50 p-5"><div class="flex items-center gap-3"><span class="grid size-11 place-items-center rounded-xl bg-gradient-to-br from-purple-700 to-indigo-600 text-sm font-black text-white shadow-lg">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><div class="min-w-0"><p class="truncate text-sm font-extrabold text-eims-ink">{{ auth()->user()->name }}</p><p class="truncate text-xs text-slate-400">{{ auth()->user()->email }}</p></div></div></div>
                        <div class="space-y-2 p-3">
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-bold text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700"><span class="grid size-9 place-items-center rounded-lg bg-indigo-50 text-indigo-600"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 21a7 7 0 0 1 14 0"/></svg></span><span><span class="block">My profile</span><span class="block text-[10px] font-medium text-slate-400">View and update your details</span></span></a>
                            <div class="h-px bg-slate-100"></div>
                            <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-bold text-red-600 transition hover:bg-red-50"><span class="grid size-9 place-items-center rounded-lg bg-red-50 text-red-600"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/></svg></span><span><span class="block">Log out</span><span class="block text-[10px] font-medium text-red-400">End your current session</span></span></button></form>
                        </div>
                    </div>
                </details>
            </div>
        </header>

        <div class="px-5 pb-10 md:px-8">
            @yield('content')
        </div>
    </main>
</body>
</html>
