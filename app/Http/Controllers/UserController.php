<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Estudiante;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::all());
    }

    public function show($id)
    {
        $user = User::with(['estudiante.colegio'])->findOrFail($id);
        return response()->json($user);
    }

    public function store(Request $request)
    {
        \Log::info('[DEBUG_REGISTRO] Solicitud de registro recibida', [
            'data' => $request->all()
        ]);
        DB::beginTransaction();

        try {
            \Log::info('Solicitud de registro recibida', [
                'data' => $request->all()
            ]);

            $rules = [
                'email' => 'required|email|unique:cuentas',
                'password' => 'required|min:6',
                'tipoUsuario' => ['required', Rule::in(['estudiante', 'tutor', 'administrador'])],
                'nombre' => 'required|string|max:255',
                'apellidos' => 'required|string|max:255',
                'ci' => 'required|string|max:20',
            ];

            // Agregar reglas específicas según el tipo de usuario
            if ($request->input('tipoUsuario') === 'estudiante') {
                $rules = array_merge($rules, [
                    'fechaNacimiento' => 'required|date',
                    'curso' => 'required|integer',
                    'colegio' => 'required|exists:colegios,id',
                    'celular' => 'required|string|max:20',
                    'nombreTutor' => 'required|string|max:255',
                    'apellidosTutor' => 'required|string|max:255',
                    'emailTutor' => 'required|email',
                    'celularTutor' => 'required|string|max:20',
                ]);
            } else if ($request->input('tipoUsuario') === 'tutor') {
                $rules = array_merge($rules, [
                    'celular' => 'required|string|max:20',
                    'colegio' => 'required|exists:colegios,id',
                    'departamento' => 'nullable|string|max:255',
                    'verification_code' => 'required|string|size:4',
                ]);
            }

            $validatedData = $request->validate($rules);

            \Log::info('Datos validados:', $validatedData);

            $user = User::create([
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'tipo_usuario' => $validatedData['tipoUsuario'],
                'nombre' => $validatedData['nombre'],
                'apellidos' => $validatedData['apellidos'],
                'ci' => $validatedData['ci']
            ]);

            \Log::info('Usuario creado:', ['user' => $user->toArray()]);

            if ($validatedData['tipoUsuario'] === 'estudiante') {
                // Registrar todos los datos disponibles para depuración
                \Log::info('Datos para crear estudiante:', [
                    'cuenta_id' => $user->id,
                    'validatedData' => $validatedData,
                    'request_all' => $request->all()
                ]);
                
                // Crear el estudiante con los campos correctos
                $estudiante = Estudiante::create([
                    'cuenta_id' => $user->id,
                    'nombre' => $validatedData['nombre'],
                    'apellido' => $validatedData['apellidos'],
                    'ci' => $validatedData['ci'],
                    'fecha_nacimiento' => $validatedData['fechaNacimiento'],
                    'curso' => (int)$validatedData['curso'],
                    'colegio_id' => (int)$validatedData['colegio'],
                    'celular' => $validatedData['celular'],
                    'nombre_tutor' => $validatedData['nombreTutor'],
                    'apellido_tutor' => $validatedData['apellidosTutor'],
                    'email_tutor' => $validatedData['emailTutor'],
                    'celular_tutor' => $validatedData['celularTutor'],
                ]);
                
                // Registrar el estudiante creado para verificar
                \Log::info('Estudiante creado:', [
                    'id' => $estudiante->id,
                    'curso' => $estudiante->curso,
                    'colegio_id' => $estudiante->colegio_id
                ]);
                \Log::info('Estudiante creado.');
            } else if ($validatedData['tipoUsuario'] === 'tutor') {
                $colegio = \App\Models\Colegio::findOrFail($validatedData['colegio']);
                
                if ($colegio->verification_code !== $validatedData['verification_code']) {
                    throw new \Exception('Código de verificación incorrecto para el colegio seleccionado');
                }

                \Log::info('Creando tutor con datos:', [
                    'cuenta_id' => $user->id,
                    'nombre' => $validatedData['nombre'],
                    'apellido' => $validatedData['apellidos'],
                    'ci' => $validatedData['ci'],
                    'telefono' => $validatedData['celular'],
                    'colegio_id' => $validatedData['colegio'] ?? null,
                    'departamento' => $validatedData['departamento'] ?? null,
                ]);

                Tutor::create([
                    'cuenta_id' => $user->id,
                    'nombre' => $validatedData['nombre'],
                    'apellido' => $validatedData['apellidos'],
                    'ci' => $validatedData['ci'],
                    'telefono' => $validatedData['celular'],
                    'colegio_id' => $validatedData['colegio'] ?? null,
                    'departamento' => $validatedData['departamento'] ?? null,
                ]);
                \Log::info('Tutor creado exitosamente.');
            }

            DB::commit();

            return response()->json([
                'message' => 'Usuario registrado correctamente',
                'user' => $user
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('Error de validación en registro', [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al registrar usuario', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'message' => 'Error al registrar usuario',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'email' => ['email', Rule::unique('cuentas')->ignore($id)],
            'tipo_usuario' => [Rule::in(['estudiante', 'tutor', 'administrador'])],
        ]);

        $user->update($validated);

        return response()->json($user);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(null, 204);
    }

    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'message' => 'Credenciales inválidas'
                ], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            
            // Obtener información completa del perfil según el tipo de usuario
            $profileData = null;
            
            if ($user->tipo_usuario === 'estudiante') {
                // Cargar los datos del estudiante incluyendo el colegio
                $estudiante = Estudiante::where('cuenta_id', $user->id)
                    ->first();
                    
                // Si se encontró el estudiante, incluir sus datos
                if ($estudiante) {
                    // Obtener el nombre del colegio si existe
                    $colegio = null;
                    if ($estudiante->colegio_id) {
                        $colegio = DB::table('colegios')
                            ->where('id', $estudiante->colegio_id)
                            ->first();
                    }
                    
                    // Registrar qué se está devolviendo
                    \Log::info('Datos de estudiante para login:', [
                        'estudiante_id' => $estudiante->id,
                        'curso' => $estudiante->curso,
                        'colegio_id' => $estudiante->colegio_id,
                        'colegio' => $colegio ? $colegio->nombre : null
                    ]);
                    
                    // Combinar datos de usuario y estudiante
                    $userData = $user->toArray();
                    $profileData = $estudiante->toArray();
                    
                    // Añadir el nombre del colegio si existe
                    if ($colegio) {
                        $profileData['colegio'] = $colegio->nombre;
                    }
                    
                    // Combinar todos los datos
                    $fullUserData = array_merge($userData, $profileData);
                    
                    return response()->json([
                        'user' => $fullUserData,
                        'token' => $token
                    ]);
                }
            } else if ($user->tipo_usuario === 'tutor') {
                // Cargar los datos del tutor
                $tutor = Tutor::where('cuenta_id', $user->id)->first();
                if ($tutor) {
                    $userData = $user->toArray();
                    $profileData = $tutor->toArray();
                    $fullUserData = array_merge($userData, $profileData);
                    
                    return response()->json([
                        'user' => $fullUserData,
                        'token' => $token
                    ]);
                }
            }
            
            // Si no se encontró perfil o no es estudiante/tutor, devolver solo los datos básicos
            return response()->json([
                'user' => $user,
                'token' => $token
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en login:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error al iniciar sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 