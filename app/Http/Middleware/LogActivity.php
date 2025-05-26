<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Obtener información de la solicitud
        $method = $request->method();
        $url = $request->fullUrl();
        $ip = $request->ip();
        $user = $request->user() ? $request->user()->email : 'guest';

        // Registrar la actividad
        Log::info('Actividad del Usuario', [
            'usuario' => $user,
            'metodo' => $method,
            'url' => $url,
            'ip' => $ip,
            'status' => $response->status(),
            'tiempo' => now()->toDateTimeString()
        ]);

        return $response;
    }
} 