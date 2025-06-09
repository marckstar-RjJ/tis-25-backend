<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ForgotPasswordController extends Controller
{
    public function checkUserEmail(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        return response()->json([
            'exists' => $user !== null
        ]);
    }

    public function generateResetToken(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Generar token de recuperación
        $token = Str::random(60);
        
        // Guardar token con expiración de 24 horas
        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => Carbon::now()
        ]);

        return response()->json([
            'token' => $token
        ]);
    }

    public function getEmailFromToken(Request $request)
    {
        $request->validate([
            'token' => 'required'
        ]);

        // Verificar token
        $passwordReset = DB::table('password_resets')
            ->where('token', Hash::make($request->token))
            ->first();

        if (!$passwordReset) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido'
            ], 404);
        }

        // Verificar si el token ha expirado (24 horas)
        if (Carbon::parse($passwordReset->created_at)->addHours(24) < Carbon::now()) {
            return response()->json([
                'success' => false,
                'message' => 'Token expirado'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'email' => $passwordReset->email
            ]
        ]);

        if (!$passwordReset) {
            return response()->json(['message' => 'Token inválido'], 404);
        }

        // Verificar si el token ha expirado (24 horas)
        if (Carbon::parse($passwordReset->created_at)->addHours(24) < Carbon::now()) {
            return response()->json(['message' => 'Token expirado'], 400);
        }

        return response()->json([
            'email' => $passwordReset->email
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed'
        ]);

        // Verificar token
        $passwordReset = DB::table('password_resets')
            ->where('token', $request->token)
            ->first();

        if (!$passwordReset) {
            return response()->json(['message' => 'Token inválido'], 404);
        }

        // Verificar si el token ha expirado (24 horas)
        if (Carbon::parse($passwordReset->created_at)->addHours(24) < Carbon::now()) {
            return response()->json(['message' => 'Token expirado'], 400);
        }

        // Actualizar la contraseña del usuario
        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();

            // Eliminar el token usado
            DB::table('password_resets')
                ->where('email', $request->email)
                ->delete();

            return response()->json([
                'message' => 'Contraseña actualizada exitosamente'
            ]);
        }

        return response()->json(['message' => 'Usuario no encontrado'], 404);
    }
}
