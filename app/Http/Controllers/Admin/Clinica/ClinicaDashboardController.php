<?php

namespace App\Http\Controllers\Admin\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Clinica;
use App\Models\Paciente;
use App\Models\Pedido;
use Illuminate\Support\Facades\Auth;

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

        return view('admin.clinicas.dashboard', compact(
            'clinica',
            'pacientesTotal',
            'pedidosPendientes',
            'pedidosFinalizados',
            'pedidosTotal',
            'ultimosPedidos'
        ));
    }
}
