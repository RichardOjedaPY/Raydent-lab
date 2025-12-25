<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Pago, PagoAplicacion, Liquidacion};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{PedidoLiquidacion};
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Clinica;
use App\Support\Audit;
use Illuminate\Support\Facades\Auth;

class PagoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:pagos.view')->only(['show']);
        $this->middleware('permission:pagos.create')->only(['createMultiple', 'storeMultiple', 'storePagoIndividual']);
        $this->middleware('permission:pagos.pdf')->only(['pdfRecibo']);
        $this->middleware('permission:pagos.delete')->only(['destroy']);
    }

    private function gsToInt(mixed $v): int
    {
        // soporta "1.200.000" o "1200000"
        $s = (string) $v;
        $s = preg_replace('/[^\d]/', '', $s) ?? '0';
        return (int) $s;
    }

    public function storePagoIndividual(Request $r, PedidoLiquidacion $liquidacion)
    {
        $user = $r->user();

        $data = $r->validate([
            'fecha'       => ['required', 'date'],
            'metodo'      => ['required', 'string', 'max:30'],
            'monto_gs'    => ['required'],
            'referencia'  => ['nullable', 'string', 'max:120'],
            'observacion' => ['nullable', 'string'],
        ]);

        $montoGs = (int) preg_replace('/[^\d]/', '', (string)$data['monto_gs']);
        if ($montoGs <= 0) {
            return back()->withErrors(['monto_gs' => 'El monto debe ser mayor a 0.'])->withInput();
        }

        $pagadoGs = (int) $liquidacion->aplicaciones()->sum('monto_gs');
        $totalGs  = (int) ($liquidacion->total_gs ?? 0);
        $saldoGs  = max(0, $totalGs - $pagadoGs);

        // ✅ parcial: aplica lo que corresponda hasta el saldo
        $aplicarGs = min($montoGs, $saldoGs);

        $pago = DB::transaction(function () use ($liquidacion, $user, $data, $montoGs, $saldoGs, $aplicarGs) {

            $pago = Pago::create([
                'clinica_id'     => $liquidacion->clinica_id,
                'fecha'          => $data['fecha'],
                'metodo'         => $data['metodo'],
                'monto_gs'       => $montoGs,
                'referencia'     => $data['referencia'] ?? null,
                'observacion'    => $data['observacion'] ?? null,
                'user_id'        => $user->id,
                'caja_sesion_id' => null,
            ]);

            if ($aplicarGs > 0) {
                // 1) Crear aplicación (UNA sola vez)
                PagoAplicacion::create([
                    'pago_id'        => $pago->id,
                    'liquidacion_id' => $liquidacion->id,
                    'monto_gs'       => (int) $aplicarGs,
                ]);

                // 2) Mantener pagado_gs sincronizado
                $liquidacion->pagado_gs = (int) ($liquidacion->pagado_gs ?? 0) + (int) $aplicarGs;
                $liquidacion->save();
            }

            // ✅ si sobra, queda como “pago a cuenta” (sin aplicación)
            return $pago;
        });

        // 🧾 AUDIT: pago individual (creación + aplicación parcial/total)
        Audit::log('pagos', 'created_individual', 'Pago individual registrado', $pago, [
            'pago_id'            => $pago->id,
            'clinica_id'         => (int) $pago->clinica_id,
            'liquidacion_id'     => (int) $liquidacion->id,
            'pedido_id'          => (int) ($liquidacion->pedido_id ?? 0),
            'metodo'             => (string) $pago->metodo,
            'monto_gs'           => (int) $montoGs,
            'saldo_antes_gs'     => (int) $saldoGs,
            'aplicado_gs'        => (int) $aplicarGs,
            'saldo_despues_gs'   => (int) max(0, $saldoGs - $aplicarGs),
            'a_cuenta_gs'        => (int) max(0, $montoGs - $aplicarGs),
        ]);

        return redirect()
            ->route('admin.pagos.show', $pago)
            ->with('ok', 'Pago registrado correctamente.');
    }

    public function show(Pago $pago)
    {
        $user = auth()->user();

        $isAdmin   = $user->hasRole('admin');
        $isCajero  = $user->hasRole('cajero');
        $isClinica = $user->hasRole('clinica');

        $pago->load([
            'clinica',
            'user',
            'aplicaciones.liquidacion.pedido.paciente',
            'aplicaciones.liquidacion.pedido.clinica',
        ]);

        // ------------------------------------------------------------
        // 1) Resolver ID de liquidación + FK real (liquidacion_id / pedido_liquidacion_id)
        // ------------------------------------------------------------
        $ap0 = $pago->aplicaciones->first();

        $liqFk = null;
        $liqId = null;

        if ($ap0) {
            if ($ap0->relationLoaded('liquidacion') && $ap0->liquidacion) {
                $liqId = (int) $ap0->liquidacion->id;
            }

            foreach (['liquidacion_id', 'pedido_liquidacion_id', 'pedido_liquidaciones_id'] as $cand) {
                if (!is_null($ap0->{$cand} ?? null)) {
                    $liqFk = $cand;
                    $liqId = $liqId ?: (int) $ap0->{$cand};
                    break;
                }
            }
        }

        // ------------------------------------------------------------
        // 2) Cargar liquidación/pedido (contexto)
        // ------------------------------------------------------------
        $liq = null;
        $pedido = null;

        if ($liqId) {
            $liq = PedidoLiquidacion::query()
                ->with(['pedido.clinica', 'pedido.paciente'])
                ->find($liqId);

            $pedido = $liq?->pedido;
        }

        // ------------------------------------------------------------
        // 3) 🔒 Multi-tenant: clínica solo puede ver pagos de su clínica
        // ------------------------------------------------------------
        if ($isClinica && !$isAdmin && !$isCajero) {
            $userClinicaId = (int) ($user->clinica_id ?? 0);
            if ($userClinicaId <= 0) {
                abort(403, 'Usuario clínica sin clínica asignada.');
            }

            $pagoClinicaId = null;

            if ($liq && (int)($liq->clinica_id ?? 0) > 0) {
                $pagoClinicaId = (int) $liq->clinica_id;
            } elseif ($pedido && (int)($pedido->clinica_id ?? 0) > 0) {
                $pagoClinicaId = (int) $pedido->clinica_id;
            } else {
                $pagoClinicaId = (int) ($pago->clinica_id ?? 0);
            }

            if ($pagoClinicaId <= 0) {
                abort(403, 'No se pudo determinar la clínica de este pago.');
            }

            if ($pagoClinicaId !== $userClinicaId) {
                abort(403, 'Acceso denegado: pago no pertenece a su clínica.');
            }

            $clinicasInvolucradas = $pago->aplicaciones
                ->map(function ($ap) {
                    return (int) optional(optional($ap->liquidacion)->pedido)->clinica_id
                        ?: (int) optional($ap->liquidacion)->clinica_id
                        ?: 0;
                })
                ->filter()
                ->unique()
                ->values();

            if ($clinicasInvolucradas->count() > 1) {
                abort(403, 'Acceso denegado: pago con aplicaciones a múltiples clínicas.');
            }
        }

        // ------------------------------------------------------------
        // 4) Historial: pagos relacionados a la liquidación (si existe)
        // ------------------------------------------------------------
        $pagosRelacionados = collect([$pago]);

        if ($liqId && $liqFk) {
            $pagosRelacionados = Pago::query()
                ->whereHas('aplicaciones', function ($q) use ($liqFk, $liqId) {
                    $q->where($liqFk, $liqId);
                })
                ->with([
                    'clinica',
                    'user',
                    'aplicaciones' => function ($q) use ($liqFk, $liqId) {
                        $q->where($liqFk, $liqId)
                          ->with(['liquidacion.pedido']);
                    },
                ])
                ->withSum(['aplicaciones as aplicado_gs_sum' => function ($q) use ($liqFk, $liqId) {
                    $q->where($liqFk, $liqId);
                }], 'monto_gs')
                ->orderByDesc('id')
                ->get();
        }

        // ------------------------------------------------------------
        // 5) Totales
        // ------------------------------------------------------------
        $aplicadoGs = (int) $pago->aplicaciones->sum('monto_gs');
        $aCuentaGs  = max(0, (int)($pago->monto_gs ?? 0) - $aplicadoGs);

        // 🧾 AUDIT: vio detalle de pago
        Audit::log('pagos', 'view', 'Vio detalle de pago', $pago, [
            'pago_id'        => (int) $pago->id,
            'clinica_id'     => (int) ($pago->clinica_id ?? 0),
            'liquidacion_id' => (int) ($liqId ?? 0),
            'pedido_id'      => (int) ($pedido->id ?? 0),
            'aplicado_gs'    => (int) $aplicadoGs,
            'a_cuenta_gs'    => (int) $aCuentaGs,
            'rol_admin'      => (bool) $isAdmin,
            'rol_cajero'     => (bool) $isCajero,
            'rol_clinica'    => (bool) $isClinica,
        ]);

        return view('admin.pagos.show', compact(
            'pago',
            'aplicadoGs',
            'aCuentaGs',
            'pagosRelacionados',
            'liq',
            'pedido'
        ));
    }

    public function pdfRecibo(Pago $pago)
    {
        $pago->load([
            'clinica',
            'user',
            'aplicaciones.liquidacion.pedido.paciente',
            'aplicaciones.liquidacion.pedido.clinica',
        ]);

        $aplicadoGs = (int) $pago->aplicaciones->sum('monto_gs');
        $aCuentaGs  = max(0, (int)$pago->monto_gs - $aplicadoGs);

        // 🧾 AUDIT: descargó recibo PDF
        Audit::log('pagos', 'pdf', 'Descargó recibo PDF', $pago, [
            'pago_id'     => (int) $pago->id,
            'clinica_id'  => (int) ($pago->clinica_id ?? 0),
            'monto_gs'    => (int) ($pago->monto_gs ?? 0),
            'aplicado_gs' => (int) $aplicadoGs,
            'a_cuenta_gs' => (int) $aCuentaGs,
        ]);

        $pdf = Pdf::loadView('admin.pagos.recibo_pdf', compact('pago', 'aplicadoGs', 'aCuentaGs'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('Recibo_Pago_' . $pago->id . '.pdf');
    }

    /**
     * Pantalla: selección de liquidaciones pendientes + filtros.
     */
    public function createMultiple(Request $request)
    {
        $u = $request->user();

        $clinicaId = (int) $request->get('clinica_id', 0);
        $desde     = trim((string) $request->get('desde', ''));
        $hasta     = trim((string) $request->get('hasta', ''));
        $codigo    = trim((string) $request->get('codigo', ''));

        $clinicas = Clinica::where('is_active', true)
            ->orderBy('nombre')
            ->get();

        $q = PedidoLiquidacion::query()
            ->with(['pedido.clinica', 'pedido.paciente','aplicaciones.pago.user'])
            ->where('estado', 'confirmada')
            ->whereRaw('GREATEST(0, (total_gs - pagado_gs)) > 0');

        if ($clinicaId > 0) {
            $q->where('clinica_id', $clinicaId);
        }

        if ($desde !== '') {
            $q->whereDate('liquidado_at', '>=', $desde);
        }

        if ($hasta !== '') {
            $q->whereDate('liquidado_at', '<=', $hasta);
        }

        if ($codigo !== '') {
            $q->whereHas('pedido', function ($p) use ($codigo) {
                $p->where('codigo_pedido', 'like', "%{$codigo}%")
                    ->orWhere('codigo', 'like', "%{$codigo}%");
            });
        }

        $liquidaciones = $q->orderByDesc('id')->paginate(20)->withQueryString();

        // 🧾 AUDIT: abrió pantalla cobro múltiple
        Audit::log('pagos', 'view_multiple', 'Vio pantalla de cobro múltiple', null, [
            'filtro_clinica_id' => (int) $clinicaId,
            'desde'             => $desde ?: null,
            'hasta'             => $hasta ?: null,
            'codigo'            => $codigo ?: null,
            'page'              => (int) $liquidaciones->currentPage(),
            'per_page'          => (int) $liquidaciones->perPage(),
            'total'             => (int) $liquidaciones->total(),
        ]);

        return view('admin.pagos.multiple', compact(
            'clinicas',
            'liquidaciones',
            'clinicaId',
            'desde',
            'hasta',
            'codigo'
        ));
    }

    public function storeMultiple(Request $request)
    {
        $data = $request->validate([
            'fecha'           => ['required', 'date'],
            'metodo'          => ['required', 'string', 'max:30'],
            'monto_gs'        => ['required'],
            'referencia'      => ['nullable', 'string', 'max:120'],
            'observacion'     => ['nullable', 'string', 'max:255'],

            'liquidaciones'   => ['required', 'array', 'min:1'],
            'liquidaciones.*' => ['integer', 'exists:pedido_liquidaciones,id'],
        ]);

        $montoTotal = $this->parseGs($data['monto_gs']);

        $liqs = PedidoLiquidacion::query()
            ->with(['pedido'])
            ->whereIn('id', $data['liquidaciones'])
            ->lockForUpdate()
            ->get();

        if ($liqs->isEmpty()) {
            return back()->withErrors(['liquidaciones' => 'No se encontraron liquidaciones seleccionadas.'])->withInput();
        }

        $uniqueClinicas = $liqs->pluck('clinica_id')->unique()->values();
        if ($uniqueClinicas->count() !== 1) {
            return back()->withErrors(['liquidaciones' => 'Las liquidaciones seleccionadas deben pertenecer a una sola clínica.'])->withInput();
        }
        $clinicaId = (int) $uniqueClinicas->first();

        if ($montoTotal <= 0) {
            return back()->withErrors(['monto_gs' => 'El monto debe ser mayor a 0.'])->withInput();
        }

        $userId = Auth::id();

        $result = DB::transaction(function () use ($data, $montoTotal, $liqs, $clinicaId, $userId) {

            $pago = Pago::create([
                'clinica_id'      => $clinicaId,
                'fecha'           => $data['fecha'],
                'metodo'          => $data['metodo'],
                'monto_gs'        => $montoTotal,
                'referencia'      => $data['referencia'] ?? null,
                'observacion'     => $data['observacion'] ?? null,
                'user_id'         => $userId,
                'caja_sesion_id'  => null,
            ]);

            $remaining = $montoTotal;
            $aplicadoTotal = 0;

            $detalleAplicaciones = [];

            $liqsSorted = $liqs->sortBy('id')->values();

            foreach ($liqsSorted as $liq) {
                if ($remaining <= 0) break;

                $total  = (int) ($liq->total_gs ?? 0);
                $pagado = (int) ($liq->pagado_gs ?? 0);
                $saldo  = max(0, $total - $pagado);

                if ($saldo <= 0) continue;

                $aplicar = min($saldo, $remaining);
                if ($aplicar <= 0) continue;

                PagoAplicacion::create([
                    'pago_id'        => $pago->id,
                    'liquidacion_id' => $liq->id,
                    'monto_gs'       => (int) $aplicar,
                ]);

                $liq->pagado_gs = (int) ($pagado + $aplicar);
                $liq->save();

                $detalleAplicaciones[] = [
                    'liquidacion_id'   => (int) $liq->id,
                    'pedido_id'        => (int) ($liq->pedido_id ?? 0),
                    'saldo_antes_gs'   => (int) $saldo,
                    'aplicado_gs'      => (int) $aplicar,
                    'saldo_despues_gs' => (int) max(0, $saldo - $aplicar),
                ];

                $remaining     -= $aplicar;
                $aplicadoTotal += $aplicar;
            }

            return [
                'pago'               => $pago,
                'aplicadoTotal'      => $aplicadoTotal,
                'saldoFavor'         => max(0, $montoTotal - $aplicadoTotal),
                'detalleAplicaciones'=> $detalleAplicaciones,
            ];
        });

        // 🧾 AUDIT: pago múltiple
        Audit::log('pagos', 'created_multiple', 'Pago múltiple registrado', $result['pago'], [
            'pago_id'        => (int) $result['pago']->id,
            'clinica_id'     => (int) $result['pago']->clinica_id,
            'metodo'         => (string) $result['pago']->metodo,
            'monto_gs'       => (int) $result['pago']->monto_gs,
            'aplicado_gs'    => (int) $result['aplicadoTotal'],
            'saldo_favor_gs' => (int) $result['saldoFavor'],
            'liquidaciones_seleccionadas' => array_values(array_map('intval', $data['liquidaciones'] ?? [])),
            // Si te preocupa tamaño, podés recortar a 50:
            'detalle_aplicaciones' => array_slice($result['detalleAplicaciones'] ?? [], 0, 50),
        ]);

        return redirect()
            ->route('admin.pagos.show', $result['pago'])
            ->with('success', 'Pago múltiple registrado correctamente.');
    }

    private function parseGs($value): int
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        return (int) ($digits ?: 0);
    }

    public function destroy(Request $request, Pago $pago)
    {
        $pago->load(['aplicaciones']);

        // 📌 Capturar data ANTES de borrar (para auditoría completa)
        $payload = [
            'pago_id'     => (int) $pago->id,
            'clinica_id'  => (int) ($pago->clinica_id ?? 0),
            'monto_gs'    => (int) ($pago->monto_gs ?? 0),
            'deleted_by'  => (int) ($request->user()?->id ?? 0),
            'aplicaciones'=> $pago->aplicaciones->map(function ($ap) {
                return [
                    'liquidacion_id' => (int) ($ap->liquidacion_id ?? 0),
                    'monto_gs'       => (int) ($ap->monto_gs ?? 0),
                ];
            })->values()->all(),
        ];

        DB::transaction(function () use ($pago) {

            $appsByLiq = $pago->aplicaciones->groupBy('liquidacion_id');

            foreach ($appsByLiq as $liqId => $apps) {
                if (! $liqId) continue;

                $sumAplicado = (int) $apps->sum('monto_gs');

                $liq = PedidoLiquidacion::query()
                    ->whereKey($liqId)
                    ->lockForUpdate()
                    ->first();

                if ($liq) {
                    $liq->pagado_gs = max(0, (int)$liq->pagado_gs - $sumAplicado);
                    $liq->save();
                }
            }

            $pago->aplicaciones()->delete();
            $pago->delete();
        });

        // 🧾 AUDIT: pago eliminado
        Audit::log('pagos', 'deleted', 'Pago eliminado', null, $payload);

        return redirect()
            ->route('admin.estado_cuenta.index')
            ->with('success', 'Pago eliminado correctamente.');
    }
}
