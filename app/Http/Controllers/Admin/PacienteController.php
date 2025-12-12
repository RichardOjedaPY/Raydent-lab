<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Models\Clinica;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PacienteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:pacientes.view')->only(['index', 'show']);
        $this->middleware('permission:pacientes.create')->only(['create', 'store']);
        $this->middleware('permission:pacientes.update')->only(['edit', 'update']);
        $this->middleware('permission:pacientes.delete')->only(['destroy', 'toggleStatus']);
    }

    public function index(Request $request)
    {
        $search     = trim((string) $request->get('search', ''));
        $clinicaId  = $request->get('clinica_id');

        $pacientes = Paciente::with('clinica')
            ->when($clinicaId, function ($q) use ($clinicaId) {
                $q->where('clinica_id', $clinicaId);
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%")
                        ->orWhere('documento', 'like', "%{$search}%");
                });
            })
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        $clinicas = Clinica::where('is_active', true)
            ->orderBy('nombre')
            ->get();

        return view('admin.pacientes.index', compact('pacientes', 'clinicas', 'search', 'clinicaId'));
    }

    public function create()
    {
        $paciente = new Paciente();

        $clinicas = Clinica::where('is_active', true)
            ->orderBy('nombre')
            ->get();

        return view('admin.pacientes.create', compact('paciente', 'clinicas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'clinica_id'       => ['required', 'integer', 'exists:clinicas,id'],
            'nombre'           => ['required', 'string', 'max:120'],
            'apellido'         => ['nullable', 'string', 'max:120'],
            'documento'        => ['nullable', 'string', 'max:30'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'genero'           => ['nullable', 'string', Rule::in(['M', 'F', 'O'])],
            'telefono'         => ['nullable', 'string', 'max:50'],
            'email'            => ['nullable', 'email', 'max:255'],
            'direccion'        => ['nullable', 'string', 'max:255'],
            'ciudad'           => ['nullable', 'string', 'max:100'],
            'is_active'        => ['nullable', 'boolean'],
            'observaciones'    => ['nullable', 'string'],
        ]);

        $data['is_active'] = $data['is_active'] ?? true;

        $paciente = Paciente::create($data);

        return redirect()
            ->route('admin.pacientes.show', $paciente)
            ->with('success', 'Paciente creado correctamente.');
    }

    public function show(Paciente $paciente)
    {
        $paciente->load('clinica');

        return view('admin.pacientes.show', compact('paciente'));
    }

    public function edit(Paciente $paciente)
    {
        $clinicas = Clinica::where('is_active', true)
            ->orderBy('nombre')
            ->get();

        return view('admin.pacientes.edit', compact('paciente', 'clinicas'));
    }

    public function update(Request $request, Paciente $paciente)
    {
        $data = $request->validate([
            'clinica_id'       => ['required', 'integer', 'exists:clinicas,id'],
            'nombre'           => ['required', 'string', 'max:120'],
            'apellido'         => ['nullable', 'string', 'max:120'],
            'documento'        => ['nullable', 'string', 'max:30'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'genero'           => ['nullable', 'string', Rule::in(['M', 'F', 'O'])],
            'telefono'         => ['nullable', 'string', 'max:50'],
            'email'            => ['nullable', 'email', 'max:255'],
            'direccion'        => ['nullable', 'string', 'max:255'],
            'ciudad'           => ['nullable', 'string', 'max:100'],
            'is_active'        => ['nullable', 'boolean'],
            'observaciones'    => ['nullable', 'string'],
        ]);

        $data['is_active'] = $data['is_active'] ?? false;

        $paciente->update($data);

        return redirect()
            ->route('admin.pacientes.show', $paciente)
            ->with('success', 'Paciente actualizado correctamente.');
    }

    public function destroy(Paciente $paciente)
    {
        // Desactivamos en lugar de borrar físico
        $paciente->is_active = false;
        $paciente->save();

        return redirect()
            ->route('admin.pacientes.index')
            ->with('success', 'Paciente desactivado correctamente.');
    }

    public function toggleStatus(Paciente $paciente)
    {
        $paciente->is_active = ! $paciente->is_active;
        $paciente->save();

        return redirect()
            ->route('admin.pacientes.index')
            ->with('success', 'Estado del paciente actualizado.');
    }
}
