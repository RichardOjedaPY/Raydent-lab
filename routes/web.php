<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ClinicaController;
use App\Http\Controllers\Admin\PermissionController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    // Siempre que se loguee, lo mandamos al panel Raydent
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        // Usuarios
        Route::resource('usuarios', UserController::class)
            ->parameters(['usuarios' => 'usuario'])
            ->except(['show']);
        Route::patch('usuarios/{usuario}/toggle-status', [UserController::class, 'toggleStatus'])
            ->name('usuarios.toggle-status');

        // Clínicas
        Route::resource('clinicas', ClinicaController::class)
            ->except(['show']);
        Route::patch('clinicas/{clinica}/toggle-status', [ClinicaController::class, 'toggleStatus'])
            ->name('clinicas.toggle-status');

        // Permisos (mapa)
        Route::get('permissions', [PermissionController::class, 'index'])
            ->name('permissions.index');

        // Editar permisos de un rol
        Route::get('permissions/roles/{role}', [PermissionController::class, 'editRole'])
            ->name('permissions.edit-role');

        Route::post('permissions/roles/{role}', [PermissionController::class, 'updateRole'])
            ->name('permissions.update-role');
    });


require __DIR__ . '/auth.php';
