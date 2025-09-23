<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required'],
            'password' => ['required'],
            'tipo_sesion' => ['required'],
        ]);

        $user = User::where('name', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password) || $user->isNotA('paciente')) {
            return response()->json([
                'message' => 'Credenciales inválidas.'
            ], 401);
        }

        return response()->json([
            'token' => $user->createToken('mobile-app')->plainTextToken,
            'user'  => $user,
            'tipo_sesion'  => $request->tipo_sesion,
        ]);
    }
}
