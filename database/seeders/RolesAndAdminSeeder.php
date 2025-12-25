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
        /**
         * ✅ 0) Limpiar cache de Spatie (crítico cuando creás/actualizás permisos y roles)
         */
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /**
         * ✅ 1) Normalizar roles (si en algún momento existió "cliente", lo renombramos a "clinica")
         * - Esto evita tener dos roles diferentes para lo mismo.
         */
        $cliente = Role::where('name', 'cliente')->first();
        if ($cliente) {
            $cliente->name = 'clinica';
            $cliente->guard_name = 'web';
            $cliente->save();
        }

        /**
         * ✅ 2) Asegurar que existan los roles base
         */
        $admin   = Role::firstOrCreate(['name' => 'admin',   'guard_name' => 'web']);
        $tecnico = Role::firstOrCreate(['name' => 'tecnico', 'guard_name' => 'web']);
        $clinica = Role::firstOrCreate(['name' => 'clinica', 'guard_name' => 'web']);
        $cajero  = Role::firstOrCreate(['name' => 'cajero',  'guard_name' => 'web']);

        /**
         * ✅ 3) Definición de permisos por módulo
         *
         * IMPORTANTE:
         * - Tu pantalla "Mapa de permisos por rol" muestra una columna "Show".
         * - Si el permiso *.show NO existe, esa columna aparece como "—".
         * - Además, para poder entrar a /admin/pedidos/{id}, necesitás "pedidos.show"
         *   (no alcanza solo con "pedidos.view").
         */
        $permisosPorModulo = [
            // CRUDs
            'usuarios'      => ['view', 'show', 'create', 'update', 'delete'],
            'clinicas'      => ['view', 'show', 'create', 'update', 'delete'],
            'pacientes'     => ['view', 'show', 'create', 'update', 'delete'],
            'consultas'     => ['view', 'show', 'create', 'update', 'delete'],

            // Pedidos
            'pedidos'       => ['view', 'show', 'create', 'update', 'delete', 'pdf', 'liquidar'],

            // Seguridad / auditoría
            'permissions'   => ['view', 'update'],
            'activity_logs' => ['view', 'show'],

            // Técnico
            'tecnico_dashboard' => ['view'],
            'tecnico_pedidos'   => ['view', 'show', 'trabajar', 'estado', 'archivos', 'fotos'],

            // Resultados
            'resultados'    => ['view', 'download', 'fotos_pdf'],

            // Precios
            'tarifario'     => ['view', 'update'],

            // Cobros / liquidaciones
            'liquidaciones' => ['view', 'show'],

            // Pagos
            'pagos'         => ['view', 'show', 'create', 'pdf', 'delete'],
            // Estado de cuenta
            'estado_cuenta' => ['view'],

        ];

        /**
         * ✅ 4) Crear permisos si no existen
         */
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

        /**
         * ✅ 5) Asignación de permisos por rol
         */

        // 5.1 ADMIN: tiene todo
        $admin->syncPermissions($permisosCreados);

        // 5.2 TÉCNICO: trabaja pedidos desde su panel y puede ver pedido + pdf (según tu flujo)
        $tecnicoPerms = [
            'tecnico_dashboard.view',
            'tecnico_pedidos.view',
            'tecnico_pedidos.show',
            'tecnico_pedidos.trabajar',
            'tecnico_pedidos.estado',
            'tecnico_pedidos.archivos',
            'tecnico_pedidos.fotos',

            'resultados.view',
            'resultados.download',
            'resultados.fotos_pdf',

            // lectura base
            'pacientes.view',
            'consultas.view',
            'pedidos.view',
            'pedidos.show',
            'pedidos.pdf',
        ];
        $tecnico->syncPermissions($tecnicoPerms);

        // 5.3 CLÍNICA: puede gestionar lo propio (según tu sistema)
        $clinicaPerms = [
            // Clínicas / pacientes / consultas (propio)
            'pacientes.view',
            'pacientes.show',
            'pacientes.create',
            'pacientes.update',
        
            'consultas.view',
            'consultas.show',
            'consultas.create',
            'consultas.update',
        
            // Pedidos (propio)
            'pedidos.view',
            'pedidos.show',
            'pedidos.create',
            'pedidos.update',
            'pedidos.pdf',
        
            // Resultados (propio)
            'resultados.view',
            'resultados.download',
            'resultados.fotos_pdf',
        
            // ✅ Estado de cuenta + pagos (para ver historial y detalle)
            'estado_cuenta.view',
            'pagos.view',
            'pagos.show',
            'pagos.pdf',  
        ];
        $clinica->syncPermissions($clinicaPerms);
        
        $clinica->syncPermissions($clinicaPerms);

        // 5.4 CAJERO: cobra (pagos) y necesita ver pedido (show) para revisar antes de cobrar / imprimir
        $cajeroPerms = [
            // Cobros
            'liquidaciones.view',
            'liquidaciones.show',

            // Pagos
            'pagos.view',
            'pagos.show',
            'pagos.create',
            'pagos.pdf',

            // Referencia / navegación
            'pedidos.view',
            'pedidos.show',
            'pedidos.pdf',
            // ✅ Permiso SOLO para liquidar/cargar precios (sin editar pedido)
            'pedidos.liquidar',
            'estado_cuenta.view',
            'pagos.delete',
            

        ];
        $cajero->syncPermissions($cajeroPerms);

        /**
         * ✅ 6) Usuario inicial: el primer usuario queda como admin activo
         * (si ya tenés admin creado y no querés tocarlo, podés comentar este bloque)
         */
        $user = User::orderBy('id')->first();
        if ($user) {
            $user->syncRoles([$admin->name]);
            $user->is_active = true;
            $user->save();
        }

        /**
         * ✅ 7) Limpiar cache nuevamente al final (recomendado)
         */
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
