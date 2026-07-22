<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditTrailController extends Controller
{
    public function __invoke(Request $r): View
    {
        $events = AuditLog::with('actor')->when($r->filled('event_type'), fn ($q) => $q->where('event_type', $r->string('event_type')))->when($r->filled('action'), fn ($q) => $q->where('action', $r->string('action')))->when($r->filled('module'), fn ($q) => $q->where('module', $r->string('module')))->when($r->filled('outcome'), fn ($q) => $q->where('outcome', $r->string('outcome')))->when($r->filled('actor'), fn ($q) => $q->where('actor_user_id', $r->integer('actor')))->when($r->filled('from'), fn ($q) => $q->whereDate('occurred_at', '>=', $r->date('from')))->when($r->filled('to'), fn ($q) => $q->whereDate('occurred_at', '<=', $r->date('to')))->when($r->filled('q'), fn ($q) => $q->where(fn ($search) => $search->where('actor_identity', 'like', '%'.$r->string('q').'%')->orWhere('ip_address', 'like', '%'.$r->string('q').'%')->orWhere('route_name', 'like', '%'.$r->string('q').'%')->orWhere('auditable_public_id', 'like', '%'.$r->string('q').'%')))->latest('occurred_at')->paginate(30)->withQueryString();

        return view('audit.index', ['events' => $events, 'eventTypes' => AuditLog::distinct()->orderBy('event_type')->pluck('event_type'), 'actions' => AuditLog::distinct()->orderBy('action')->pluck('action'), 'modules' => AuditLog::whereNotNull('module')->distinct()->orderBy('module')->pluck('module'), 'actors' => User::whereHas('roles')->orderBy('name')->get()]);
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('actor');

        return view('audit.show', ['event' => $auditLog]);
    }
}
