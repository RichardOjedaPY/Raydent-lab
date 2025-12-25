<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinica;
use App\Models\TarifarioConcepto;
use App\Models\TarifarioClinicaPrecio;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TarifarioController extends Controller
{
    public function __construct()
    {
        // Ajustá permisos a tu estándar
        $this->middleware('permission:tarifario.view')->only(['index', 'clinica', 'clinicaIndex']);
        $this->middleware('permission:tarifario.update')->only(['update', 'clinicaUpdate']);
    }

    public function index(Request $r)
    {
        $q = TarifarioConcepto::query();

        if ($r->filled('q')) {
            $term = trim($r->q);
            $q->where(function ($w) use ($term) {
                $w->where('nombre', 'like', "%{$term}%")
                  ->orWhere('concept_key', 'like', "%{$term}%")
                  ->orWhere('grupo', 'like', "%{$term}%");
            });
        }

        if ($r->filled('grupo')) {
            $q->where('grupo', $r->grupo);
        }

        $conceptos = $q->orderBy('grupo')->orderBy('nombre')->paginate(50)->withQueryString();

        $grupos = TarifarioConcepto::query()
            ->whereNotNull('grupo')
            ->select('grupo')
            ->distinct()
            ->orderBy('grupo')
            ->pluck('grupo');

        // 🧾 AUDIT: vio listado tarifario maestro
        Audit::log('tarifario', 'view_list', 'Vio tarifario maestro', null, [
            'q'        => $r->filled('q') ? trim((string) $r->q) : null,
            'grupo'    => $r->filled('grupo') ? (string) $r->grupo : null,
            'page'     => (int) $conceptos->currentPage(),
            'per_page' => (int) $conceptos->perPage(),
            'total'    => (int) $conceptos->total(),
        ]);

        return view('admin.tarifario.index', compact('conceptos', 'grupos'));
    }

    public function update(Request $r)
    {
        $data = $r->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.concept_key' => ['required', 'string', 'max:80'],
            'items.*.precio_gs'   => ['nullable'],
            'items.*.is_active'   => ['nullable'],
        ]);

        // Para auditoría (sin afectar lógica)
        $touched = 0;

        foreach ($data['items'] as $row) {
            $key = $row['concept_key'];
            $precio = $this->parseGs($row['precio_gs'] ?? 0);
            $active = isset($row['is_active']) ? 1 : 0;

            TarifarioConcepto::where('concept_key', $key)->update([
                'precio_gs' => $precio,
                'is_active' => $active,
            ]);

            $touched++;
        }

        // 🧾 AUDIT: actualizó tarifario maestro
        Audit::log('tarifario', 'update_master', 'Tarifario maestro actualizado', null, [
            'items_count' => (int) $touched,
        ]);

        return back()->with('success', 'Tarifario maestro actualizado.');
    }

    public function clinica(Clinica $clinica, Request $r)
    {
        $conceptos = TarifarioConcepto::query()
            ->orderBy('grupo')
            ->orderBy('nombre')
            ->get();

        $overrides = TarifarioClinicaPrecio::query()
            ->where('clinica_id', $clinica->id)
            ->get()
            ->keyBy('concept_key');

        $clinicas = Clinica::query()->orderBy('nombre')->get(['id', 'nombre']);

        // 🧾 AUDIT: vio tarifario por clínica
        Audit::log('tarifario', 'view_clinic', 'Vio tarifario por clínica', $clinica, [
            'clinica_id'     => (int) $clinica->id,
            'conceptos'      => (int) $conceptos->count(),
            'overrides'      => (int) $overrides->count(),
        ]);

        return view('admin.tarifario.clinica', compact('clinica', 'clinicas', 'conceptos', 'overrides'));
    }

    public function clinicaUpdate(Clinica $clinica, Request $r)
    {
        $data = $r->validate([
            'items' => ['required', 'array'],
            'items.*.concept_key' => ['required', 'string', 'max:80'],
            'items.*.precio_override_gs' => ['nullable'],
        ]);

        // Contadores para auditoría (sin tocar lógica)
        $createdOrUpdated = 0;
        $deleted          = 0;

        DB::transaction(function () use ($clinica, $data, &$createdOrUpdated, &$deleted) {
            foreach ($data['items'] as $row) {
                $key = $row['concept_key'];
                $raw = (string)($row['precio_override_gs'] ?? '');

                // si viene vacío => borrar override (usa maestro)
                $digits = preg_replace('/\D+/', '', $raw);
                if ($digits === '' || $digits === null) {
                    $deleted += (int) TarifarioClinicaPrecio::where('clinica_id', $clinica->id)
                        ->where('concept_key', $key)
                        ->delete();
                    continue;
                }

                $precio = (int)$digits;

                TarifarioClinicaPrecio::updateOrCreate(
                    ['clinica_id' => $clinica->id, 'concept_key' => $key],
                    ['precio_gs' => $precio]
                );

                $createdOrUpdated++;
            }
        });

        // 🧾 AUDIT: actualizó overrides por clínica
        Audit::log('tarifario', 'update_clinic', 'Precios por clínica actualizados', $clinica, [
            'clinica_id'         => (int) $clinica->id,
            'items_count'        => (int) count($data['items'] ?? []),
            'updated_or_created' => (int) $createdOrUpdated,
            'deleted'            => (int) $deleted,
        ]);

        return back()->with('success', 'Precios por clínica actualizados.');
    }

    private function parseGs($value): int
    {
        $s = (string) $value;
        $digits = preg_replace('/\D+/', '', $s);
        return (int) ($digits ?: 0);
    }

    public function clinicaIndex()
    {
        // Buscamos la primera clínica para mostrar sus precios
        $clinica = \App\Models\Clinica::where('is_active', true)->first();

        if (!$clinica) {
            // 🧾 AUDIT: intentó ir a clínica pero no hay activas
            Audit::log('tarifario', 'redirect_clinic_none', 'No hay clínicas activas para configurar', null, []);

            return redirect()->route('admin.tarifario.index')
                ->with('error', 'No hay clínicas activas para configurar.');
        }

        // 🧾 AUDIT: redirigió a tarifario por clínica (primera activa)
        Audit::log('tarifario', 'redirect_clinic', 'Accedió a tarifario por clínica (primera activa)', $clinica, [
            'clinica_id' => (int) $clinica->id,
        ]);

        return redirect()->route('admin.tarifario.clinica', $clinica->id);
    }
}
