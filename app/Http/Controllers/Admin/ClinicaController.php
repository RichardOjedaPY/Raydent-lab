<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinica;
use Illuminate\Http\Request;

class ClinicaController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $clinicas = Clinica::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('nombre', 'like', "%{$search}%")
                      ->orWhere('ruc', 'like', "%{$search}%")
                      ->orWhere('ciudad', 'like', "%{$search}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('admin.clinicas.index', compact('clinicas', 'search'));
    }

    public function create()
    {
        $clinica = new Clinica();

        return view('admin.clinicas.create', compact('clinica'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'        => ['required', 'string', 'max:255'],
            'ruc'           => ['nullable', 'string', 'max:30'],
            'direccion'     => ['nullable', 'string', 'max:255'],
            'ciudad'        => ['nullable', 'string', 'max:100'],
            'telefono'      => ['nullable', 'string', 'max:50'],
            'email'         => ['nullable', 'email', 'max:255'],
            'plan'          => ['required', 'string', 'max:20'],
            'is_active'     => ['nullable', 'boolean'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $clinica = Clinica::create([
            'nombre'        => $data['nombre'],
            'ruc'           => $data['ruc'] ?? null,
            'direccion'     => $data['direccion'] ?? null,
            'ciudad'        => $data['ciudad'] ?? null,
            'telefono'      => $data['telefono'] ?? null,
            'email'         => $data['email'] ?? null,
            'plan'          => $data['plan'],
            'is_active'     => $data['is_active'] ?? false,
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        return redirect()
            ->route('admin.clinicas.index')
            ->with('success', 'Clínica creada correctamente.');
    }

    public function edit(Clinica $clinica)
    {
        return view('admin.clinicas.edit', compact('clinica'));
    }

    public function update(Request $request, Clinica $clinica)
    {
        $data = $request->validate([
            'nombre'        => ['required', 'string', 'max:255'],
            'ruc'           => ['nullable', 'string', 'max:30'],
            'direccion'     => ['nullable', 'string', 'max:255'],
            'ciudad'        => ['nullable', 'string', 'max:100'],
            'telefono'      => ['nullable', 'string', 'max:50'],
            'email'         => ['nullable', 'email', 'max:255'],
            'plan'          => ['required', 'string', 'max:20'],
            'is_active'     => ['nullable', 'boolean'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $clinica->update([
            'nombre'        => $data['nombre'],
            'ruc'           => $data['ruc'] ?? null,
            'direccion'     => $data['direccion'] ?? null,
            'ciudad'        => $data['ciudad'] ?? null,
            'telefono'      => $data['telefono'] ?? null,
            'email'         => $data['email'] ?? null,
            'plan'          => $data['plan'],
            'is_active'     => $data['is_active'] ?? false,
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        return redirect()
            ->route('admin.clinicas.index')
            ->with('success', 'Clínica actualizada correctamente.');
    }

    public function destroy(Clinica $clinica)
    {
        // Por ahora no borramos físico, solo desactivamos.
        $clinica->is_active = false;
        $clinica->save();

        return redirect()
            ->route('admin.clinicas.index')
            ->with('success', 'Clínica desactivada correctamente.');
    }

    public function toggleStatus(Clinica $clinica)
    {
        $clinica->is_active = ! $clinica->is_active;
        $clinica->save();

        return redirect()
            ->route('admin.clinicas.index')
            ->with('success', 'Estado de la clínica actualizado.');
    }
}
