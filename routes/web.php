<?php

use App\Http\Controllers\Dashboard\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('ingresar', [LoginController::class, 'index'])
    ->name('dashboard.login');
Route::post('ingresar', [LoginController::class, 'login']);
Route::get('salir', [LoginController::class, 'logout'])
    ->name('dashboard.logout');

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('dashboard.index');
    })->name('dashboard.index');
});
