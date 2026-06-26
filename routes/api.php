<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PacienteController;
use App\Http\Controllers\API\TratamientoController;
use App\Http\Resources\API\InfoUsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return new InfoUsuarioResource($request->user());
    });

    Route::post('sync-data-paciente', [PacienteController::class, 'syncData'])
        ->name('sync-data-paciente');

    Route::post('pedido-ayuda', [PacienteController::class, 'pedidoAyuda'])
        ->name('pedido-ayuda');

    Route::get('tratamiento', [TratamientoController::class, 'getTratamiento'])
        ->name('tratamiento');

    Route::get('multimedia/{path}', [TratamientoController::class, 'getMultimedia'])
        ->where('path', '.*')
        ->name('multimedia');
});
