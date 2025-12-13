<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Models\Clinica;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Pedido;



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
    $user     = $request->user();
    $isAdmin  = $user->hasRole('admin');

    $search = trim((string) $request->get('search', ''));

    // 🔒 Multi-tenant: clínica solo ve su clínica
    $clinicaId = $isAdmin ? $request->get('clinica_id') : ($user->clinica_id);

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

    $clinicas = $isAdmin
        ? Clinica::where('is_active', true)->orderBy('nombre')->get()
        : Clinica::where('id', $user->clinica_id)->get();

    return view('admin.pacientes.index', compact('pacientes', 'clinicas', 'search', 'clinicaId'));
}

public function create()
{
    $user    = Auth::user();
    $isAdmin = $user->hasRole('admin');

    if (! $isAdmin && ! $user->clinica_id) {
        abort(403, 'Usuario clínica sin clínica asignada.');
    }

    $paciente = new Paciente();

    $clinicas = $isAdmin
        ? Clinica::where('is_active', true)->orderBy('nombre')->get()
        : Clinica::where('id', $user->clinica_id)->get();

    return view('admin.pacientes.create', compact('paciente', 'clinicas'));
}

public function store(Request $request)
{
    $user    = $request->user();
    $isAdmin = $user->hasRole('admin');

    if (! $isAdmin && ! $user->clinica_id) {
        abort(403, 'Usuario clínica sin clínica asignada.');
    }

    $rules = [
        'clinica_id'       => $isAdmin ? ['required', 'integer', 'exists:clinicas,id'] : ['nullable'],
        'nombre'           => ['required', 'string', 'max:120'],
        'apellido'         => ['nullable', 'string', 'max:120'],
        'documento'        => ['nullable', 'string', 'max:30'],
        'fecha_nacimiento' => ['nullable', 'date'],
        'edad'             => ['nullable', 'integer', 'min:0', 'max:130'],
        'genero'           => ['nullable', 'string', Rule::in(['M', 'F', 'O'])],
        'telefono'         => ['nullable', 'string', 'max:50'],
        'email'            => ['nullable', 'email', 'max:255'],
        'direccion'        => ['nullable', 'string', 'max:255'],
        'ciudad'           => ['nullable', 'string', 'max:100'],
        'is_active'        => ['nullable', 'boolean'],
        'observaciones'    => ['nullable', 'string'],
    ];

    $data = $request->validate($rules);

    // 🔒 Multi-tenant: clínica no elige clinica_id
    if (! $isAdmin) {
        $data['clinica_id'] = $user->clinica_id;
    }

    $data['is_active'] = $data['is_active'] ?? true;

    // 🎂 Si hay fecha, calculamos edad automáticamente (servidor)
    if (! empty($data['fecha_nacimiento'])) {
        $data['edad'] = Carbon::parse($data['fecha_nacimiento'])->age;
    }

    $paciente = Paciente::create($data);

    return redirect()
        ->route('admin.pacientes.show', $paciente)
        ->with('success', 'Paciente creado correctamente.');
}

public function show(Paciente $paciente)
{
    $user    = Auth::user();
    $isAdmin = $user->hasRole('admin');

    // 🔒 Multi-tenant
    if (! $isAdmin && (int) $paciente->clinica_id !== (int) $user->clinica_id) {
        abort(403);
    }

    $paciente->load('clinica');

    // ✅ Historial: últimas consultas
    $consultas = $paciente->consultas()
        ->orderByDesc('fecha_hora')
        ->limit(20)
        ->get();

    // ✅ Historial: últimos pedidos + cantidad de fotos realizadas
    $pedidos = $paciente->pedidos()
        ->with(['clinica'])
        ->withCount('fotosRealizadas')
        ->orderByDesc('fecha_solicitud')
        ->orderByDesc('id')
        ->limit(20)
        ->get();

    return view('admin.pacientes.show', compact('paciente', 'consultas', 'pedidos'));
}


public function edit(Paciente $paciente)
{
    $user    = Auth::user();
    $isAdmin = $user->hasRole('admin');

    if (! $isAdmin && (int) $paciente->clinica_id !== (int) $user->clinica_id) {
        abort(403);
    }

    $clinicas = $isAdmin
        ? Clinica::where('is_active', true)->orderBy('nombre')->get()
        : Clinica::where('id', $user->clinica_id)->get();

    return view('admin.pacientes.edit', compact('paciente', 'clinicas'));
}

public function update(Request $request, Paciente $paciente)
{
    $user    = $request->user();
    $isAdmin = $user->hasRole('admin');

    if (! $isAdmin && (int) $paciente->clinica_id !== (int) $user->clinica_id) {
        abort(403);
    }

    $rules = [
        'clinica_id'       => $isAdmin ? ['required', 'integer', 'exists:clinicas,id'] : ['nullable'],
        'nombre'           => ['required', 'string', 'max:120'],
        'apellido'         => ['nullable', 'string', 'max:120'],
        'documento'        => ['nullable', 'string', 'max:30'],
        'fecha_nacimiento' => ['nullable', 'date'],
        'edad'             => ['nullable', 'integer', 'min:0', 'max:130'],
        'genero'           => ['nullable', 'string', Rule::in(['M', 'F', 'O'])],
        'telefono'         => ['nullable', 'string', 'max:50'],
        'email'            => ['nullable', 'email', 'max:255'],
        'direccion'        => ['nullable', 'string', 'max:255'],
        'ciudad'           => ['nullable', 'string', 'max:100'],
        'is_active'        => ['nullable', 'boolean'],
        'observaciones'    => ['nullable', 'string'],
    ];

    $data = $request->validate($rules);

    if (! $isAdmin) {
        $data['clinica_id'] = $user->clinica_id;
    }

    $data['is_active'] = $data['is_active'] ?? false;

    if (! empty($data['fecha_nacimiento'])) {
        $data['edad'] = Carbon::parse($data['fecha_nacimiento'])->age;
    }

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
