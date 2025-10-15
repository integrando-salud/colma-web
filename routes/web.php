<?php

use App\Http\Controllers\Dashboard\Auth\LoginController;
use App\Http\Controllers\Dashboard\IndexController;
use App\Http\Controllers\Dashboard\PacienteController;
use App\Http\Controllers\Dashboard\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('ingresar', [LoginController::class, 'index'])
    ->name('dashboard.login');
Route::post('ingresar', [LoginController::class, 'login']);
Route::get('salir', [LoginController::class, 'logout'])
    ->name('dashboard.logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [IndexController::class, 'showIndex'])
        ->name('dashboard.index');

    Route::prefix('usuarios')->group(function () {
        Route::get('/', [UsuarioController::class, 'index'])
            ->name('dashboard.usuarios.index');
        Route::post('guardar', [UsuarioController::class, 'store'])
            ->name('dashboard.usuarios.store');
        Route::delete('eliminar/{user}', [UsuarioController::class, 'delete'])
            ->name('dashboard.usuarios.delete');
        Route::put('actualizar-contrasena/{user}', [UsuarioController::class, 'updatePassword'])
            ->name('dashboard.usuarios.updatePassword');
    });

    Route::prefix('pacientes')->group(function () {
        Route::get('/', [PacienteController::class, 'index'])
            ->name('dashboard.pacientes.index');
        Route::get('crear', [PacienteController::class, 'create'])
            ->name('dashboard.pacientes.create');
    });
});
