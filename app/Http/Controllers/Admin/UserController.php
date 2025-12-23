<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\Clinica;
use App\Support\Audit;



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
        $roles = Role::whereIn('name', ['admin', 'tecnico', 'clinica', 'cajero'])
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
        $roles = Role::whereIn('name', ['admin', 'tecnico', 'clinica', 'cajero'])
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
        $afterRoles = $user->getRoleNames()->values()->all();
        Audit::log('usuarios', 'roles_set', 'Roles asignados al usuario', $user, [
            'roles' => $afterRoles,
            'clinica_id' => $user->clinica_id,
            'tipo_usuario_clinica' => $user->tipo_usuario_clinica,
        ]);
        
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
            'required','email','max:255',
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
            return back()->withErrors(['clinica_id' => 'Debe seleccionar una clínica para usuarios con rol Clínica.'])->withInput();
        }
        if (empty($data['tipo_usuario_clinica'])) {
            return back()->withErrors(['tipo_usuario_clinica' => 'Debe seleccionar el tipo de usuario dentro de la clínica.'])->withInput();
        }
    } else {
        $data['clinica_id']           = null;
        $data['tipo_usuario_clinica'] = null;
    }

    // BEFORE (campos relevantes)
    $before = $user->only(['name','email','is_active','clinica_id','tipo_usuario_clinica']);
    $beforeRoles = $user->getRoleNames()->values()->all();

    // Aplicar cambios
    $user->name  = $data['name'];
    $user->email = $data['email'];

    $passwordChanged = !empty($data['password']);
    if ($passwordChanged) {
        $user->password = Hash::make($data['password']);
    }

    // Si el checkbox no vino, no tocar
    if (array_key_exists('is_active', $data)) {
        $user->is_active = (bool) $data['is_active'];
    }

    $user->clinica_id           = $data['clinica_id'];
    $user->tipo_usuario_clinica = $data['tipo_usuario_clinica'];

    // Guardar
    $user->save();

    // Roles (una sola vez)
    $user->syncRoles([$data['role']]);
    $afterRoles = $user->getRoleNames()->values()->all();

    // AFTER
    $user->refresh();
    $after = $user->only(['name','email','is_active','clinica_id','tipo_usuario_clinica']);

    // Armar “changes” limpio (sin exponer hash)
    $changes = [];

    foreach (['name','email','is_active','clinica_id','tipo_usuario_clinica'] as $k) {
        if (($before[$k] ?? null) != ($after[$k] ?? null)) {
            $changes[$k] = ['before' => $before[$k] ?? null, 'after' => $after[$k] ?? null];
        }
    }

    if ($passwordChanged) {
        $changes['password'] = ['before' => '(oculto)', 'after' => '(actualizado)'];
    }

    if ($beforeRoles !== $afterRoles) {
        $changes['roles'] = ['before' => $beforeRoles, 'after' => $afterRoles];
    }

    // LOG si hubo cualquier cambio
    if (!empty($changes)) {
        Audit::log('usuarios', 'updated', 'Usuario actualizado', $user, [
            'clinica_id' => $user->clinica_id,
            'changes'    => $changes,
        ]);
    }

    return redirect()
        ->route('admin.usuarios.index')
        ->with('success', 'Usuario actualizado correctamente.');
}

    




    public function destroy(User $usuario)
    {
        $before = (bool) $usuario->is_active;
    
        $usuario->is_active = false;
        $usuario->save();
    
        Audit::log('usuarios', 'disabled', 'Usuario desactivado', $usuario, [
            'before_is_active' => $before,
            'after_is_active'  => (bool) $usuario->is_active,
        ]);
    
        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario desactivado correctamente.');
    }
    

    public function toggleStatus(User $usuario)
{
    $before = (bool) $usuario->is_active;

    $usuario->is_active = ! $usuario->is_active;
    $usuario->save();

    Audit::log('usuarios', 'status_toggled', 'Estado del usuario actualizado', $usuario, [
        'before_is_active' => $before,
        'after_is_active'  => (bool) $usuario->is_active,
    ]);

    return redirect()
        ->route('admin.usuarios.index')
        ->with('success', 'Estado del usuario actualizado.');
}

}
