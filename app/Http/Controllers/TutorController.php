<?php

namespace App\Http\Controllers;

use App\Models\Tutor;
use App\Models\Cuenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TutorController extends Controller
{
    /**
     * Registrar un nuevo estudiante bajo la tutela de un tutor
     */
    public function registerStudent(Request $request, $tutorId)
    {
        Log::info('Iniciando registro de estudiante por tutor', [
            'tutor_id' => $tutorId,
            'request_data' => $request->all()
        ]);

        try {
            // Validar que el tutor existe
            $tutor = Tutor::with('cuenta')->findOrFail($tutorId);
            Log::info('Tutor encontrado', ['tutor' => $tutor->toArray()]);

            // Validar datos del estudiante
            try {
                $validatedData = $request->validate([
                    'nombre' => 'required|string|max:255',
                    'apellido' => 'required|string|max:255',
                    'ci' => 'required|string|max:20',
                    'email' => 'required|email|unique:cuentas',
                    'fechaNacimiento' => 'required|date',
                    'curso' => 'required|integer|min:1|max:12',
                    'colegio' => 'required|exists:colegios,id'
                ]);
                Log::info('Datos validados correctamente', ['validated_data' => $validatedData]);
            } catch (ValidationException $e) {
                Log::error('Error de validación', ['errors' => $e->errors()]);
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $e->errors()
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Usar el CI como contraseña
                $password = $validatedData['ci'];
                Log::info('Usando CI como contraseña');

                // Crear la cuenta del estudiante
                $cuenta = Cuenta::create([
                    'email' => $validatedData['email'],
                    'password' => Hash::make($password),
                    'tipo_usuario' => 'estudiante',
                    'nombre' => $validatedData['nombre'],
                    'apellidos' => $validatedData['apellido'],
                    'ci' => $validatedData['ci'],
                    'fecha_nacimiento' => $validatedData['fechaNacimiento'],
                    'curso' => $validatedData['curso'],
                    'colegio_id' => $validatedData['colegio']
                ]);
                Log::info('Cuenta de estudiante creada', ['cuenta_id' => $cuenta->id]);

                DB::commit();
                Log::info('Transacción completada exitosamente');

                return response()->json([
                    'message' => 'Estudiante registrado exitosamente',
                    'credentials' => [
                        'email' => $validatedData['email'],
                        'password' => $password // Devolvemos el CI como contraseña
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error en la transacción', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error general en registro de estudiante', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error al registrar estudiante',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 