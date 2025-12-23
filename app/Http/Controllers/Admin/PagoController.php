<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Pago, PagoAplicacion, Liquidacion};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{PedidoLiquidacion};
use Barryvdh\DomPDF\Facade\Pdf;



class PagoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:pagos.create');
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

        return \DB::transaction(function () use ($liquidacion, $user, $data, $montoGs, $saldoGs) {

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

            // ✅ parcial: aplica lo que corresponda hasta el saldo
            $aplicarGs = min($montoGs, $saldoGs);

            if ($aplicarGs > 0) {
                PagoAplicacion::create([
                    'pago_id'        => $pago->id,
                    'liquidacion_id' => $liquidacion->id,
                    'monto_gs'       => $aplicarGs,
                ]);
            }

            // ✅ si sobra, queda como “pago a cuenta” (sin aplicación)

            return redirect()
                ->route('admin.pagos.show', $pago)
                ->with('ok', 'Pago registrado correctamente.');
        });
    }



    public function show(Pago $pago)
    {
        $pago->load([
            'clinica',
            'user',
            'aplicaciones.liquidacion.pedido.paciente',
            'aplicaciones.liquidacion.pedido.clinica',
        ]);

        $aplicadoGs = (int) $pago->aplicaciones->sum('monto_gs');
        $aCuentaGs  = max(0, (int)$pago->monto_gs - $aplicadoGs);

        return view('admin.pagos.show', compact('pago', 'aplicadoGs', 'aCuentaGs'));
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

        $pdf = Pdf::loadView('admin.pagos.recibo_pdf', compact('pago', 'aplicadoGs', 'aCuentaGs'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('Recibo_Pago_' . $pago->id . '.pdf');
    }
}
