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
        try {
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:255',
                'direccion' => 'required|string|max:255',
                'telefonoReferencia' => 'required|string|max:20',
                'codigoColegio' => 'required|string|max:4|unique:colegios,verification_code',
            ]);

            // Mapear los nombres de campo del frontend a los nombres de columna de la base de datos
            $colegioData = [
                'nombre' => $validatedData['nombre'],
                'direccion' => $validatedData['direccion'],
                'telefono' => $validatedData['telefonoReferencia'],
                'verification_code' => $validatedData['codigoColegio']
            ];

            \Log::info('Datos del colegio a crear:', $colegioData);

            $colegio = Colegio::create($colegioData);

            \Log::info('Colegio creado:', ['id' => $colegio->id, 'verification_code' => $colegio->verification_code]);

            return response()->json([
                'mensaje' => 'Colegio creado correctamente',
                'colegio' => $colegio
            ], 201, [
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'X-Requested-With, Content-Type, X-Token-Auth, Authorization',
            ]);
        } catch (ValidationException $e) {
            \Log::error('Error de validación:', $e->errors());
            return response()->json([
                'mensaje' => 'Error de validación',
                'errores' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al crear colegio:', ['error' => $e->getMessage()]);
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