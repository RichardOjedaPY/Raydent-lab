<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        $roles       = Role::orderBy('name')->get();
        $permissions = Permission::with('roles')->orderBy('name')->get();

        return view('admin.permissions.index', compact('roles', 'permissions'));
    }

    public function editRole(Role $role)
    {
        // Módulos y acciones que usamos
        $modules = [
            'usuarios'  => 'Usuarios',
            'clinicas'  => 'Clínicas',
            'pacientes' => 'Pacientes',
            'pedidos'   => 'Pedidos',
        ];

        $actions = [
            'view'   => 'Ver / Listar',
            'create' => 'Crear',
            'update' => 'Editar',
            'delete' => 'Eliminar',
        ];

        // Mapa permiso => objeto Permission
        $allPermissions = Permission::all()
            ->keyBy('name');

        return view('admin.permissions.edit-role', compact(
            'role', 'modules', 'actions', 'allPermissions'
        ));
    }

    public function updateRole(Request $request, Role $role)
    {
        $data = $request->validate([
            'perms'   => ['nullable', 'array'],
            'perms.*' => ['string'],
        ]);

        $perms = $data['perms'] ?? [];

        // Sincroniza permisos: lo que no está en el array se quita
        $role->syncPermissions($perms);

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', "Permisos actualizados para el rol {$role->name}.");
    }
}
