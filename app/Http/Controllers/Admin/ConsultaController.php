<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use App\Models\Paciente;
use App\Models\Clinica;
use Illuminate\Http\Request;

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

        $search = trim((string) $request->get('search', ''));

        // 🔒 Multi-tenant
        $clinicaId = $isAdmin ? $request->get('clinica_id') : $user->clinica_id;

        $consultas = Consulta::with(['paciente', 'clinica', 'profesional'])
            ->when($clinicaId, fn ($q) => $q->where('clinica_id', $clinicaId))
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

        $clinicas = $isAdmin
            ? Clinica::where('is_active', true)->orderBy('nombre')->get()
            : Clinica::where('id', $user->clinica_id)->get();

        return view('admin.consultas.index', compact(
            'consultas', 'clinicas', 'search', 'clinicaId', 'isAdmin'
        ));
    }

    public function create(Request $request)
    {
        $user    = $request->user();
        $isAdmin = $user->hasRole('admin');

        if (! $isAdmin && ! $user->clinica_id) {
            abort(403, 'Usuario clínica sin clínica asignada.');
        }

        $consulta = new Consulta();

        $clinicas = $isAdmin
            ? Clinica::where('is_active', true)->orderBy('nombre')->get()
            : Clinica::where('id', $user->clinica_id)->get();

        // Opcional: si viene con ?paciente_id=
        $pacienteId = $request->get('paciente_id');

        $pacientes = Paciente::with('clinica')
            ->when(! $isAdmin, fn ($q) => $q->where('clinica_id', $user->clinica_id))
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->limit(200)
            ->get();

        return view('admin.consultas.create', compact(
            'consulta', 'clinicas', 'pacientes', 'pacienteId', 'isAdmin'
        ));
    }

    public function store(Request $request)
    {
        $user    = $request->user();
        $isAdmin = $user->hasRole('admin');

        if (! $isAdmin && ! $user->clinica_id) {
            abort(403, 'Usuario clínica sin clínica asignada.');
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

        // 🔒 Multi-tenant: clínica no elige clinica_id
        if (! $isAdmin) {
            $data['clinica_id'] = $user->clinica_id;
        }

        // Seguridad: paciente debe pertenecer a clínica
        $paciente = Paciente::findOrFail($data['paciente_id']);
        if ((int) $paciente->clinica_id !== (int) $data['clinica_id']) {
            return back()
                ->withErrors(['paciente_id' => 'El paciente seleccionado no pertenece a la clínica indicada.'])
                ->withInput();
        }

        $data['user_id'] = $user->id;

        $consulta = Consulta::create($data);

        return redirect()
            ->route('admin.consultas.show', $consulta)
            ->with('success', 'Consulta registrada correctamente.');
    }

    public function show(Consulta $consulta)
    {
        $user    = auth()->user();
        $isAdmin = $user->hasRole('admin');

        // 🔒 Multi-tenant
        if (! $isAdmin && (int) $consulta->clinica_id !== (int) $user->clinica_id) {
            abort(403);
        }

        $consulta->load(['paciente', 'clinica', 'profesional']);

        return view('admin.consultas.show', compact('consulta'));
    }

    public function edit(Consulta $consulta)
    {
        $user    = auth()->user();
        $isAdmin = $user->hasRole('admin');

        if (! $isAdmin && (int) $consulta->clinica_id !== (int) $user->clinica_id) {
            abort(403);
        }

        $clinicas = $isAdmin
            ? Clinica::where('is_active', true)->orderBy('nombre')->get()
            : Clinica::where('id', $user->clinica_id)->get();

        $pacientes = Paciente::with('clinica')
            ->when(! $isAdmin, fn ($q) => $q->where('clinica_id', $user->clinica_id))
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->limit(200)
            ->get();

        return view('admin.consultas.edit', compact('consulta', 'clinicas', 'pacientes', 'isAdmin'));
    }

    public function update(Request $request, Consulta $consulta)
    {
        $user    = $request->user();
        $isAdmin = $user->hasRole('admin');

        if (! $isAdmin && (int) $consulta->clinica_id !== (int) $user->clinica_id) {
            abort(403);
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
                ->withErrors(['paciente_id' => 'El paciente seleccionado no pertenece a la clínica indicada.'])
                ->withInput();
        }

        $consulta->update($data);

        return redirect()
            ->route('admin.consultas.show', $consulta)
            ->with('success', 'Consulta actualizada correctamente.');
    }

    public function destroy(Consulta $consulta)
    {
        $user    = auth()->user();
        $isAdmin = $user->hasRole('admin');

        if (! $isAdmin && (int) $consulta->clinica_id !== (int) $user->clinica_id) {
            abort(403);
        }

        $consulta->delete();

        return redirect()
            ->route('admin.consultas.index')
            ->with('success', 'Consulta eliminada correctamente.');
    }
}
