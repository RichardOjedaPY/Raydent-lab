<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinica;
use App\Models\PedidoLiquidacion;
use App\Models\Pago;
use App\Models\PagoAplicacion;
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
         * 0) Detectar nombres reales de columnas (NO romper por FK distintos)
         * ==========================================================
         * Tu error venía de: "Unknown column 'pedido_liquidacion_id' ..."
         * Eso indica que en tu tabla `pago_aplicaciones` el FK se llama distinto
         * (ej: `liquidacion_id`, `pedido_liquidaciones_id`, etc.).
         *
         * Este controlador resuelve automáticamente los nombres correctos.
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
         * 1) Filtros
         * ==========================================================
         */
        $clinicaId = (int) $r->get('clinica_id', 0);
        $desde     = $r->get('desde'); // YYYY-MM-DD
        $hasta     = $r->get('hasta'); // YYYY-MM-DD
        $tab       = $r->get('tab', 'pendientes'); // pendientes | pagados | pagos

        // Clínicas para el selector
        $clinicas = Clinica::query()
            ->where('is_active', true)
            ->orderBy('nombre')
            ->get();

        /**
         * ==========================================================
         * 2) Subquery: total aplicado por liquidación
         * ==========================================================
         * SELECT <liq_fk>, SUM(<monto>) as aplicado_gs FROM pago_aplicaciones GROUP BY <liq_fk>
         */
        $aplicadoPorLiq = DB::table($paTable)
            ->select([
                DB::raw("{$paLiqFk} as pedido_liquidacion_id"),
                DB::raw("SUM({$paMontoCol}) as aplicado_gs"),
            ])
            ->groupBy($paLiqFk);

        /**
         * ==========================================================
         * 3) Query base de liquidaciones (con aplicado + saldo)
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
         * 4) Totales (resumen)
         * ==========================================================
         */
        $totalLiquidado = (int) (clone $liqQ)->sum(DB::raw("pl.{$liqTotalCol}"));
        $totalPagado    = (int) (clone $liqQ)->sum(DB::raw('COALESCE(ap.aplicado_gs,0)'));
        $saldoPendiente = (int) (clone $liqQ)->sum(DB::raw("GREATEST(0, (pl.{$liqTotalCol} - COALESCE(ap.aplicado_gs,0)))"));

        /**
         * ==========================================================
         * 5) Listados: Pendientes / Pagados (drill-down por liquidación)
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
         * 6) Pagos + saldo a favor (pago a cuenta)
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
                // Pagos vinculados a la clínica por aplicaciones -> liquidaciones
                $q->whereExists(function ($w) use ($clinicaId, $paTable, $paPagoFk, $paLiqFk, $liqTable) {
                    $w->select(DB::raw(1))
                        ->from("{$paTable} as pa")
                        ->join("{$liqTable} as pl", 'pl.id', '=', "pa.{$paLiqFk}")
                        ->whereColumn("pa.{$paPagoFk}", 'p.id')
                        ->where('pl.clinica_id', $clinicaId);
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

        $pagosACuenta = (int) (clone $pagosQ)->sum(DB::raw("GREATEST(0, (p.{$pagoTotalCol} - COALESCE(ap2.aplicado_gs,0)))"));

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

    /**
     * Resuelve el nombre real de una columna en una tabla.
     * - Prueba una lista de candidatos hasta encontrar uno existente.
     * - Si no encuentra, lanza excepción con mensaje claro.
     */
    private function resolveColumn(string $table, array $candidates, string $label): string
    {
        foreach ($candidates as $col) {
            if (Schema::hasColumn($table, $col)) {
                return $col;
            }
        }

        // Mensaje explícito para que lo veas al instante en caso de faltar columnas
        throw new \RuntimeException(
            "EstadoCuentaController: No se encontró columna para {$label} en la tabla '{$table}'. " .
            "Probé: " . implode(', ', $candidates)
        );
    }
}
