<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\Clinica;




class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:usuarios.view')->only(['index']);
        $this->middleware('permission:usuarios.create')->only(['create', 'store']);
        $this->middleware('permission:usuarios.update')->only(['edit', 'update']);
        $this->middleware('permission:usuarios.delete')->only(['destroy', 'toggleStatus']);
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $users = User::with('clinica')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.usuarios.index', compact('users', 'search'));
    }


    public function create()
    {
        $user      = new User();
        $roles     = Role::whereIn('name', ['admin', 'tecnico', 'clinica'])
            ->orderBy('name')
            ->get();
        $clinicas  = Clinica::where('is_active', true)
            ->orderBy('nombre')
            ->get();

        return view('admin.usuarios.create', compact('user', 'roles', 'clinicas'));
    }

    public function edit(User $usuario)
    {
        $user      = $usuario;
        $roles     = Role::whereIn('name', ['admin', 'tecnico', 'clinica'])
            ->orderBy('name')
            ->get();
        $clinicas  = Clinica::where('is_active', true)
            ->orderBy('nombre')
            ->get();

        return view('admin.usuarios.edit', compact('user', 'roles', 'clinicas'));
    }




    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'email'                => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'             => ['required', 'string', 'min:8', 'confirmed'],
            'is_active'            => ['nullable', 'boolean'],
            'role'                 => ['required', 'string', 'exists:roles,name'],
            'clinica_id'           => ['nullable', 'integer', 'exists:clinicas,id'],
            'tipo_usuario_clinica' => ['nullable', 'string', Rule::in(['owner', 'staff'])],
        ]);

        // Reglas para rol "clinica"
        if ($data['role'] === 'clinica') {
            if (empty($data['clinica_id'])) {
                return back()
                    ->withErrors(['clinica_id' => 'Debe seleccionar una clínica para usuarios con rol Clínica.'])
                    ->withInput();
            }
            if (empty($data['tipo_usuario_clinica'])) {
                return back()
                    ->withErrors(['tipo_usuario_clinica' => 'Debe seleccionar el tipo de usuario dentro de la clínica.'])
                    ->withInput();
            }
        } else {
            // Si NO es rol clinica, limpiamos estos campos
            $data['clinica_id']           = null;
            $data['tipo_usuario_clinica'] = null;
        }

        $user                        = new User();
        $user->clinica_id            = $data['clinica_id'];
        $user->tipo_usuario_clinica  = $data['tipo_usuario_clinica'];
        $user->name                  = $data['name'];
        $user->email                 = $data['email'];
        $user->password              = Hash::make($data['password']);
        $user->is_active             = $data['is_active'] ?? false;
        $user->save();

        $user->syncRoles([$data['role']]);

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }







    public function update(Request $request, User $usuario)
    {
        $user = $usuario;

        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'email'                => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password'             => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active'            => ['nullable', 'boolean'],
            'role'                 => ['required', 'string', 'exists:roles,name'],
            'clinica_id'           => ['nullable', 'integer', 'exists:clinicas,id'],
            'tipo_usuario_clinica' => ['nullable', 'string', Rule::in(['owner', 'staff'])],
        ]);

        if ($data['role'] === 'clinica') {
            if (empty($data['clinica_id'])) {
                return back()
                    ->withErrors(['clinica_id' => 'Debe seleccionar una clínica para usuarios con rol Clínica.'])
                    ->withInput();
            }
            if (empty($data['tipo_usuario_clinica'])) {
                return back()
                    ->withErrors(['tipo_usuario_clinica' => 'Debe seleccionar el tipo de usuario dentro de la clínica.'])
                    ->withInput();
            }
        } else {
            $data['clinica_id']           = null;
            $data['tipo_usuario_clinica'] = null;
        }

        $user->name  = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->is_active            = $data['is_active'] ?? false;
        $user->clinica_id           = $data['clinica_id'];
        $user->tipo_usuario_clinica = $data['tipo_usuario_clinica'];
        $user->save();

        $user->syncRoles([$data['role']]);

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }




    public function destroy(User $usuario)
    {
        // En lugar de borrar físico, desactivamos
        $usuario->is_active = false;
        $usuario->save();

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario desactivado correctamente.');
    }

    public function toggleStatus(User $usuario)
    {
        $usuario->is_active = ! $usuario->is_active;
        $usuario->save();

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Estado del usuario actualizado.');
    }
}
