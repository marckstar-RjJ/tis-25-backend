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
            'token' => $token,
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

        // Mostrar todos los tokens en la base de datos para depuración
        $allTokens = DB::table('password_resets')->pluck('token');
        error_log('Tokens en la base de datos: ' . json_encode($allTokens));
        error_log('Token recibido: ' . $request->token);

        try {
            // Buscar el token ignorando espacios y case (colación insensible a mayúsculas/minúsculas)
            $passwordReset = DB::table('password_resets')
                ->whereRaw('TRIM(token) = TRIM(?)', [trim($request->token)])
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

            // Verificar que el email existe en la tabla cuentas
            $user = User::where('email', $passwordReset->email)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'email' => $user->email
                ]
            ]);

        } catch (\Exception $e) {
            error_log('Error en getEmailFromToken: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed'
        ]);

        try {
            // Verificar token
            $passwordReset = DB::table('password_resets')
                ->where('token', $request->token)
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

            // Verificar si el email coincide
            if ($passwordReset->email !== $request->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email no coincide'
                ], 400);
            }

            // Actualizar la contraseña del usuario
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            // Eliminar el token usado
            DB::table('password_resets')
                ->where('email', $request->email)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }
}
