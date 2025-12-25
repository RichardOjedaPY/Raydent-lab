<?php

namespace App\Http\Controllers\Admin\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Clinica;
use App\Models\Paciente;
use App\Models\Pedido;
use App\Models\PedidoLiquidacion;
use App\Models\PagoAplicacion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClinicaDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // IMPORTANTE: asumimos que el user tiene clinica_id
        $clinicaId = $user->clinica_id ?? null;

        abort_if(! $clinicaId, 403, 'Usuario sin clínica asignada.');

        $clinica = Clinica::findOrFail($clinicaId);

        $pacientesTotal = Paciente::where('clinica_id', $clinicaId)->count();

        $pedidosTotal = Pedido::where('clinica_id', $clinicaId)->count();

        // "Pendientes" = pendiente + en_proceso
        $pedidosPendientes = Pedido::where('clinica_id', $clinicaId)
            ->whereIn('estado', ['pendiente', 'en_proceso'])
            ->count();

        // "Finalizados" = realizado + entregado (ajustá si tu regla es otra)
        $pedidosFinalizados = Pedido::where('clinica_id', $clinicaId)
            ->whereIn('estado', ['realizado', 'entregado'])
            ->count();

        $ultimosPedidos = Pedido::with(['paciente'])
            ->where('clinica_id', $clinicaId)
            ->latest('id')
            ->limit(10)
            ->get();

        /**
         * ==========================================================
         * ✅ NUEVO: Pagos pendientes (saldo pendiente) para la clínica
         * - saldo = GREATEST(0, total_liquidado - aplicado)
         * - aplicado = SUM(monto_gs) en pago_aplicaciones agrupado por liquidación
         * - Robusto: detecta columnas reales (saldo_gs puede no existir)
         * ==========================================================
         */

        $saldoPendienteGs = 0;
        $liquidacionesPendientesCount = 0;

        try {
            $liqTable = (new PedidoLiquidacion())->getTable();  // normalmente: pedido_liquidaciones
            $paTable  = (new PagoAplicacion())->getTable();     // normalmente: pago_aplicaciones

            // Helpers para columnas
            $pickCol = function (string $table, array $candidates): ?string {
                foreach ($candidates as $col) {
                    if (Schema::hasColumn($table, $col)) return $col;
                }
                return null;
            };

            // pago_aplicaciones
            $paLiqFk = $pickCol($paTable, [
                'pedido_liquidacion_id',
                'liquidacion_id',
                'pedido_liquidaciones_id',
            ]);

            $paMontoCol = $pickCol($paTable, [
                'monto_gs',
                'monto',
                'importe_gs',
            ]);

            // liquidaciones
            $liqTotalCol  = $pickCol($liqTable, ['total_gs', 'total']);
            $liqEstadoCol = $pickCol($liqTable, ['estado', 'status']);
            $liqClinicaCol = $pickCol($liqTable, ['clinica_id']);
            $liqPedidoCol  = $pickCol($liqTable, ['pedido_id']);

            // Solo si podemos calcular saldo (necesitamos total y estructura mínima)
            if ($liqTotalCol && $paLiqFk && $paMontoCol) {

                // Subquery: aplicado por liquidación
                $aplicadoPorLiq = DB::table($paTable)
                    ->selectRaw("{$paLiqFk} as liquidacion_id, SUM({$paMontoCol}) as aplicado_gs")
                    ->groupBy($paLiqFk);

                // Query base liquidaciones
                $q = DB::table("{$liqTable} as pl")
                    ->leftJoinSub($aplicadoPorLiq, 'ap', function ($j) {
                        $j->on('ap.liquidacion_id', '=', 'pl.id');
                    });

                // Filtrar por clínica:
                // 1) Si pl tiene clinica_id, directo
                if ($liqClinicaCol) {
                    $q->where("pl.{$liqClinicaCol}", $clinicaId);
                }
                // 2) Si no tiene clinica_id, intentamos por join con pedidos
                elseif ($liqPedidoCol && Schema::hasTable('pedidos') && Schema::hasColumn('pedidos', 'clinica_id')) {
                    $q->join('pedidos as ped', 'ped.id', '=', "pl.{$liqPedidoCol}")
                      ->where('ped.clinica_id', $clinicaId);
                } else {
                    // No se puede asegurar multi-tenant -> no arriesgar
                    $q = null;
                }

                if ($q) {
                    // Solo confirmadas si existe estado (mantiene tu lógica de estado de cuenta)
                    if ($liqEstadoCol) {
                        $q->where("pl.{$liqEstadoCol}", 'confirmada');
                    }

                    $saldoExpr = "GREATEST(0, (pl.{$liqTotalCol} - COALESCE(ap.aplicado_gs,0)))";

                    $saldoPendienteGs = (int) $q->sum(DB::raw($saldoExpr));

                    // Cantidad de liquidaciones con saldo > 0
                    $liquidacionesPendientesCount = (int) (clone $q)
                        ->whereRaw("{$saldoExpr} > 0")
                        ->count();
                }
            }
        } catch (\Throwable $e) {
            // No rompemos el dashboard si falta alguna columna/tabla en un entorno
            $saldoPendienteGs = 0;
            $liquidacionesPendientesCount = 0;
        }

        return view('admin.clinicas.dashboard', compact(
            'clinica',
            'pacientesTotal',
            'pedidosPendientes',
            'pedidosFinalizados',
            'pedidosTotal',
            'ultimosPedidos',
        
            'saldoPendienteGs',
            'liquidacionesPendientesCount'
        ));
    }
}
