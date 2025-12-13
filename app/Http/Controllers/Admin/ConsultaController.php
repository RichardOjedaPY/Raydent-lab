<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use App\Models\Paciente;
use App\Models\Clinica;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsultaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:consultas.view')->only(['index', 'show']);
        $this->middleware('permission:consultas.create')->only(['create', 'store']);
        $this->middleware('permission:consultas.update')->only(['edit', 'update']);
        $this->middleware('permission:consultas.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $user    = $request->user();
        $isAdmin = $user->hasRole('admin');

        // Si es clínica y no tiene clínica asignada, bloqueamos
        if (! $isAdmin && $user->hasRole('clinica') && ! $user->clinica_id) {
            abort(403, 'Usuario clínica sin clínica asignada.');
        }

        $search = trim((string) $request->get('search', ''));

        // 🔒 Multi-tenant: si no es admin, fija clinica_id
        $clinicaId = $isAdmin ? $request->get('clinica_id') : ($user->clinica_id);

        $consultas = Consulta::with(['paciente', 'clinica', 'profesional'])
            ->when($clinicaId, function ($q) use ($clinicaId) {
                $q->where('clinica_id', $clinicaId);
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('paciente', function ($w) use ($search) {
                    $w->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%")
                        ->orWhere('documento', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('fecha_hora')
            ->paginate(20)
            ->withQueryString();

        // Para el filtro: admin ve todas, clínica solo su clínica
        $clinicas = $isAdmin
            ? Clinica::where('is_active', true)->orderBy('nombre')->get()
            : Clinica::where('id', $user->clinica_id)->get();

        return view('admin.consultas.index', compact(
            'consultas', 'clinicas', 'search', 'clinicaId'
        ));
    }

    public function create(Request $request)
    {
        $user    = $request->user();
        $isAdmin = $user->hasRole('admin');

        if (! $isAdmin && $user->hasRole('clinica') && ! $user->clinica_id) {
            abort(403, 'Usuario clínica sin clínica asignada.');
        }

        $consulta = new Consulta();

        $clinicas = $isAdmin
            ? Clinica::where('is_active', true)->orderBy('nombre')->get()
            : Clinica::where('id', $user->clinica_id)->get();

        // Opcional: si viene con ?paciente_id= preseleccionamos (pero debe ser de su clínica si no es admin)
        $pacienteId = $request->get('paciente_id');

        $pacientes = Paciente::with('clinica')
            ->when(! $isAdmin && $user->clinica_id, function ($q) use ($user) {
                $q->where('clinica_id', $user->clinica_id);
            })
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->limit(200)
            ->get();

        // Si viene paciente_id y no corresponde (multi-tenant), lo anulamos
        if ($pacienteId && ! $isAdmin && $user->clinica_id) {
            $p = $pacientes->firstWhere('id', (int) $pacienteId);
            if (! $p) {
                $pacienteId = null;
            }
        }

        return view('admin.consultas.create', compact(
            'consulta', 'clinicas', 'pacientes', 'pacienteId'
        ));
    }

    public function store(Request $request)
    {
        $user    = $request->user();
        $isAdmin = $user->hasRole('admin');

        if (! $isAdmin && $user->hasRole('clinica') && ! $user->clinica_id) {
            abort(403, 'Usuario clínica sin clínica asignada.');
        }

        $rules = [
            // 🔒 Admin elige clínica, NO admin se fuerza por backend
            'clinica_id'             => $isAdmin ? ['required', 'integer', 'exists:clinicas,id'] : ['nullable'],
            'paciente_id'            => ['required', 'integer', 'exists:pacientes,id'],
            'fecha_hora'             => ['required', 'date'],
            'motivo_consulta'        => ['required', 'string', 'max:255'],
            'descripcion_problema'   => ['nullable', 'string'],
            'antecedentes_medicos'   => ['nullable', 'string'],
            'antecedentes_odontologicos' => ['nullable', 'string'],
            'medicamentos_actuales'  => ['nullable', 'string'],
            'alergias'               => ['nullable', 'string'],
            'diagnostico_presuntivo' => ['nullable', 'string'],
            'plan_tratamiento'       => ['nullable', 'string'],
            'observaciones'          => ['nullable', 'string'],
        ];

        $data = $request->validate($rules);

        // 🔒 Multi-tenant: si no es admin, se fuerza clinica_id
        if (! $isAdmin) {
            $data['clinica_id'] = $user->clinica_id;
        }

        // Seguridad: el paciente debe pertenecer a la clínica final
        $paciente = Paciente::findOrFail($data['paciente_id']);
        if ((int) $paciente->clinica_id !== (int) $data['clinica_id']) {
            return back()
                ->withErrors(['paciente_id' => 'El paciente seleccionado no pertenece a tu clínica.'])
                ->withInput();
        }

        $data['user_id'] = $user->id;

        $consulta = Consulta::create($data);

        return redirect()
            ->route('admin.consultas.show', $consulta)
            ->with('success', 'Consulta registrada correctamente.');
    }

    public function show(Consulta $consulta, Request $request)
    {
        $user    = $request->user();
        $isAdmin = $user->hasRole('admin');

        // 🔒 Multi-tenant: clínica solo puede ver su clínica
        if (! $isAdmin && $user->hasRole('clinica')) {
            if (! $user->clinica_id || (int) $consulta->clinica_id !== (int) $user->clinica_id) {
                abort(403);
            }
        }

        $consulta->load(['paciente', 'clinica', 'profesional']);

        return view('admin.consultas.show', compact('consulta'));
    }

    public function edit(Consulta $consulta, Request $request)
    {
        $user    = $request->user();
        $isAdmin = $user->hasRole('admin');

        if (! $isAdmin && $user->hasRole('clinica')) {
            if (! $user->clinica_id || (int) $consulta->clinica_id !== (int) $user->clinica_id) {
                abort(403);
            }
        }

        $clinicas = $isAdmin
            ? Clinica::where('is_active', true)->orderBy('nombre')->get()
            : Clinica::where('id', $user->clinica_id)->get();

        $pacientes = Paciente::with('clinica')
            ->when(! $isAdmin && $user->clinica_id, function ($q) use ($user) {
                $q->where('clinica_id', $user->clinica_id);
            })
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->limit(200)
            ->get();

        return view('admin.consultas.edit', compact(
            'consulta', 'clinicas', 'pacientes'
        ));
    }

    public function update(Request $request, Consulta $consulta)
    {
        $user    = $request->user();
        $isAdmin = $user->hasRole('admin');

        if (! $isAdmin && $user->hasRole('clinica')) {
            if (! $user->clinica_id || (int) $consulta->clinica_id !== (int) $user->clinica_id) {
                abort(403);
            }
        }

        $rules = [
            'clinica_id'             => $isAdmin ? ['required', 'integer', 'exists:clinicas,id'] : ['nullable'],
            'paciente_id'            => ['required', 'integer', 'exists:pacientes,id'],
            'fecha_hora'             => ['required', 'date'],
            'motivo_consulta'        => ['required', 'string', 'max:255'],
            'descripcion_problema'   => ['nullable', 'string'],
            'antecedentes_medicos'   => ['nullable', 'string'],
            'antecedentes_odontologicos' => ['nullable', 'string'],
            'medicamentos_actuales'  => ['nullable', 'string'],
            'alergias'               => ['nullable', 'string'],
            'diagnostico_presuntivo' => ['nullable', 'string'],
            'plan_tratamiento'       => ['nullable', 'string'],
            'observaciones'          => ['nullable', 'string'],
        ];

        $data = $request->validate($rules);

        if (! $isAdmin) {
            $data['clinica_id'] = $user->clinica_id;
        }

        $paciente = Paciente::findOrFail($data['paciente_id']);
        if ((int) $paciente->clinica_id !== (int) $data['clinica_id']) {
            return back()
                ->withErrors(['paciente_id' => 'El paciente seleccionado no pertenece a tu clínica.'])
                ->withInput();
        }

        $consulta->update($data);

        return redirect()
            ->route('admin.consultas.show', $consulta)
            ->with('success', 'Consulta actualizada correctamente.');
    }

    public function destroy(Consulta $consulta, Request $request)
    {
        $user    = $request->user();
        $isAdmin = $user->hasRole('admin');

        if (! $isAdmin && $user->hasRole('clinica')) {
            if (! $user->clinica_id || (int) $consulta->clinica_id !== (int) $user->clinica_id) {
                abort(403);
            }
        }

        $consulta->delete();

        return redirect()
            ->route('admin.consultas.index')
            ->with('success', 'Consulta eliminada correctamente.');
    }
}
