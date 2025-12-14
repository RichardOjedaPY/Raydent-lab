<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Clinica, Paciente, Pedido, User};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Si tu sistema es multi-tenant por clinica_id, esto filtra automáticamente.
        // Para admin (clinica_id null) mostrará todo.
        $clinicaId = $user->clinica_id ?? null;

        $qPedidos = Pedido::query()->when($clinicaId, fn ($q) => $q->where('clinica_id', $clinicaId));
        $qPacientes = Paciente::query()->when($clinicaId, fn ($q) => $q->where('clinica_id', $clinicaId));

        // KPIs principales
        $totalPedidos   = (clone $qPedidos)->count();
        $pedidosHoy     = (clone $qPedidos)->whereDate('created_at', now()->toDateString())->count();
        $pedidosSemana  = (clone $qPedidos)->where('created_at', '>=', now()->subDays(6)->startOfDay())->count();
        $pedidosMes     = (clone $qPedidos)->where('created_at', '>=', now()->subDays(29)->startOfDay())->count();

        $totalPacientes = (clone $qPacientes)->count();

        // Conteo por estado (no asumimos valores; agrupamos lo que exista)
        $estadosRaw = (clone $qPedidos)
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->orderBy('estado')
            ->pluck('total', 'estado');

        $estadoLabels = $estadosRaw->keys()->map(fn ($e) => $e ? Str::headline($e) : 'Sin estado')->values();
        $estadoData   = $estadosRaw->values()->map(fn ($v) => (int) $v)->values();

        // Pedidos últimos 14 días (rellenando días sin datos)
        $desde = now()->subDays(13)->startOfDay();

        $porDiaRaw = (clone $qPedidos)
            ->where('created_at', '>=', $desde)
            ->selectRaw('DATE(created_at) as fecha, COUNT(*) as total')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->pluck('total', 'fecha')
            ->all();

        $pedidosPorDiaLabels = [];
        $pedidosPorDiaData   = [];

        for ($i = 13; $i >= 0; $i--) {
            $dIso = now()->subDays($i)->toDateString(); // YYYY-MM-DD
            $pedidosPorDiaLabels[] = Carbon::parse($dIso)->format('d/m');
            $pedidosPorDiaData[]   = (int) ($porDiaRaw[$dIso] ?? 0);
        }

        // Últimos pedidos
        $ultimosPedidos = (clone $qPedidos)
            ->with(['clinica', 'paciente']) // si tus relaciones existen
            ->latest('id')
            ->take(8)
            ->get();

        // Actividad reciente (Spatie Activitylog)
        $actividad = Activity::query()
            ->latest('id')
            ->take(8)
            ->get();

        // Para admin global, sumarizadores extra
        $totalClinicas = $clinicaId ? null : Clinica::count();
        $totalUsuarios = $clinicaId ? null : User::count();

        return view('admin.dashboard', compact(
            'totalPedidos',
            'pedidosHoy',
            'pedidosSemana',
            'pedidosMes',
            'totalPacientes',
            'estadoLabels',
            'estadoData',
            'pedidosPorDiaLabels',
            'pedidosPorDiaData',
            'ultimosPedidos',
            'actividad',
            'totalClinicas',
            'totalUsuarios'
        ));
    }
}
