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
        $search    = trim((string) $request->get('search', ''));
        $clinicaId = $request->get('clinica_id');

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

        $clinicas = Clinica::where('is_active', true)
            ->orderBy('nombre')
            ->get();

        return view('admin.consultas.index', compact(
            'consultas', 'clinicas', 'search', 'clinicaId'
        ));
    }

    public function create(Request $request)
    {
        $consulta  = new Consulta();

        $clinicas = Clinica::where('is_active', true)
            ->orderBy('nombre')
            ->get();

        // Opcional: si viene con ?paciente_id= preseleccionamos
        $pacienteId = $request->get('paciente_id');

        $pacientes = Paciente::with('clinica')
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->limit(200) // para no traer miles de golpe
            ->get();

        return view('admin.consultas.create', compact(
            'consulta', 'clinicas', 'pacientes', 'pacienteId'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'clinica_id'             => ['required', 'integer', 'exists:clinicas,id'],
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
        ]);

        // Seguridad: el paciente debe pertenecer a la clínica elegida
        $paciente = Paciente::findOrFail($data['paciente_id']);
        if ((int) $paciente->clinica_id !== (int) $data['clinica_id']) {
            return back()
                ->withErrors(['paciente_id' => 'El paciente seleccionado no pertenece a la clínica indicada.'])
                ->withInput();
        }

        $data['user_id'] = $request->user()->id;

        $consulta = Consulta::create($data);

        return redirect()
            ->route('admin.consultas.show', $consulta)
            ->with('success', 'Consulta registrada correctamente.');
    }

    public function show(Consulta $consulta)
    {
        $consulta->load(['paciente', 'clinica', 'profesional']);

        return view('admin.consultas.show', compact('consulta'));
    }

    public function edit(Consulta $consulta)
    {
        $clinicas = Clinica::where('is_active', true)
            ->orderBy('nombre')
            ->get();

        $pacientes = Paciente::with('clinica')
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
        $data = $request->validate([
            'clinica_id'             => ['required', 'integer', 'exists:clinicas,id'],
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
        ]);

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
        $consulta->delete();

        return redirect()
            ->route('admin.consultas.index')
            ->with('success', 'Consulta eliminada correctamente.');
    }
}
