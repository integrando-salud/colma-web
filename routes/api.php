<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CentroSaludController;
use App\Http\Controllers\API\DescargasController;
use App\Http\Controllers\API\DiagnosticoController;
use App\Http\Controllers\API\PaqueteController;
use App\Http\Controllers\API\UsuarioController;
use App\Http\Resources\API\InfoUsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return new InfoUsuarioResource($request->user());
})->middleware('auth:sanctum');

Route::post('login', [AuthController::class, 'login']);

Route::get('centros-salud', [CentroSaludController::class, 'get'])
    ->name('api.centrosSalud.get');

Route::get('usuarios', [UsuarioController::class, 'get'])
    ->name('api.usuarios.get');

Route::get('diagnosticos', [DiagnosticoController::class, 'get'])
    ->name('api.diagnosticos.get');

Route::get('paquetes', [PaqueteController::class, 'get'])
    ->name('api.paquetes.get');

Route::get('descargas', [DescargasController::class, 'download'])
    ->name('api.descargas');
