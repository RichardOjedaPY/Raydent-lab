<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Normalizar roles
        $cliente = Role::where('name', 'cliente')->first();
        if ($cliente) {
            $cliente->name = 'clinica';
            $cliente->save();
        }

        $admin   = Role::firstOrCreate(['name' => 'admin',   'guard_name' => 'web']);
        $tecnico = Role::firstOrCreate(['name' => 'tecnico', 'guard_name' => 'web']);
        $clinica = Role::firstOrCreate(['name' => 'clinica', 'guard_name' => 'web']);

        // 2) Crear permisos por módulo y acción
        // Borramos permisos viejos de pruebas
        Permission::whereIn('name', [
            'clinicas.manage', 'clinicas.view',
            'pacientes.manage', 'pacientes.view',
            'pedidos.manage',   'pedidos.view',
            'usuarios.manage',  'usuarios.view',
        ])->delete();

        // 🔹 AQUI incluimos CONSULTAS como nuevo módulo
        $modulos  = ['usuarios', 'clinicas', 'pacientes', 'consultas', 'pedidos'];
        $acciones = ['view', 'create', 'update', 'delete'];

        $permisosCreados = [];

        foreach ($modulos as $mod) {
            foreach ($acciones as $acc) {
                $name = "{$mod}.{$acc}";
                $perm = Permission::firstOrCreate([
                    'name'       => $name,
                    'guard_name' => 'web',
                ]);
                $permisosCreados[] = $perm->name;
            }
        }

        // 3) Asignar permisos por defecto

        // Admin: todo
        $admin->syncPermissions($permisosCreados);

        // Técnico: trabaja con pacientes, consultas (solo ver) y pedidos
        $tecnicoPerms = [
            // pacientes
            'pacientes.view', 'pacientes.create', 'pacientes.update', 'pacientes.delete',
            // consultas (por ahora solo ver)
            'consultas.view',
            // pedidos
            'pedidos.view', 'pedidos.create', 'pedidos.update', 'pedidos.delete',
        ];
        $tecnico->syncPermissions($tecnicoPerms);

        // Clínica: maneja sus pacientes, consultas y pedidos (sin borrar por defecto)
        $clinicaPerms = [
            // pacientes
            'pacientes.view', 'pacientes.create', 'pacientes.update',
            // consultas
            'consultas.view', 'consultas.create', 'consultas.update',
            // pedidos
            'pedidos.view', 'pedidos.create', 'pedidos.update',
        ];
        $clinica->syncPermissions($clinicaPerms);

        // 4) Primer usuario = admin
        $user = User::orderBy('id')->first();
        if ($user) {
            $user->syncRoles([$admin->name]);
            $user->is_active = true;
            $user->save();
        }
    }
}
