<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ Limpia cache de permisos/roles (Spatie)
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | 1) Normalizar roles
        |--------------------------------------------------------------------------
        | Si existía "cliente", lo convertimos en "clinica"
        */
        $cliente = Role::where('name', 'cliente')->first();
        if ($cliente) {
            $cliente->name = 'clinica';
            $cliente->guard_name = 'web';
            $cliente->save();
        }

        $admin   = Role::firstOrCreate(['name' => 'admin',   'guard_name' => 'web']);
        $tecnico = Role::firstOrCreate(['name' => 'tecnico', 'guard_name' => 'web']);
        $clinica = Role::firstOrCreate(['name' => 'clinica', 'guard_name' => 'web']);

        /*
        |--------------------------------------------------------------------------
        | 2) Crear permisos por módulo y acción (escalable)
        |--------------------------------------------------------------------------
        | Convención: modulo.accion
        |
        | Módulos nuevos:
        | - tecnico_dashboard.*
        | - tecnico_pedidos.*
        | - resultados.*
        | - dashboards (si querés separar admin/tecnico, ya lo cubrimos con tecnico_dashboard.view)
        */
        $permisosPorModulo = [
            // Core admin
            'usuarios'     => ['view', 'create', 'update', 'delete'],
            'clinicas'     => ['view', 'create', 'update', 'delete'],
            'pacientes'    => ['view', 'create', 'update', 'delete'],
            'consultas'    => ['view', 'create', 'update', 'delete'],
            'pedidos'      => ['view', 'create', 'update', 'delete', 'pdf'],

            // Seguridad / permisos
            'permissions'  => ['view', 'update'],

            // Técnico (módulos nuevos)
            'tecnico_dashboard' => ['view'],
            'tecnico_pedidos'   => ['view', 'trabajar', 'estado', 'archivos', 'fotos'],

            // Resultados (módulos nuevos)
            'resultados' => ['view', 'download', 'fotos_pdf'],
        ];

        $permisosCreados = [];

        foreach ($permisosPorModulo as $modulo => $acciones) {
            foreach ($acciones as $accion) {
                $name = "{$modulo}.{$accion}";
                $perm = Permission::firstOrCreate([
                    'name'       => $name,
                    'guard_name' => 'web',
                ]);
                $permisosCreados[] = $perm->name;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3) Asignar permisos por defecto por rol
        |--------------------------------------------------------------------------
        */

        // ADMIN: todo
        $admin->syncPermissions($permisosCreados);

        // TÉCNICO: panel técnico + resultados + (solo lectura de pacientes/consultas/pedidos)
        $tecnicoPerms = [
            // Dashboard técnico + Panel técnico
            'tecnico_dashboard.view',
            'tecnico_pedidos.view',
            'tecnico_pedidos.trabajar',
            'tecnico_pedidos.estado',
            'tecnico_pedidos.archivos',
            'tecnico_pedidos.fotos',

            // Resultados (ver/descargar/pdf)
            'resultados.view',
            'resultados.download',
            'resultados.fotos_pdf',

            // Lectura de datos base
            'pacientes.view',
            'consultas.view',
            'pedidos.view',
            'pedidos.pdf',
        ];
        $tecnico->syncPermissions($tecnicoPerms);

        // CLÍNICA: gestiona sus pacientes/consultas/pedidos + ver/descargar resultados
        $clinicaPerms = [
            // Pacientes
            'pacientes.view',
            'pacientes.create',
            'pacientes.update',

            // Consultas
            'consultas.view',
            'consultas.create',
            'consultas.update',

            // Pedidos
            'pedidos.view',
            'pedidos.create',
            'pedidos.update',
            'pedidos.pdf',

            // Resultados (ver/descargar/pdf)
            'resultados.view',
            'resultados.download',
            'resultados.fotos_pdf',
        ];
        $clinica->syncPermissions($clinicaPerms);

        /*
        |--------------------------------------------------------------------------
        | 4) Primer usuario = admin
        |--------------------------------------------------------------------------
        */
        $user = User::orderBy('id')->first();
        if ($user) {
            $user->syncRoles([$admin->name]);
            $user->is_active = true;
            $user->save();
        }

        // Limpia cache otra vez por seguridad
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
