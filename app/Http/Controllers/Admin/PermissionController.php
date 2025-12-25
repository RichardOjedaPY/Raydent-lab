<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        $roles       = Role::orderBy('name')->get();
        $permissions = Permission::with('roles')->orderBy('name')->get();

        // 🧾 AUDIT: vio listado/mapa de permisos
        Audit::log('permissions', 'view_index', 'Vio mapa de permisos por rol', null, [
            'roles_count'       => (int) $roles->count(),
            'permissions_count' => (int) $permissions->count(),
        ]);

        return view('admin.permissions.index', compact('roles', 'permissions'));
    }

    public function editRole(Role $role)
    {
        // 1) Traer todos los permisos existentes
        $permissions = Permission::query()
            ->orderBy('name')
            ->get();

        // 2) Armar módulos y acciones automáticamente (modulo.accion)
        $modules = [];
        $actionsDetected = [];

        foreach ($permissions as $perm) {
            $name = (string) $perm->name;

            if (!str_contains($name, '.')) {
                continue;
            }

            [$moduleKey, $actionKey] = explode('.', $name, 2);

            $modules[$moduleKey] = $this->moduleLabel($moduleKey);
            $actionsDetected[$actionKey] = $this->actionLabel($actionKey);
        }

        // 3) Orden preferido de acciones (primero las CRUD)
        $preferredActionOrder = ['view', 'create', 'update', 'delete'];

        $actions = [];

        foreach ($preferredActionOrder as $a) {
            if (isset($actionsDetected[$a])) {
                $actions[$a] = $actionsDetected[$a];
                unset($actionsDetected[$a]);
            }
        }

        ksort($actionsDetected);
        foreach ($actionsDetected as $k => $label) {
            $actions[$k] = $label;
        }

        // 4) Orden preferido de módulos (lo importante arriba)
        $preferredModuleOrder = [
            'usuarios', 'clinicas', 'pacientes', 'consultas', 'pedidos',
            'resultados', 'tecnico_dashboard', 'tecnico_pedidos', 'permissions',
        ];

        $modules = $this->orderByPreferred($modules, $preferredModuleOrder);

        // 5) Mapa permiso => objeto Permission
        $allPermissions = $permissions->keyBy('name');

        // 🧾 AUDIT: abrió edición de permisos de un rol
        Audit::log('permissions', 'view_edit_role', 'Vio edición de permisos del rol', $role, [
            'role_id'           => (int) $role->id,
            'role_name'         => (string) $role->name,
            'modules_count'     => (int) count($modules),
            'actions_count'     => (int) count($actions),
            'permissions_count' => (int) $permissions->count(),
        ]);

        return view('admin.permissions.edit-role', compact(
            'role', 'modules', 'actions', 'allPermissions'
        ));
    }

    public function updateRole(Request $request, Role $role)
    {
        // Snapshot anterior (para auditoría, no altera lógica)
        $before = $role->permissions()->pluck('name')->values()->all();

        $data = $request->validate([
            'perms'   => ['nullable', 'array'],
            'perms.*' => ['string'],
        ]);

        $perms = $data['perms'] ?? [];

        $role->syncPermissions($perms);

        // Snapshot posterior
        $after = $role->permissions()->pluck('name')->values()->all();

        // Cambios (resumen, sin hacer pesado el log)
        $added   = array_values(array_diff($after, $before));
        $removed = array_values(array_diff($before, $after));

        // 🧾 AUDIT: actualizó permisos del rol
        Audit::log('permissions', 'updated_role', 'Permisos actualizados para rol', $role, [
            'role_id'        => (int) $role->id,
            'role_name'      => (string) $role->name,
            'before_count'   => (int) count($before),
            'after_count'    => (int) count($after),
            'added_count'    => (int) count($added),
            'removed_count'  => (int) count($removed),

            // limitar para no inflar activity
            'added'          => array_slice($added, 0, 100),
            'removed'        => array_slice($removed, 0, 100),
        ]);

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', "Permisos actualizados para el rol {$role->name}.");
    }

    private function moduleLabel(string $moduleKey): string
    {
        return match ($moduleKey) {
            'usuarios'          => 'Usuarios',
            'clinicas'          => 'Clínicas',
            'pacientes'         => 'Pacientes',
            'consultas'         => 'Consultas',
            'pedidos'           => 'Pedidos',
            'resultados'        => 'Resultados',
            'tecnico_dashboard' => 'Dashboard Técnico',
            'tecnico_pedidos'   => 'Panel Técnico',
            'permissions'       => 'Permisos / Roles',
            default             => ucfirst(str_replace('_', ' ', $moduleKey)),
        };
    }

    private function actionLabel(string $actionKey): string
    {
        return match ($actionKey) {
            'view'     => 'Ver / Listar',
            'create'   => 'Crear',
            'update'   => 'Editar',
            'delete'   => 'Eliminar',

            'pdf'       => 'PDF',
            'download'  => 'Descargar',
            'fotos_pdf' => 'Fotos PDF',
            'estado'    => 'Cambiar estado',
            'archivos'  => 'Subir archivos',
            'fotos'     => 'Subir fotos',
            'trabajar'  => 'Trabajar',

            default     => ucfirst(str_replace('_', ' ', $actionKey)),
        };
    }

    private function orderByPreferred(array $items, array $preferredOrder): array
    {
        $ordered = [];

        foreach ($preferredOrder as $key) {
            if (array_key_exists($key, $items)) {
                $ordered[$key] = $items[$key];
                unset($items[$key]);
            }
        }

        ksort($items);
        foreach ($items as $k => $v) {
            $ordered[$k] = $v;
        }

        return $ordered;
    }
}
