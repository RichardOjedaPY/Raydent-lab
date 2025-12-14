<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:activity_logs.view')->only(['index']);
        $this->middleware('permission:activity_logs.show')->only(['show']);
    }
    

    public function index(Request $r)
    {
        $user = $r->user();
    
        $isAdmin   = $user->hasRole('admin');
        $isTecnico = $user->hasRole('tecnico');
        $clinicaId = $user->clinica_id ? (int) $user->clinica_id : null;
    
        $q        = trim((string) $r->input('q', ''));
        $logName  = trim((string) $r->input('log_name', ''));
        $event    = trim((string) $r->input('event', ''));
        $from     = trim((string) $r->input('from', ''));
        $to       = trim((string) $r->input('to', ''));
    
        // Base query con scope tenant solo para clínica
        $base = Activity::query()
            ->with(['causer', 'subject'])
            ->when(! $isAdmin && ! $isTecnico && $clinicaId, function ($qq) use ($clinicaId) {
                $qq->where('properties->clinica_id', $clinicaId);
            });
    
        $logs = (clone $base)
            ->when($logName !== '', fn ($qq) => $qq->where('log_name', $logName))
            ->when($event !== '', fn ($qq) => $qq->where('event', $event))
            ->when($from !== '', fn ($qq) => $qq->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn ($qq) => $qq->whereDate('created_at', '<=', $to))
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('description', 'like', "%{$q}%")
                      ->orWhere('log_name', 'like', "%{$q}%")
                      ->orWhere('subject_type', 'like', "%{$q}%")
                      ->orWhere('causer_type', 'like', "%{$q}%");
                });
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();
    
        // Importante: logNames/events deben respetar el mismo scope tenant
        $logNames = (clone $base)
            ->select('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');
    
        $events = (clone $base)
            ->select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->filter(); // saca null/vacíos
    
        return view('admin.activity_logs.index', compact('logs', 'logNames', 'events'));
    }
    

    public function show(Activity $activity, Request $r)
{
    $user = $r->user();
    $isAdmin   = $user->hasRole('admin');
    $isTecnico = $user->hasRole('tecnico');
    $clinicaId = $user->clinica_id ? (int) $user->clinica_id : null;

    if (! $isAdmin && ! $isTecnico && $clinicaId) {
        if ((int) data_get($activity->properties, 'clinica_id') !== $clinicaId) {
            abort(403);
        }
    }

    $activity->load(['causer', 'subject']);

    return view('admin.activity_logs.show', compact('activity'));
}

}
