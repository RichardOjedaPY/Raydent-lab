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
        // ✅ Limpia cache de permisos/roles
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1) Normalizar roles
        $cliente = Role::where('name', 'cliente')->first();
        if ($cliente) {
            $cliente->name = 'clinica';
            $cliente->guard_name = 'web';
            $cliente->save();
        }

        $admin   = Role::firstOrCreate(['name' => 'admin',   'guard_name' => 'web']);
        $tecnico = Role::firstOrCreate(['name' => 'tecnico', 'guard_name' => 'web']);
        $clinica = Role::firstOrCreate(['name' => 'clinica', 'guard_name' => 'web']);

        // 2) Definición de Permisos
        $permisosPorModulo = [
            'usuarios'      => ['view', 'create', 'update', 'delete'],
            'clinicas'      => ['view', 'create', 'update', 'delete'],
            'pacientes'     => ['view', 'create', 'update', 'delete'],
            'consultas'     => ['view', 'create', 'update', 'delete'],
            'pedidos'       => ['view', 'create', 'update', 'delete', 'pdf'],
            'permissions'   => ['view', 'update'],
            'activity_logs' => ['view', 'show'],
            'tecnico_dashboard' => ['view'],
            'tecnico_pedidos'   => ['view', 'trabajar', 'estado', 'archivos', 'fotos'],
            'resultados'    => ['view', 'download', 'fotos_pdf'],
            
            //   Módulo Tarifario:  
            'tarifario'     => ['view', 'update'], 
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

        // 3) Asignación de Permisos por Rol

        // ADMIN: Tiene TODO (incluyendo tarifario.view y tarifario.update)
        $admin->syncPermissions($permisosCreados);

        // TÉCNICO: Por ahora no suele ver precios, pero si quisieras, añadirías 'tarifario.view' aquí.
        $tecnicoPerms = [
            'tecnico_dashboard.view',
            'tecnico_pedidos.view',
            'tecnico_pedidos.trabajar',
            'tecnico_pedidos.estado',
            'tecnico_pedidos.archivos',
            'tecnico_pedidos.fotos',
            'resultados.view',
            'resultados.download',
            'resultados.fotos_pdf',
            'pacientes.view',
            'consultas.view',
            'pedidos.view',
            'pedidos.pdf',
        ];
        $tecnico->syncPermissions($tecnicoPerms);

        // CLÍNICA: Sus permisos estándar
        $clinicaPerms = [
            'pacientes.view', 'pacientes.create', 'pacientes.update',
            'consultas.view', 'consultas.create', 'consultas.update',
            'pedidos.view', 'pedidos.create', 'pedidos.update', 'pedidos.pdf',
            'resultados.view', 'resultados.download', 'resultados.fotos_pdf',
        ];
        $clinica->syncPermissions($clinicaPerms);

        // 4) Usuario Inicial
        $user = User::orderBy('id')->first();
        if ($user) {
            $user->syncRoles([$admin->name]);
            $user->is_active = true;
            $user->save();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}