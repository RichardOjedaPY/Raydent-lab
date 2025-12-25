<?php

namespace App\Http\Controllers\Admin\Cajero;

use App\Http\Controllers\Controller;
use App\Models\{Clinica, Pago, Pedido, PedidoLiquidacion, PagoAplicacion};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CajeroDashboardController extends Controller
{
    public function __construct()
    {
        // $this->middleware('permission:cajero.dashboard.view')->only(['index']);
    }

    public function index()
    {
        $user = auth()->user();
        $clinicaId = $user->clinica_id ?? null;

        // ==========================================================
        // 0) Detectar columnas reales (Pagos / PagoAplicaciones / Liquidaciones)
        // ==========================================================
        $pagoTable      = (new Pago())->getTable();
        $pagoMontoCol   = $this->resolveColumn($pagoTable, ['monto_gs', 'total_gs', 'monto', 'importe_gs'], 'monto pago');
        $pagoFechaCol   = $this->resolveColumn($pagoTable, ['fecha', 'created_at'], 'fecha pago');
        $pagoClinicaCol = Schema::hasColumn($pagoTable, 'clinica_id') ? 'clinica_id' : null;

        $paTable    = (new PagoAplicacion())->getTable();
        $paPagoFk   = $this->resolveColumn($paTable, ['pago_id', 'pagos_id'], 'FK pago');
        $paMontoCol = $this->resolveColumn($paTable, ['monto_gs', 'monto', 'importe_gs'], 'monto aplicación');

        $liqTable      = (new PedidoLiquidacion())->getTable();
        $liqPedidoCol  = $this->resolveColumn($liqTable, ['pedido_id', 'pedidos_id'], 'pedido_id en liquidación');
        $liqTotalCol   = $this->resolveColumn($liqTable, ['total_gs', 'total', 'monto_total'], 'total liquidación');
        $liqPagadoCol  = $this->resolveColumn($liqTable, ['pagado_gs', 'aplicado_gs', 'pagado', 'aplicado'], 'pagado/aplicado liquidación');
        $liqEstadoCol  = Schema::hasColumn($liqTable, 'estado') ? 'estado' : null;
        $liqClinicaCol = Schema::hasColumn($liqTable, 'clinica_id') ? 'clinica_id' : null;

        // IMPORTANTE: no usar ?? dentro del string interpolado
        $liqPkCol = Schema::hasColumn($liqTable, 'id') ? 'id' : $liqPedidoCol; // fallback seguro

        $pedidoTable      = (new Pedido())->getTable();
        $pedidoClinicaCol = Schema::hasColumn($pedidoTable, 'clinica_id') ? 'clinica_id' : null;
        $pedidoEstadoCol  = Schema::hasColumn($pedidoTable, 'estado') ? 'estado' : null;

        // ==========================================================
        // 1) KPIs: Pagos hoy / mes
        // ==========================================================
        $qPagos = Pago::query()
            ->when($clinicaId && $pagoClinicaCol, function ($q) use ($clinicaId, $pagoClinicaCol) {
                $q->where($pagoClinicaCol, $clinicaId);
            });

        $hoy = now()->toDateString();

        $pagadoHoy = (int) (clone $qPagos)
            ->whereDate($pagoFechaCol, $hoy)
            ->sum($pagoMontoCol);

        $pagadoMes = (int) (clone $qPagos)
            ->whereDate($pagoFechaCol, '>=', now()->startOfMonth()->toDateString())
            ->sum($pagoMontoCol);

        // ==========================================================
        // 2) Pendiente total (liquidaciones confirmadas con saldo > 0)
        // ==========================================================
        $qLiq = PedidoLiquidacion::query()
            ->from($liqTable)
            ->when($liqEstadoCol, fn ($q) => $q->where($liqEstadoCol, 'confirmada'))
            ->when($clinicaId && $liqClinicaCol, function ($q) use ($clinicaId, $liqClinicaCol) {
                $q->where($liqClinicaCol, $clinicaId);
            });

        $pendientePagoTotal = (int) (
            (clone $qLiq)
                ->selectRaw("SUM(GREATEST(0, ({$liqTotalCol} - COALESCE({$liqPagadoCol},0)))) as pendiente")
                ->value('pendiente') ?? 0
        );

        $liqPendientesCount = (int) (clone $qLiq)
            ->whereRaw("GREATEST(0, ({$liqTotalCol} - COALESCE({$liqPagadoCol},0))) > 0")
            ->count();

        // ==========================================================
        // 3) Pagos a cuenta (saldo a favor) (evita error SQL 1140)
        // ==========================================================
        $aplicadoPorPago = DB::table($paTable)
            ->selectRaw("{$paPagoFk} as pago_id, SUM({$paMontoCol}) as aplicado_gs")
            ->groupBy($paPagoFk);

        $qPagosSaldoBase = DB::table("{$pagoTable} as p")
            ->leftJoinSub($aplicadoPorPago, 'ap', function ($j) {
                $j->on('ap.pago_id', '=', 'p.id');
            })
            ->when($clinicaId && $pagoClinicaCol, function ($q) use ($clinicaId, $pagoClinicaCol) {
                $q->where("p.{$pagoClinicaCol}", $clinicaId);
            });

        $pagosACuentaTotal = (int) (
            (clone $qPagosSaldoBase)
                ->selectRaw("SUM(GREATEST(0, (p.{$pagoMontoCol} - COALESCE(ap.aplicado_gs,0)))) as total")
                ->value('total') ?? 0
        );

        $pagosACuentaCount = (int) (clone $qPagosSaldoBase)
            ->whereRaw("GREATEST(0, (p.{$pagoMontoCol} - COALESCE(ap.aplicado_gs,0))) > 0")
            ->count();

        // ==========================================================
        // 4) Pagos últimos 14 días (serie para gráfico)
        // ==========================================================
        $desde14 = now()->subDays(13)->startOfDay()->toDateString();

        $pagosPorDiaRaw = (clone $qPagos)
            ->whereDate($pagoFechaCol, '>=', $desde14)
            ->selectRaw("DATE({$pagoFechaCol}) as fecha, SUM({$pagoMontoCol}) as total")
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->pluck('total', 'fecha')
            ->all();

        $pagosPorDiaLabels = [];
        $pagosPorDiaData   = [];

        for ($i = 13; $i >= 0; $i--) {
            $dIso = now()->subDays($i)->toDateString();
            $pagosPorDiaLabels[] = Carbon::parse($dIso)->format('d/m');
            $pagosPorDiaData[]   = (int) ($pagosPorDiaRaw[$dIso] ?? 0);
        }

        // ==========================================================
        // 5) Top clínicas por pendiente (Gs)
        // ==========================================================
        $topPendienteClinicas = [];
        if (Schema::hasTable((new Clinica())->getTable()) && $liqClinicaCol) {
            $clinicaTable = (new Clinica())->getTable();

            $topPendienteClinicas = DB::table("{$liqTable} as pl")
                ->join("{$clinicaTable} as c", 'c.id', '=', "pl.{$liqClinicaCol}")
                ->when($liqEstadoCol, fn ($q) => $q->where("pl.{$liqEstadoCol}", 'confirmada'))
                ->when($clinicaId, fn ($q) => $q->where("pl.{$liqClinicaCol}", $clinicaId))
                ->selectRaw("
                    c.id as clinica_id,
                    c.nombre as clinica,
                    SUM(GREATEST(0, (pl.{$liqTotalCol} - COALESCE(pl.{$liqPagadoCol},0)))) as pendiente
                ")
                ->groupBy('c.id', 'c.nombre')
                ->orderByDesc('pendiente')
                ->limit(6)
                ->get()
                ->map(fn ($r) => [
                    'clinica_id' => (int) $r->clinica_id,
                    'clinica'    => (string) $r->clinica,
                    'pendiente'  => (int) $r->pendiente,
                ])
                ->all();
        }

        // ==========================================================
        // 6) Últimos pagos
        // ==========================================================
        $ultimosPagos = (clone $qPagos)
            ->with(['clinica', 'user'])
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // ==========================================================
        // 7) NUEVO: pedidos pendientes de liquidación
        // terminado y (sin liquidación o última != confirmada)
        // ==========================================================
        $estadosParaLiquidar = ['finalizado', 'realizado', 'entregado', 'terminado'];

        // Subquery: última liquidación por pedido (NO usar ?? dentro del string)
        $liqLatest = DB::table($liqTable)
            ->selectRaw("{$liqPedidoCol} as pedido_id, MAX({$liqPkCol}) as liq_id")
            ->groupBy($liqPedidoCol);

        $qPedidosPendLiq = Pedido::query()
            ->from($pedidoTable)
            ->leftJoinSub($liqLatest, 'l', function ($j) use ($pedidoTable) {
                $j->on('l.pedido_id', '=', "{$pedidoTable}.id");
            })
            ->leftJoin("{$liqTable} as pl", 'pl.id', '=', 'l.liq_id')
            ->when($clinicaId && $pedidoClinicaCol, function ($q) use ($clinicaId, $pedidoTable, $pedidoClinicaCol) {
                $q->where("{$pedidoTable}.{$pedidoClinicaCol}", $clinicaId);
            })
            ->when($pedidoEstadoCol, function ($q) use ($pedidoTable, $pedidoEstadoCol, $estadosParaLiquidar) {
                $q->whereIn("{$pedidoTable}.{$pedidoEstadoCol}", $estadosParaLiquidar);
            })
            ->where(function ($w) use ($liqEstadoCol) {
                $w->whereNull('l.liq_id');
                if ($liqEstadoCol) {
                    $w->orWhere("pl.{$liqEstadoCol}", '!=', 'confirmada');
                }
            })
            ->select("{$pedidoTable}.*")
            ->with(['clinica', 'paciente']);

        $pedidosPendientesLiquidacion = (clone $qPedidosPendLiq)
            ->orderByDesc("{$pedidoTable}.id")
            ->limit(10)
            ->get();

        $pedidosPendientesLiquidacionCount = (int) (clone $qPedidosPendLiq)->count();

        return view('admin.cajero.dashboard', compact(
            'pagadoHoy',
            'pagadoMes',
            'pendientePagoTotal',
            'liqPendientesCount',
            'pagosACuentaTotal',
            'pagosACuentaCount',
            'pagosPorDiaLabels',
            'pagosPorDiaData',
            'topPendienteClinicas',
            'ultimosPagos',
            'clinicaId',

            // pedidos por liquidar
            'pedidosPendientesLiquidacionCount',
            'pedidosPendientesLiquidacion'
        ));
    }

    private function resolveColumn(string $table, array $candidates, string $label): string
    {
        foreach ($candidates as $col) {
            if (Schema::hasColumn($table, $col)) {
                return $col;
            }
        }

        throw new \RuntimeException(
            "CajeroDashboardController: No se encontró columna para {$label} en la tabla '{$table}'. " .
            "Probé: " . implode(', ', $candidates)
        );
    }
}
