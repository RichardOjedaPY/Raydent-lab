<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinica;
use App\Models\PedidoLiquidacion;
use App\Models\Pago;
use App\Models\PagoAplicacion;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EstadoCuentaController extends Controller
{
    public function __construct()
    {
        // ✅ Permiso del módulo Estado de cuenta
        $this->middleware('permission:estado_cuenta.view')->only(['index']);
    }

    public function index(Request $r)
    {
        /**
         * ==========================================================
         * 0) Seguridad / Multi-tenant (NO romper por URL manipulada)
         * ==========================================================
         * - Clinica: SIEMPRE su clinica_id
         * - Admin/Cajero: pueden ver todas o filtrar por clinica_id
         */
        $user     = $r->user();
        $isAdmin  = $user?->hasRole('admin') ?? false;
        $isCajero = $user?->hasRole('cajero') ?? false;
        $isClinica = $user?->hasRole('clinica') ?? false;

        $canChooseClinica = $isAdmin || $isCajero;

        // Filtros base
        $desde = $r->get('desde'); // YYYY-MM-DD
        $hasta = $r->get('hasta'); // YYYY-MM-DD
        $tab   = (string) $r->get('tab', 'pendientes'); // pendientes | pagados | pagos

        // ✅ Soporte opcional para "Ver pagos" por fila de liquidación
        $liquidacionId = (int) $r->get('liquidacion_id', 0);

        // ✅ clinicaId efectivo (blindado)
        if ($canChooseClinica) {
            $clinicaId = (int) $r->get('clinica_id', 0); // 0 = Todas
        } else {
            $clinicaId = (int) ($user->clinica_id ?? 0);
            if ($isClinica && $clinicaId <= 0) {
                abort(403, 'Usuario clínica sin clínica asignada.');
            }
        }

        /**
         * ==========================================================
         * 1) Detectar nombres reales de columnas (NO romper por FK distintos)
         * ==========================================================
         */
        $paModel = new PagoAplicacion();
        $paTable = $paModel->getTable(); // normalmente: pago_aplicaciones

        $paPagoFk = $this->resolveColumn($paTable, [
            'pago_id',
            'pagos_id',
        ], 'FK pago');

        $paLiqFk  = $this->resolveColumn($paTable, [
            'pedido_liquidacion_id',
            'liquidacion_id',
            'pedido_liquidaciones_id',
        ], 'FK liquidación');

        $paMontoCol = $this->resolveColumn($paTable, [
            'monto_gs',
            'monto',
            'importe_gs',
        ], 'monto');

        // Tabla pagos: total del pago
        $pagoTable = (new Pago())->getTable(); // normalmente: pagos
        $pagoTotalCol = $this->resolveColumn($pagoTable, [
            'total_gs',
            'monto_total_gs',
            'monto_gs',
        ], 'total pago');

        // Tabla liquidaciones: total liquidado + fecha
        $liqTable = (new PedidoLiquidacion())->getTable(); // normalmente: pedido_liquidaciones
        $liqTotalCol = $this->resolveColumn($liqTable, [
            'total_gs',
            'total',
        ], 'total liquidación');

        $liqFechaCol = $this->resolveColumn($liqTable, [
            'liquidado_at',
            'created_at',
        ], 'fecha liquidación');

        /**
         * ==========================================================
         * 2) Clínicas para el selector
         * ==========================================================
         * - Admin/Cajero: todas
         * - Clinica: solo su clínica
         */
        $clinicas = Clinica::query()
            ->where('is_active', true)
            ->when(!$canChooseClinica && $clinicaId > 0, fn($q) => $q->where('id', $clinicaId))
            ->orderBy('nombre')
            ->get();

        /**
         * ==========================================================
         * 3) Subquery: total aplicado por liquidación
         * ==========================================================
         */
        $aplicadoPorLiq = DB::table($paTable)
            ->select([
                DB::raw("{$paLiqFk} as pedido_liquidacion_id"),
                DB::raw("SUM({$paMontoCol}) as aplicado_gs"),
            ])
            ->groupBy($paLiqFk);

        /**
         * ==========================================================
         * 4) Query base de liquidaciones (con aplicado + saldo)
         * ==========================================================
         */
        $liqQ = PedidoLiquidacion::query()
            ->from("{$liqTable} as pl")
            ->leftJoinSub($aplicadoPorLiq, 'ap', function ($j) {
                $j->on('ap.pedido_liquidacion_id', '=', 'pl.id');
            })
            ->select([
                'pl.*',
                DB::raw('COALESCE(ap.aplicado_gs,0) as aplicado_gs'),
                DB::raw("GREATEST(0, (pl.{$liqTotalCol} - COALESCE(ap.aplicado_gs,0))) as saldo_gs"),
            ])
            ->where('pl.estado', 'confirmada')
            ->when($clinicaId > 0, fn($q) => $q->where('pl.clinica_id', $clinicaId))
            ->when($desde, fn($q) => $q->whereDate("pl.{$liqFechaCol}", '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate("pl.{$liqFechaCol}", '<=', $hasta));

        /**
         * ==========================================================
         * 5) Totales (resumen)
         * ==========================================================
         */
        $totalLiquidado = (int) (clone $liqQ)->sum(DB::raw("pl.{$liqTotalCol}"));
        $totalPagado    = (int) (clone $liqQ)->sum(DB::raw('COALESCE(ap.aplicado_gs,0)'));
        $saldoPendiente = (int) (clone $liqQ)->sum(DB::raw("GREATEST(0, (pl.{$liqTotalCol} - COALESCE(ap.aplicado_gs,0)))"));

        /**
         * ==========================================================
         * 6) Listados: Pendientes / Pagados
         * ==========================================================
         */
        $liquidaciones = (clone $liqQ)
            ->when($tab === 'pendientes', fn($q) => $q->having('saldo_gs', '>', 0))
            ->when($tab === 'pagados', fn($q) => $q->having('saldo_gs', '=', 0))
            ->orderByDesc("pl.{$liqFechaCol}")
            ->paginate(20)
            ->withQueryString();

        /**
         * ==========================================================
         * 7) Pagos + saldo a favor
         * ==========================================================
         * saldo_a_favor = pago.total - SUM(aplicaciones)
         */
        $aplicadoPorPago = DB::table($paTable)
            ->select([
                DB::raw("{$paPagoFk} as pago_id"),
                DB::raw("SUM({$paMontoCol}) as aplicado_gs"),
            ])
            ->groupBy($paPagoFk);

        $pagosQ = Pago::query()
            ->from("{$pagoTable} as p")
            ->leftJoinSub($aplicadoPorPago, 'ap2', function ($j) {
                $j->on('ap2.pago_id', '=', 'p.id');
            })
            ->when($clinicaId > 0, function ($q) use ($clinicaId, $paTable, $paPagoFk, $paLiqFk, $liqTable) {
                $q->whereExists(function ($w) use ($clinicaId, $paTable, $paPagoFk, $paLiqFk, $liqTable) {
                    $w->select(DB::raw(1))
                        ->from("{$paTable} as pa")
                        ->join("{$liqTable} as pl", 'pl.id', '=', "pa.{$paLiqFk}")
                        ->whereColumn("pa.{$paPagoFk}", 'p.id')
                        ->where('pl.clinica_id', $clinicaId);
                });
            })
            ->when($liquidacionId > 0, function ($q) use ($liquidacionId, $paTable, $paPagoFk, $paLiqFk) {
                $q->whereExists(function ($w) use ($liquidacionId, $paTable, $paPagoFk, $paLiqFk) {
                    $w->select(DB::raw(1))
                        ->from("{$paTable} as pa")
                        ->whereColumn("pa.{$paPagoFk}", 'p.id')
                        ->where("pa.{$paLiqFk}", $liquidacionId);
                });
            })
            ->when($desde, fn($q) => $q->whereDate('p.created_at', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('p.created_at', '<=', $hasta))
            ->select([
                'p.*',
                DB::raw('COALESCE(ap2.aplicado_gs,0) as aplicado_gs'),
                DB::raw("GREATEST(0, (p.{$pagoTotalCol} - COALESCE(ap2.aplicado_gs,0))) as saldo_a_favor_gs"),
            ])
            ->orderByDesc('p.id');

        $pagos = (clone $pagosQ)->paginate(20)->withQueryString();

        $pagosACuenta = (int) (clone $pagosQ)->sum(
            DB::raw("GREATEST(0, (p.{$pagoTotalCol} - COALESCE(ap2.aplicado_gs,0)))")
        );

        // 🧾 AUDIT: vio estado de cuenta (con filtros/roles/totales)
        Audit::log('estado_cuenta', 'view', 'Vio estado de cuenta', null, [
            'rol_admin'   => (bool) $isAdmin,
            'rol_cajero'  => (bool) $isCajero,
            'rol_clinica' => (bool) $isClinica,

            'clinica_id'         => (int) $clinicaId, // 0 = todas
            'can_choose_clinica' => (bool) $canChooseClinica,

            'desde' => $desde ?: null,
            'hasta' => $hasta ?: null,
            'tab'   => $tab ?: null,

            'liquidacion_id' => (int) $liquidacionId,

            'total_liquidado_gs' => (int) $totalLiquidado,
            'total_pagado_gs'    => (int) $totalPagado,
            'saldo_pendiente_gs' => (int) $saldoPendiente,
            'pagos_a_cuenta_gs'  => (int) $pagosACuenta,

            'liquidaciones_page'      => (int) $liquidaciones->currentPage(),
            'liquidaciones_per_page'  => (int) $liquidaciones->perPage(),
            'liquidaciones_total'     => (int) $liquidaciones->total(),

            'pagos_page'      => (int) $pagos->currentPage(),
            'pagos_per_page'  => (int) $pagos->perPage(),
            'pagos_total'     => (int) $pagos->total(),
        ]);

        return view('admin.estado_cuenta.index', compact(
            'clinicas',
            'clinicaId',
            'desde',
            'hasta',
            'tab',
            'totalLiquidado',
            'totalPagado',
            'saldoPendiente',
            'pagosACuenta',
            'liquidaciones',
            'pagos'
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
            "EstadoCuentaController: No se encontró columna para {$label} en la tabla '{$table}'. " .
            "Probé: " . implode(', ', $candidates)
        );
    }
}
