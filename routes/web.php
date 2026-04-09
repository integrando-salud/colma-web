<?php

use App\Http\Controllers\Dashboard\AjaxController;
use App\Http\Controllers\Dashboard\Auth\LoginController;
use App\Http\Controllers\Dashboard\IndexController;
use App\Http\Controllers\Dashboard\InteraccionController;
use App\Http\Controllers\Dashboard\PacienteController;
use App\Http\Controllers\Dashboard\UsuarioController;
use App\Http\Middleware\ProhibirPromotoresPeru;
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
            ->name('dashboard.usuarios.index')
            ->can('ver-usuarios');
        Route::post('guardar', [UsuarioController::class, 'store'])
            ->name('dashboard.usuarios.store')
            ->can('crear-usuarios')
            ->can('editar-usuarios');
        Route::delete('eliminar/{user}', [UsuarioController::class, 'delete'])
            ->name('dashboard.usuarios.delete')
            ->can('eliminar-usuarios');
        Route::put('actualizar-contrasena/{user}', [UsuarioController::class, 'updatePassword'])
            ->name('dashboard.usuarios.updatePassword')
            ->can('editar-usuarios');
    });

    Route::prefix('pacientes')->group(function () {
        Route::get('/', [PacienteController::class, 'index'])
            ->name('dashboard.pacientes.index');
        Route::middleware(ProhibirPromotoresPeru::class)->group(function () {
            Route::get('crear', [PacienteController::class, 'create'])
                ->name('dashboard.pacientes.create');
        });
        Route::post('guardar', [PacienteController::class, 'store'])
            ->name('dashboard.pacientes.store');
        Route::get('{paciente}/editar', [PacienteController::class, 'edit'])
            ->name('dashboard.pacientes.edit');
        Route::get('{paciente}/alertas', [PacienteController::class, 'alertas'])
            ->name('dashboard.pacientes.alertas');
        Route::get('{paciente}/alertas/historial', [PacienteController::class, 'alertasHistorial'])
            ->name('dashboard.pacientes.alertas.historial');
        Route::get('{paciente}/interacciones', [PacienteController::class, 'interacciones'])
            ->name('dashboard.pacientes.interacciones');
        Route::get('{paciente}/interacciones/crear', [PacienteController::class, 'createInteraccion'])
            ->name('dashboard.pacientes.createInteraccion');
        Route::get('{paciente}/interacciones/{interaccion}/editar', [PacienteController::class, 'editInteraccion'])
            ->name('dashboard.pacientes.editInteraccion');
        Route::get('{paciente}/datos-clinicos', [PacienteController::class, 'datosClinicos'])
            ->name('dashboard.pacientes.datosClinicos');
    });

    Route::post('interacciones/guardar', [InteraccionController::class, 'store'])
        ->name('interacciones.store');
    Route::post('interacciones/eliminar', [InteraccionController::class, 'delete'])
        ->name('interacciones.delete');

    Route::prefix('ajax')->group(function () {
        Route::get('centros-salud', [AjaxController::class, 'getCentrosSalud'])
            ->name('ajax.centrosSalud.get');
        Route::get('usuarios', [AjaxController::class, 'getUsuarios'])
            ->name('ajax.usuarios.get');
        Route::get('diagnosticos', [AjaxController::class, 'getDiagnosticos'])
            ->name('ajax.diagnosticos.get');
        Route::get('paquetes', [AjaxController::class, 'getPaquetes'])
            ->name('ajax.paquetes.get');

        Route::get('{usuario}/contar-pacientes-activos', [AjaxController::class, 'contarPacientesActivos'])
            ->name('ajax.usuario.contarPacientesActivos');
        Route::get('{usuario}/contar-pacientes-con-alertas-pendientes', [AjaxController::class, 'contarPacientesConAlertasPendientes'])
            ->name('ajax.usuario.contarPacientesConAlertasPendientes');
        Route::get('{usuario}/contar-contactos-pendientes', [AjaxController::class, 'contarContactosPendientes'])
            ->name('ajax.usuario.contarContactosPendientes');

        Route::patch('{paciente}/cambiar-estado', [AjaxController::class, 'cambiarEstadoPaciente'])
            ->name('ajax.paciente.cambiarEstado');
    });
});
