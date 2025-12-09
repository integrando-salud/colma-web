<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Resources\API\InfoUsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return new InfoUsuarioResource($request->user());
})->middleware('auth:sanctum');

Route::post('login', [AuthController::class, 'login']);
