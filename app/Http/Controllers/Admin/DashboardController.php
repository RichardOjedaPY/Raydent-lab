<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Clinica, Paciente, Pedido, User, Pago, PedidoLiquidacion};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use App\Models\PagoAplicacion;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Multi-tenant por clinica_id (admin global => null)
        $clinicaId = $user->clinica_id ?? null;

        $qPedidos   = Pedido::query()->when($clinicaId, fn ($q) => $q->where('clinica_id', $clinicaId));
        $qPacientes = Paciente::query()->when($clinicaId, fn ($q) => $q->where('clinica_id', $clinicaId));

        // KPIs principales
        $totalPedidos   = (clone $qPedidos)->count();
        $pedidosHoy     = (clone $qPedidos)->whereDate('created_at', now()->toDateString())->count();
        $pedidosSemana  = (clone $qPedidos)->where('created_at', '>=', now()->subDays(6)->startOfDay())->count();
        $pedidosMes     = (clone $qPedidos)->where('created_at', '>=', now()->subDays(29)->startOfDay())->count();
        $totalPacientes = (clone $qPacientes)->count();

        // Conteo por estado (pedidos)
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
            $dIso = now()->subDays($i)->toDateString();
            $pedidosPorDiaLabels[] = Carbon::parse($dIso)->format('d/m');
            $pedidosPorDiaData[]   = (int) ($porDiaRaw[$dIso] ?? 0);
        }

        // Últimos pedidos
        $ultimosPedidos = (clone $qPedidos)
            ->with(['clinica', 'paciente'])
            ->latest('id')
            ->take(8)
            ->get();

        // Actividad reciente
        $actividad = Activity::query()
            ->latest('id')
            ->take(8)
            ->get();

        // Admin global
        $totalClinicas = $clinicaId ? null : Clinica::count();
        $totalUsuarios = $clinicaId ? null : User::count();

        // =====================================================================
        // PAGOS (desde tu tabla/modelo Pago)
        // =====================================================================
        $qPagos = Pago::query()->when($clinicaId, fn ($q) => $q->where('clinica_id', $clinicaId));

        $pagadoHoy = (int) (clone $qPagos)
            ->whereDate('fecha', now()->toDateString())
            ->sum('monto_gs');

        $pagadoMes = (int) (clone $qPagos)
            ->where('fecha', '>=', now()->startOfMonth()->toDateString())
            ->sum('monto_gs');

        // Pagos por día (últimos 14)
        $pagosPorDiaLabels = [];
        $pagosPorDiaData   = [];

        $pagosPorDiaRaw = (clone $qPagos)
            ->whereDate('fecha', '>=', now()->subDays(13)->toDateString())
            ->selectRaw('DATE(fecha) as fecha, SUM(monto_gs) as total')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->pluck('total', 'fecha')
            ->all();

        for ($i = 13; $i >= 0; $i--) {
            $dIso = now()->subDays($i)->toDateString();
            $pagosPorDiaLabels[] = Carbon::parse($dIso)->format('d/m');
            $pagosPorDiaData[]   = (int) ($pagosPorDiaRaw[$dIso] ?? 0);
        }

        // Pagos por mes (últimos 12)
        $pagosPorMesLabels = [];
        $pagosPorMesData   = [];

        $desdeMes = now()->subMonths(11)->startOfMonth();

        $pagosPorMesRaw = (clone $qPagos)
            ->whereDate('fecha', '>=', $desdeMes->toDateString())
            ->selectRaw('DATE_FORMAT(fecha, "%Y-%m") as ym, SUM(monto_gs) as total')
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('total', 'ym')
            ->all();

        for ($i = 11; $i >= 0; $i--) {
            $ym = now()->subMonths($i)->format('Y-m');
            $pagosPorMesLabels[] = Carbon::createFromFormat('Y-m', $ym)->format('m/Y');
            $pagosPorMesData[]   = (int) ($pagosPorMesRaw[$ym] ?? 0);
        }

        // =====================================================================
        // PENDIENTE DE PAGO (desde tu tabla/modelo PedidoLiquidacion)
        // EXACTAMENTE igual a createMultiple()
        // saldo = GREATEST(0, total_gs - pagado_gs)
        // =====================================================================
        $qLiq = PedidoLiquidacion::query()
            ->where('estado', 'confirmada')
            ->when($clinicaId, fn ($q) => $q->where('clinica_id', $clinicaId))
            ->whereRaw('GREATEST(0, (total_gs - COALESCE(pagado_gs,0))) > 0');

        $pendientePagoTotal = (int) ($qLiq
            ->selectRaw('SUM(GREATEST(0, (total_gs - COALESCE(pagado_gs,0)))) as pendiente')
            ->value('pendiente') ?? 0);

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
            'totalUsuarios',

            // pagos
            'pagadoHoy',
            'pagadoMes',
            'pendientePagoTotal',
            'pagosPorDiaLabels',
            'pagosPorDiaData',
            'pagosPorMesLabels',
            'pagosPorMesData'
        ));
    }
}
