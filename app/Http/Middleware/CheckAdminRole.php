<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Cuenta;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Obtener el usuario autenticado
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'mensaje' => 'No autorizado. Debe iniciar sesión como administrador.',
                    'error' => 'UNAUTHORIZED'
                ], 401);
            }
            
            // Verificar si el usuario es administrador
            if ($user->tipo_usuario !== 'administrador') {
                return response()->json([
                    'mensaje' => 'No autorizado. Solo los administradores pueden realizar esta acción.',
                    'error' => 'FORBIDDEN',
                    'user_type' => $user->tipo_usuario
                ], 403);
            }
            
            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al verificar permisos de administrador',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 