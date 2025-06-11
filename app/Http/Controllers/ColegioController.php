<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Colegio;
use Illuminate\Validation\ValidationException;

class ColegioController extends Controller
{
    /**
     * Obtener todos los colegios
     */
    public function index()
    {
        try {
            // Obtener todos los colegios de la base de datos, seleccionando explícitamente los campos
            $colegios = Colegio::select('id', 'nombre', 'direccion', 'telefono', 'verification_code')->get();
            
            // Log para depuración
            \Log::info('Colegios obtenidos: ' . count($colegios));
            
            // Establecer encabezados CORS explícitamente
            return response()->json($colegios, 200)
                ->header('Content-Type', 'application/json')
                ->header('Access-Control-Allow-Origin', 'https://olimpiadas-sansi.netlify.app')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-Token-Auth, Authorization')
                ->header('Access-Control-Allow-Credentials', 'true');
        } catch (\Exception $e) {
            // Log del error para depuración
            \Log::error('Error en ColegioController@index: ' . $e->getMessage());
            
            // Retornar error con encabezados CORS
            return response()->json(['error' => 'Error al obtener colegios: ' . $e->getMessage()], 500)
                ->header('Content-Type', 'application/json')
                ->header('Access-Control-Allow-Origin', 'https://olimpiadas-sansi.netlify.app')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-Token-Auth, Authorization')
                ->header('Access-Control-Allow-Credentials', 'true');
        }
    }

    /**
     * Crear un nuevo colegio
     */
    public function store(Request $request)
    {
        fwrite(STDERR, "=== INICIO MÉTODO STORE ===\n");
        fwrite(STDERR, "Request recibido: " . json_encode($request->all()) . "\n");
        try {
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:255',
                'direccion' => 'required|string|max:255',
                'telefono' => 'required|string|max:20',
            ]);

            fwrite(STDERR, "=== INICIO CREACIÓN COLEGIO ===\n");
            fwrite(STDERR, "Datos validados: " . json_encode($validatedData) . "\n");

            // Generar código de verificación único de 4 dígitos
            do {
                $codigo = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                fwrite(STDERR, "Código generado: " . $codigo . "\n");
            } while (Colegio::where('verification_code', $codigo)->exists());

            $colegio = new Colegio();
            $colegio->nombre = $validatedData['nombre'];
            $colegio->direccion = $validatedData['direccion'];
            $colegio->telefono = $validatedData['telefono'];
            $colegio->verification_code = $codigo;

            fwrite(STDERR, "Colegio antes de guardar: " . json_encode([
                'nombre' => $colegio->nombre,
                'direccion' => $colegio->direccion,
                'telefono' => $colegio->telefono,
                'verification_code' => $colegio->verification_code
            ]) . "\n");

            $colegio->save();

            fwrite(STDERR, "Colegio después de guardar: " . json_encode([
                'id' => $colegio->id,
                'nombre' => $colegio->nombre,
                'verification_code' => $colegio->verification_code
            ]) . "\n");
            fwrite(STDERR, "=== FIN CREACIÓN COLEGIO ===\n");

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
            return response()->json([
                'mensaje' => 'Error de validación',
                'errores' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al crear colegio: ' . $e->getMessage());
            return response()->json([
                'mensaje' => 'Error al crear colegio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un colegio específico
     */
    public function show($id)
    {
        $colegio = Colegio::find($id);
        
        if (!$colegio) {
            return response()->json([
                'mensaje' => 'Colegio no encontrado'
            ], 404);
        }
        
        return response()->json($colegio, 200, [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'X-Requested-With, Content-Type, X-Token-Auth, Authorization',
        ]);
    }

    /**
     * Actualizar un colegio
     */
    public function update(Request $request, $id)
    {
        try {
            $colegio = Colegio::find($id);
            
            if (!$colegio) {
                return response()->json([
                    'mensaje' => 'Colegio no encontrado'
                ], 404);
            }
            
            $validatedData = $request->validate([
                'nombre' => 'string|max:255',
                'direccion' => 'string|max:255',
                'telefono' => 'string|max:20',
            ]);

            $colegio->update($validatedData);

            return response()->json([
                'mensaje' => 'Colegio actualizado correctamente',
                'colegio' => $colegio
            ], 200, [
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'X-Requested-With, Content-Type, X-Token-Auth, Authorization',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'mensaje' => 'Error de validación',
                'errores' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al actualizar colegio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un colegio
     */
    public function destroy($id)
    {
        $colegio = Colegio::find($id);
        
        if (!$colegio) {
            return response()->json([
                'mensaje' => 'Colegio no encontrado'
            ], 404);
        }
        
        $colegio->delete();
        
        return response()->json([
            'mensaje' => 'Colegio eliminado correctamente'
        ], 200, [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'X-Requested-With, Content-Type, X-Token-Auth, Authorization',
        ]);
    }
} 