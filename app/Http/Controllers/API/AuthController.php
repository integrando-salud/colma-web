<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Auth\LoginRequest;
use App\Http\Resources\API\InfoUsuarioResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('documento', $request->documento)->first();

        if (! $user || ! Hash::check($request->password, $user->password) || $user->isNotA('paciente')) {
            return response()->json([
                'message' => 'Documento o contraseña incorrectos'
            ], 401);
        }

        return response()->json([
            'token' => $user->createToken('mobile-app')->plainTextToken,
            'usuario'  => new InfoUsuarioResource($user),
        ]);
    }
}
