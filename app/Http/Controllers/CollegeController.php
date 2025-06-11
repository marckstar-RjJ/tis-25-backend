<?php

namespace App\Http\Controllers;

use App\Models\Colegio;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class CollegeController extends Controller
{
    public function index()
    {
        return response()->json(Colegio::all());
    }

    public function show($id)
    {
        $colegio = Colegio::findOrFail($id);
        return response()->json($colegio);
    }

    public function store(Request $request)
    {
        try {
            Log::info('=== INICIO CREACIÓN COLEGIO ===');
            Log::info('Datos recibidos: ' . json_encode($request->all()));

            // Validar los datos
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:255',
                'direccion' => 'required|string|max:255',
                'telefono' => 'required|string|max:20',
            ]);

            Log::info('Datos validados: ' . json_encode($validatedData));

            // Generar código de verificación único de 4 dígitos
            do {
                $codigo = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                Log::info('Código generado: ' . $codigo);
            } while (Colegio::where('verification_code', $codigo)->exists());

            // Crear el colegio usando el modelo
            $colegio = new Colegio();
            $colegio->nombre = $validatedData['nombre'];
            $colegio->direccion = $validatedData['direccion'];
            $colegio->telefono = $validatedData['telefono'];
            $colegio->verification_code = $codigo;
            $colegio->save();

            Log::info('Colegio creado: ' . json_encode([
                'id' => $colegio->id,
                'nombre' => $colegio->nombre,
                'verification_code' => $colegio->verification_code
            ]));

            Log::info('=== FIN CREACIÓN COLEGIO ===');

            return response()->json([
                'mensaje' => 'Colegio creado correctamente',
                'colegio' => $colegio
            ], 201)
                ->header('Content-Type', 'application/json')
                ->header('Access-Control-Allow-Origin', 'https://olimpiadas-sansi.netlify.app')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-Token-Auth, Authorization')
                ->header('Access-Control-Allow-Credentials', 'true');
        } catch (ValidationException $e) {
            Log::error('Error de validación: ' . json_encode($e->errors()));
            return response()->json([
                'mensaje' => 'Error de validación',
                'errores' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al crear colegio: ' . $e->getMessage());
            return response()->json([
                'mensaje' => 'Error al crear colegio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $colegio = Colegio::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'string|max:255|unique:colegios,nombre,' . $id,
            'direccion' => 'string|max:255',
            'telefono' => 'string|max:20',
        ]);

        $colegio->update($validated);

        return response()->json($colegio);
    }

    public function destroy($id)
    {
        $colegio = Colegio::findOrFail($id);
        $colegio->delete();

        return response()->json(null, 204);
    }
} 