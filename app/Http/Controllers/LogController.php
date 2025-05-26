<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MongoDB\Laravel\Facades\MongoDB;

class LogController extends Controller
{
    public function store(Request $request)
    {
        try {
            $logData = $request->all();
            
            // Agregar información adicional
            $logData['ip'] = $request->ip();
            $logData['created_at'] = now();
            
            // Guardar en MongoDB
            MongoDB::connection()
                ->database('logs')
                ->collection('frontend_logs')
                ->insertOne($logData);

            // También guardar en el log de Laravel
            Log::info('Frontend Log', $logData);

            return response()->json(['message' => 'Log registrado exitosamente'], 201);
        } catch (\Exception $e) {
            Log::error('Error al guardar log del frontend: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar el log'], 500);
        }
    }

    public function index()
    {
        try {
            $logs = MongoDB::connection()
                ->database('logs')
                ->collection('frontend_logs')
                ->find()
                ->toArray();

            return response()->json($logs);
        } catch (\Exception $e) {
            Log::error('Error al obtener logs: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener los logs'], 500);
        }
    }
} 