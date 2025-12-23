<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{PedidoLiquidacion, Clinica};
use Illuminate\Http\Request;

class LiquidacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:liquidaciones.view');
    }

    public function pedidosLiquidados(Request $r)
    {
        $q = PedidoLiquidacion::query()
            ->with(['pedido.paciente', 'pedido.clinica'])
            ->withSum('aplicaciones as pagado_gs', 'monto_gs');

        if ($r->filled('clinica_id')) {
            $q->where('clinica_id', $r->integer('clinica_id'));
        }

        if ($r->filled('desde')) $q->whereDate('liquidado_at', '>=', $r->date('desde'));
        if ($r->filled('hasta')) $q->whereDate('liquidado_at', '<=', $r->date('hasta'));

        if ($r->filled('codigo')) {
            $codigo = trim((string) $r->input('codigo'));
            $q->whereHas('pedido', fn($qq) => $qq->where('codigo_pedido', 'like', "%{$codigo}%"));
        }

        if ($r->input('saldo') === 'con') {
            $q->havingRaw('(COALESCE(total_gs, 0) - COALESCE(pagado_gs, 0)) > 0');
        } elseif ($r->input('saldo') === 'sin') {
            $q->havingRaw('(COALESCE(total_gs, 0) - COALESCE(pagado_gs, 0)) <= 0');
        }

        $liquidaciones = $q->latest('id')->paginate(20)->withQueryString();
        $clinicas = Clinica::orderBy('nombre')->get();

        return view('admin.liquidaciones.pedidos_liquidados', compact('liquidaciones', 'clinicas'));
    }
}
