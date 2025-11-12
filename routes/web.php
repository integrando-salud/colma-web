<?php

use App\Http\Controllers\Dashboard\Auth\LoginController;
use App\Http\Controllers\Dashboard\IndexController;
use App\Http\Controllers\Dashboard\InteraccionController;
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
        Route::post('guardar', [PacienteController::class, 'store'])
            ->name('dashboard.pacientes.store');
        Route::get('{paciente}/editar', [PacienteController::class, 'edit'])
            ->name('dashboard.pacientes.edit');
        Route::get('{paciente}/interacciones', [PacienteController::class, 'interacciones'])
            ->name('dashboard.pacientes.interacciones');
        Route::get('{paciente}/interacciones/crear', [PacienteController::class, 'createInteraccion'])
            ->name('dashboard.pacientes.createInteraccion');
    });

    Route::prefix('ajax')->group(function () {
        Route::prefix('interacciones')->group(function () {
            Route::get('razones-no-contacto', [InteraccionController::class, 'getRazonesNoContacto'])
                ->name('ajax.interacciones.razonesNoContacto');
            Route::get('modalidades', [InteraccionController::class, 'getModalidades'])
                ->name('ajax.interacciones.modalidades');
            Route::get('acciones', [InteraccionController::class, 'getAcciones'])
                ->name('ajax.interacciones.acciones');
            Route::get('motivos-contacto', [InteraccionController::class, 'getMotivosContacto'])
                ->name('ajax.interacciones.motivosContacto');
            Route::get('motivos-reprogramacion', [InteraccionController::class, 'getMotivosReprogramacion'])
                ->name('ajax.interacciones.motivosReprogramacion');
            Route::post('crear', [InteraccionController::class, 'create'])
                ->name('ajax.interacciones.create');
        });
    });
});
