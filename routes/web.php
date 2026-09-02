<?php

use App\Http\Controllers\Dashboard\AjaxController;
use App\Http\Controllers\Dashboard\Auth\LoginController;
use App\Http\Controllers\Dashboard\IndexController;
use App\Http\Controllers\Dashboard\IndicadorController;
use App\Http\Controllers\Dashboard\InteraccionController;
use App\Http\Controllers\Dashboard\PacienteController;
use App\Http\Controllers\Dashboard\UsuarioController;
use App\Http\Middleware\ProhibirPromotoresPeru;
use App\Http\Middleware\SetUserTimezone;
use App\Models\Paciente;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('ingresar', [LoginController::class, 'index'])
        ->name('dashboard.login');
    Route::post('ingresar', [LoginController::class, 'login']);
});
Route::middleware(['auth', SetUserTimezone::class])->group(function () {
    Route::post('salir', [LoginController::class, 'logout'])
        ->name('dashboard.logout');

    Route::get('/', [IndexController::class, 'showIndex'])
        ->name('dashboard.index');

    Route::prefix('usuarios')->group(function () {
        Route::get('/', [UsuarioController::class, 'index'])
            ->name('dashboard.usuarios.index')
            ->can('ver-usuarios');
        Route::post('guardar', [UsuarioController::class, 'store'])
            ->name('dashboard.usuarios.store');
        Route::delete('eliminar/{user}', [UsuarioController::class, 'delete'])
            ->name('dashboard.usuarios.delete')
            ->can('eliminar-usuarios');
        Route::put('actualizar-contrasena/{user}', [UsuarioController::class, 'updatePassword'])
            ->name('dashboard.usuarios.updatePassword')
            ->can('editar-usuarios');
    });

    Route::prefix('pacientes')->group(function () {
        Route::get('/', [PacienteController::class, 'index'])
            ->name('dashboard.pacientes.index')
            ->can('viewAny', Paciente::class);
        Route::middleware(ProhibirPromotoresPeru::class)->group(function () {
            Route::get('crear', [PacienteController::class, 'create'])
                ->name('dashboard.pacientes.create')
                ->can('create', Paciente::class);

            Route::post('guardar', [PacienteController::class, 'store'])
                ->name('dashboard.pacientes.store');
        });
        Route::get('{paciente}/editar', [PacienteController::class, 'edit'])
            ->name('dashboard.pacientes.edit')
            ->can('update', 'paciente');
        Route::get('{paciente}/alertas', [PacienteController::class, 'alertas'])
            ->name('dashboard.pacientes.alertas')
            ->can('view', 'paciente');
        Route::get('{paciente}/alertas/historial', [PacienteController::class, 'alertasHistorial'])
            ->name('dashboard.pacientes.alertas.historial')
            ->can('view', 'paciente');
        Route::get('{paciente}/interacciones', [PacienteController::class, 'interacciones'])
            ->name('dashboard.pacientes.interacciones')
            ->can('view', 'paciente');
        Route::get('{paciente}/interacciones/crear', [PacienteController::class, 'createInteraccion'])
            ->name('dashboard.pacientes.createInteraccion')
            ->can('view', 'paciente');
        Route::get('{paciente}/interacciones/{interaccion}/editar', [PacienteController::class, 'editInteraccion'])
            ->name('dashboard.pacientes.editInteraccion')
            ->can('view', 'paciente');
        Route::get('{paciente}/datos-clinicos', [PacienteController::class, 'datosClinicos'])
            ->name('dashboard.pacientes.datosClinicos')
            ->can('view', 'paciente');
    });

    Route::get('indicadores', [IndicadorController::class, 'index'])
        ->name('dashboard.indicadores.index')
        ->can('ver-indicadores');

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

        Route::get('{usuario}/contar-pacientes-activos', [AjaxController::class, 'contarPacientesActivos'])
            ->name('ajax.usuario.contarPacientesActivos');
        Route::get('{usuario}/contar-pacientes', [AjaxController::class, 'contarPacientes'])
            ->name('ajax.usuario.contarPacientes');
        Route::get('{usuario}/contar-pacientes-con-alertas-pendientes', [AjaxController::class, 'contarPacientesConAlertasPendientes'])
            ->name('ajax.usuario.contarPacientesConAlertasPendientes');
        Route::get('{usuario}/contar-contactos-pendientes', [AjaxController::class, 'contarContactosPendientes'])
            ->name('ajax.usuario.contarContactosPendientes');

        Route::patch('{paciente}/cambiar-estado', [AjaxController::class, 'cambiarEstadoPaciente'])
            ->name('ajax.paciente.cambiarEstado')
            ->can('changeStatus', 'paciente');
        Route::get('{paciente}/entrenamiento-actual', [AjaxController::class, 'getEntrenamientoActual'])
            ->name('ajax.paciente.entrenamientoActual')
            ->can('view', 'paciente');
        Route::post('{paciente}/resetear-datos', [AjaxController::class, 'resetearDatosPaciente'])
            ->name('ajax.paciente.resetearDatos')
            ->can('view', 'paciente');
    });
});
