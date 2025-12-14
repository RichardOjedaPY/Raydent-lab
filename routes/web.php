<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ClinicaController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PacienteController;
use App\Http\Controllers\Admin\ConsultaController;
use App\Http\Controllers\Admin\PedidoController;
use App\Http\Controllers\Admin\Tecnico\TecnicoPedidoController;
use App\Http\Controllers\Admin\Tecnico\TecnicoDashboardController;
use App\Http\Controllers\Admin\Clinica\ClinicaDashboardController;
use App\Http\Controllers\Admin\PedidoResultadoController;
use App\Http\Controllers\Admin\ActivityLogController;


Route::get('/', function () {
    return view('welcome');
});

/**
 * ✅ Redirect después del login según rol.
 */
Route::get('/dashboard', function () {
    $u = Auth::user();

    if (! $u) {
        return redirect()->route('login');
    }

    if ($u->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }

    if ($u->hasRole('tecnico')) {
        return redirect()->route('admin.tecnico.dashboard');
    }

    if ($u->hasRole('clinica')) {
        return redirect()->route('admin.clinica.dashboard');
    }

    // Fallback por permisos
    if ($u->can('pedidos.view')) {
        return redirect()->route('admin.pedidos.index');
    }

    abort(403);
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity-logs.index');

    Route::get('activity-logs/{activity}', [ActivityLogController::class, 'show'])
        ->name('activity-logs.show');
        /**
         * ============================
         * ADMIN (solo admin)
         * ============================
         */
        Route::middleware(['role:admin'])->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

            Route::resource('usuarios', UserController::class)
                ->parameters(['usuarios' => 'usuario'])
                ->except(['show']);
            Route::patch('usuarios/{usuario}/toggle-status', [UserController::class, 'toggleStatus'])
                ->name('usuarios.toggle-status');

            Route::resource('clinicas', ClinicaController::class)->except(['show']);
            Route::patch('clinicas/{clinica}/toggle-status', [ClinicaController::class, 'toggleStatus'])
                ->name('clinicas.toggle-status');

            Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
            Route::get('permissions/roles/{role}', [PermissionController::class, 'editRole'])->name('permissions.edit-role');
            Route::post('permissions/roles/{role}', [PermissionController::class, 'updateRole'])->name('permissions.update-role');
        });

        /**
         * ============================
         * CLÍNICA (clinica|admin)
         * ============================
         */
        Route::middleware(['role:clinica|admin'])
            ->prefix('clinica')
            ->name('clinica.')
            ->group(function () {
                Route::get('dashboard', [ClinicaDashboardController::class, 'index'])
                    ->name('dashboard');
            });

        /**
         * =========================================
         * RUTAS COMPARTIDAS (auth)
         * =========================================
         */
        Route::get('resultados/archivo/{archivo}/download', [PedidoResultadoController::class, 'downloadArchivo'])
            ->name('resultados.archivo.download');

        Route::get('resultados/foto/{foto}', [PedidoResultadoController::class, 'verFoto'])
            ->name('resultados.foto.ver');

        Route::get('pedidos/{pedido}/fotos-pdf', [PedidoResultadoController::class, 'pdfFotos'])
            ->name('pedidos.fotos_pdf');

        Route::get('pedidos/{pedido}/pdf', [PedidoController::class, 'pdf'])
            ->name('pedidos.pdf')
            ->middleware('permission:pedidos.view');

        /**
         * ============================
         * MÓDULOS (según permisos en tus controladores)
         * ============================
         * Nota: si tus controllers ya tienen middleware permission:* por método, esto queda OK.
         */
        Route::resource('pacientes', PacienteController::class)->parameters(['pacientes' => 'paciente']);
        Route::patch('pacientes/{paciente}/toggle-status', [PacienteController::class, 'toggleStatus'])
            ->name('pacientes.toggle-status');

        Route::resource('consultas', ConsultaController::class)->parameters(['consultas' => 'consulta']);

        Route::resource('pedidos', PedidoController::class)->parameters(['pedidos' => 'pedido']);

        /**
         * ============================
         * TÉCNICO (tecnico|admin)
         * ============================
         */
        Route::middleware(['role:tecnico|admin'])
            ->prefix('tecnico')
            ->name('tecnico.')
            ->group(function () {

                Route::get('dashboard', [TecnicoDashboardController::class, 'index'])
                    ->name('dashboard');

                Route::get('pedidos', [TecnicoPedidoController::class, 'index'])
                    ->name('pedidos.index');

                Route::get('pedidos/{pedido}', [TecnicoPedidoController::class, 'show'])
                    ->name('pedidos.show');

                Route::post('pedidos/{pedido}/estado', [TecnicoPedidoController::class, 'cambiarEstado'])
                    ->name('pedidos.estado');

                Route::post('pedidos/{pedido}/archivos', [TecnicoPedidoController::class, 'subirArchivos'])
                    ->name('pedidos.archivos');

                Route::post('pedidos/{pedido}/fotos', [TecnicoPedidoController::class, 'subirFotos'])
                    ->name('pedidos.fotos');
            });
    });

require __DIR__ . '/auth.php';
