<?php

use App\Http\Controllers\Dashboard\Auth\LoginController;
use App\Http\Controllers\Dashboard\IndexController;
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
    });
});
