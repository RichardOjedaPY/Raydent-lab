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
        // ─────────────────────────────────────────────
        // 1) Normalizar roles
        // ─────────────────────────────────────────────

        // Renombrar 'cliente' -> 'clinica' si existía
        $cliente = Role::where('name', 'cliente')->first();
        if ($cliente) {
            $cliente->name = 'clinica';
            $cliente->save();
        }

        // Crear roles base
        $admin   = Role::firstOrCreate(['name' => 'admin',   'guard_name' => 'web']);
        $tecnico = Role::firstOrCreate(['name' => 'tecnico', 'guard_name' => 'web']);
        $clinica = Role::firstOrCreate(['name' => 'clinica', 'guard_name' => 'web']);

        // ─────────────────────────────────────────────
        // 2) Crear permisos por módulo y acción
        // ─────────────────────────────────────────────
        // Limpiamos permisos viejos (si quedaron de pruebas)
        Permission::whereIn('name', [
            'clinicas.manage', 'clinicas.view',
            'pacientes.manage', 'pacientes.view',
            'pedidos.manage',   'pedidos.view',
            'usuarios.manage',  'usuarios.view',
        ])->delete();

        $modulos  = ['usuarios', 'clinicas', 'pacientes', 'pedidos'];
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

        // ─────────────────────────────────────────────
        // 3) Asignar permisos por defecto a cada rol
        // ─────────────────────────────────────────────

        // Admin tiene todo
        $admin->syncPermissions($permisosCreados);

        // Técnico: puede trabajar con pacientes y pedidos (CRUD),
        // pero no toca usuarios ni clínicas
        $tecnicoPerms = [
            'pacientes.view', 'pacientes.create', 'pacientes.update', 'pacientes.delete',
            'pedidos.view',   'pedidos.create',   'pedidos.update',   'pedidos.delete',
        ];
        $tecnico->syncPermissions($tecnicoPerms);

        // Clínica: puede manejar SUS pacientes y pedidos,
        // pero sin borrar (por ejemplo) – lo ajustamos si quieres
        $clinicaPerms = [
            'pacientes.view', 'pacientes.create', 'pacientes.update',
            'pedidos.view',   'pedidos.create',   'pedidos.update',
        ];
        $clinica->syncPermissions($clinicaPerms);

        // ─────────────────────────────────────────────
        // 4) Asegurar que el primer usuario sea admin
        // ─────────────────────────────────────────────
        $user = User::orderBy('id')->first();

        if ($user) {
            $user->syncRoles([$admin->name]);
            $user->is_active = true;
            $user->save();
        }
    }
}
