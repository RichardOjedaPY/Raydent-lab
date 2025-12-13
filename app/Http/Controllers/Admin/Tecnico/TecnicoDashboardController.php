<?php

namespace App\Http\Controllers\Admin\Tecnico;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TecnicoDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:tecnico|admin']);
    }

    public function index()
    {
        $userId = Auth::id();

        // Mismo criterio que tu panel técnico: ve pedidos sin asignar o asignados al técnico
        $base = Pedido::query()
            ->where(function ($w) use ($userId) {
                $w->whereNull('tecnico_id')
                  ->orWhere('tecnico_id', $userId);
            });

        $total      = (clone $base)->count();
        $pendientes = (clone $base)->where('estado', 'pendiente')->count();
        $enProceso  = (clone $base)->where('estado', 'en_proceso')->count();
        $realizados = (clone $base)->where('estado', 'realizado')->count();

        // Ranking de clínicas con más pedidos (sobre el universo del técnico)
        $topClinicas = (clone $base)
            ->select('clinica_id', DB::raw('COUNT(*) as total'))
            ->with('clinica:id,nombre')
            ->groupBy('clinica_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return [
                    'clinica' => $row->clinica->nombre ?? 'Sin clínica',
                    'total'   => (int) $row->total,
                ];
            });

        return view('admin.tecnico.dashboard', compact(
            'total', 'pendientes', 'enProceso', 'realizados', 'topClinicas'
        ));
    }
}
