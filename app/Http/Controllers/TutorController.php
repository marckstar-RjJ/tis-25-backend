<?php

namespace App\Http\Controllers;

use App\Models\Tutor;
use App\Models\Cuenta;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TutorController extends Controller
{
    /**
     * Registrar un nuevo estudiante bajo la tutela de un tutor
     */
    public function registerStudent(Request $request, $tutorId)
    {
        try {
            Log::info('Registro de estudiante por tutor', [
                'tutor_id' => $tutorId,
                'data' => $request->all()
            ]);

            // Validar que el tutor existe
            $tutor = Tutor::with('cuenta')->findOrFail($tutorId);

            // Validar datos del estudiante
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:255',
                'apellido' => 'required|string|max:255',
                'ci' => 'required|string|max:20',
                'email' => 'required|email|unique:cuentas',
                'fechaNacimiento' => 'required|date',
                'curso' => 'required|integer|min:1|max:12',
                'colegio' => 'required|exists:colegios,id',
            ]);

            DB::beginTransaction();

            try {
                // Generar una contraseña aleatoria
                $password = Str::random(8);

                // Crear la cuenta del estudiante
                $cuenta = Cuenta::create([
                    'email' => $validatedData['email'],
                    'password' => Hash::make($password),
                    'tipo_usuario' => 'estudiante',
                    'nombre' => $validatedData['nombre'],
                    'apellidos' => $validatedData['apellido'],
                    'ci' => $validatedData['ci']
                ]);

                // Crear el perfil del estudiante
                $estudiante = Estudiante::create([
                    'cuenta_id' => $cuenta->id,
                    'nombre' => $validatedData['nombre'],
                    'apellido' => $validatedData['apellido'],
                    'ci' => $validatedData['ci'],
                    'fecha_nacimiento' => $validatedData['fechaNacimiento'],
                    'curso' => $validatedData['curso'],
                    'colegio_id' => $validatedData['colegio'],
                    'tutor_id' => $tutorId,
                    'nombre_tutor' => $tutor->nombre,
                    'apellido_tutor' => $tutor->apellido,
                    'email_tutor' => $tutor->cuenta->email,
                    'celular_tutor' => $tutor->telefono
                ]);

                DB::commit();

                // Enviar correo con las credenciales
                // TODO: Implementar envío de correo con credenciales

                return response()->json([
                    'message' => 'Estudiante registrado exitosamente',
                    'student' => $estudiante->load(['cuenta', 'colegio', 'tutor']),
                    'credentials' => [
                        'email' => $validatedData['email'],
                        'password' => $password
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al registrar estudiante', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error en registro de estudiante por tutor', [
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