<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $origin = $request->header('Origin');
        $allowedOrigins = config('cors.allowed_origins', []);

        if (in_array($origin, $allowedOrigins)) {
            $headers = [
                'Access-Control-Allow-Origin' => $origin,
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Max-Age' => '86400',
            ];

            if ($request->isMethod('OPTIONS')) {
                return response()->json('OK', 200, $headers);
            }

            $response = $next($request);
            foreach ($headers as $key => $value) {
                $response->headers->set($key, $value);
            }
            return $response;
        }

        return $next($request);
    }
} 