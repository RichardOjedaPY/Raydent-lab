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
        $pagoTable = (new Pago())->getTable(); // pagos
        $pagoMontoCol  = $this->resolveColumn($pagoTable, ['monto_gs', 'total_gs', 'monto', 'importe_gs'], 'monto pago');
        $pagoFechaCol  = $this->resolveColumn($pagoTable, ['fecha', 'created_at'], 'fecha pago');
        $pagoClinicaCol = Schema::hasColumn($pagoTable, 'clinica_id') ? 'clinica_id' : null;

        $paTable = (new PagoAplicacion())->getTable(); // pago_aplicaciones
        $paPagoFk   = $this->resolveColumn($paTable, ['pago_id', 'pagos_id'], 'FK pago');
        $paMontoCol = $this->resolveColumn($paTable, ['monto_gs', 'monto', 'importe_gs'], 'monto aplicación');

        // FK a liquidación (si existe en pago_aplicaciones)
        $paLiqFk = null;
        foreach (['pedido_liquidacion_id', 'liquidacion_id', 'pedido_liquidaciones_id'] as $c) {
            if (Schema::hasColumn($paTable, $c)) { $paLiqFk = $c; break; }
        }

        // Liquidaciones
        $liqTable = (new PedidoLiquidacion())->getTable(); // pedido_liquidaciones
        $liqIdCol     = Schema::hasColumn($liqTable, 'id') ? 'id' : 'id';
        $liqPedidoCol = $this->resolveColumn($liqTable, ['pedido_id'], 'FK pedido en liquidación');
        $liqTotalCol  = $this->resolveColumn($liqTable, ['total_gs', 'total', 'monto_total', 'total_monto'], 'total liquidación');
        $liqEstadoCol = Schema::hasColumn($liqTable, 'estado') ? 'estado' : null;
        $liqClinicaCol = Schema::hasColumn($liqTable, 'clinica_id') ? 'clinica_id' : null;

        // Columna “pagado” si existe (opcional). Si no, lo calculamos desde pago_aplicaciones por liquidación.
        $liqPagadoCol = null;
        foreach (['pagado_gs', 'aplicado_gs', 'pagado', 'aplicado', 'monto_pagado', 'monto_aplicado'] as $c) {
            if (Schema::hasColumn($liqTable, $c)) { $liqPagadoCol = $c; break; }
        }

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
        // pendiente = SUM(GREATEST(0, total - pagado))
        // pagado: preferimos SUM(pago_aplicaciones) por liquidación si hay FK, sino columna en liquidación, sino 0
        // ==========================================================
        $pagadoPorLiq = null;
        if ($paLiqFk) {
            $pagadoPorLiq = DB::table($paTable)
                ->selectRaw("{$paLiqFk} as liq_id, SUM({$paMontoCol}) as pagado_gs")
                ->groupBy($paLiqFk);
        }

        $liqBase = DB::table("{$liqTable} as pl")
            ->when($liqEstadoCol, function ($q) use ($liqEstadoCol) {
                // Ajustá si tu estado válido para liquidaciones es otro
                $q->where("pl.{$liqEstadoCol}", 'confirmada');
            })
            ->when($clinicaId && $liqClinicaCol, function ($q) use ($clinicaId, $liqClinicaCol) {
                $q->where("pl.{$liqClinicaCol}", $clinicaId);
            });

        if ($pagadoPorLiq) {
            $liqBase->leftJoinSub($pagadoPorLiq, 'pl_pagado', function ($j) {
                $j->on('pl_pagado.liq_id', '=', 'pl.id');
            });
        }

        $pagadoExpr = '0';
        if ($liqPagadoCol) {
            $pagadoExpr = "COALESCE(pl.{$liqPagadoCol},0)";
        } elseif ($pagadoPorLiq) {
            $pagadoExpr = "COALESCE(pl_pagado.pagado_gs,0)";
        }

        $pendientePagoTotal = (int) (clone $liqBase)
            ->selectRaw("SUM(GREATEST(0, (pl.{$liqTotalCol} - {$pagadoExpr}))) as pendiente")
            ->value('pendiente');

        $liqPendientesCount = (int) (clone $liqBase)
            ->whereRaw("GREATEST(0, (pl.{$liqTotalCol} - {$pagadoExpr})) > 0")
            ->count();

        // ==========================================================
        // 3) Pagos a cuenta (saldo a favor)
        // saldo_a_favor = pago.monto - SUM(aplicaciones por pago_id)
        // IMPORTANTE: hacerlo en una query "solo aggregate" para evitar error de GROUP.
        // ==========================================================
        $aplicadoPorPago = DB::table($paTable)
            ->selectRaw("{$paPagoFk} as pago_id, SUM({$paMontoCol}) as aplicado_gs")
            ->groupBy($paPagoFk);

        $pagosACuentaTotal = (int) DB::table("{$pagoTable} as p")
            ->leftJoinSub($aplicadoPorPago, 'ap', function ($j) {
                $j->on('ap.pago_id', '=', 'p.id');
            })
            ->when($clinicaId && $pagoClinicaCol, function ($q) use ($clinicaId, $pagoClinicaCol) {
                $q->where("p.{$pagoClinicaCol}", $clinicaId);
            })
            ->selectRaw("SUM(GREATEST(0, (p.{$pagoMontoCol} - COALESCE(ap.aplicado_gs,0)))) as total")
            ->value('total');

        // ==========================================================
        // 4) Serie Pagos últimos 14 días (para gráfico)
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
        // 5) Pendiente por clínica (Top 6)
        // ==========================================================
        $topPendienteClinicas = [];
        if (Schema::hasTable((new Clinica())->getTable()) && $liqClinicaCol) {
            $clinicaTable = (new Clinica())->getTable();

            $qTop = DB::table("{$liqTable} as pl")
                ->join("{$clinicaTable} as c", 'c.id', '=', "pl.{$liqClinicaCol}")
                ->when($liqEstadoCol, function ($q) use ($liqEstadoCol) {
                    $q->where("pl.{$liqEstadoCol}", 'confirmada');
                })
                ->when($clinicaId, function ($q) use ($clinicaId, $liqClinicaCol) {
                    $q->where("pl.{$liqClinicaCol}", $clinicaId);
                });

            if ($pagadoPorLiq) {
                $qTop->leftJoinSub($pagadoPorLiq, 'pl_pagado', function ($j) {
                    $j->on('pl_pagado.liq_id', '=', 'pl.id');
                });
            }

            $pagadoExprTop = '0';
            if ($liqPagadoCol) {
                $pagadoExprTop = "COALESCE(pl.{$liqPagadoCol},0)";
            } elseif ($pagadoPorLiq) {
                $pagadoExprTop = "COALESCE(pl_pagado.pagado_gs,0)";
            }

            $topPendienteClinicas = $qTop
                ->selectRaw("
                    c.nombre as clinica,
                    SUM(GREATEST(0, (pl.{$liqTotalCol} - {$pagadoExprTop}))) as pendiente
                ")
                ->groupBy('c.nombre')
                ->orderByDesc('pendiente')
                ->limit(6)
                ->get()
                ->map(function ($r) {
                    return [
                        'clinica'   => (string) $r->clinica,
                        'pendiente' => (int) $r->pendiente,
                    ];
                })
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
        // 7) ✅ Pedidos pendientes de liquidar
        // Regla: pedido que NO tiene registro en pedido_liquidaciones para ese pedido_id.
        // (esto es lo que necesitás para que apenas se cree un pedido aparezca acá)
        // ==========================================================
        $pedidoTable = (new Pedido())->getTable(); // pedidos

        $qPendientesLiquidar = Pedido::query()
            ->from("{$pedidoTable} as pe")
            ->with(['clinica', 'paciente'])
            ->when($clinicaId, function ($q) use ($clinicaId) {
                $q->where('pe.clinica_id', $clinicaId);
            })
            // opcional: excluir anulados/cancelados si existieran en tu sistema
            ->whereNotIn('pe.estado', ['anulado', 'cancelado'])
            ->whereNotExists(function ($sub) use ($liqTable, $liqPedidoCol) {
                $sub->select(DB::raw(1))
                    ->from($liqTable)
                    ->whereColumn("{$liqTable}.{$liqPedidoCol}", 'pe.id');
            })
            ->orderByDesc('pe.id');

        $pedidosPendientesLiquidacionCount = (int) (clone $qPendientesLiquidar)->count();

        $pedidosPendientesLiquidacion = (clone $qPendientesLiquidar)
            ->limit(10)
            ->get();

        return view('admin.cajero.dashboard', compact(
            'pagadoHoy',
            'pagadoMes',
            'pendientePagoTotal',
            'liqPendientesCount',
            'pagosACuentaTotal',
            'pagosPorDiaLabels',
            'pagosPorDiaData',
            'topPendienteClinicas',
            'ultimosPagos',
            'clinicaId',
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
