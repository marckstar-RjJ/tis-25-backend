<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class StudentController extends Controller
{
    public function index()
    {
        return response()->json(Student::with(['user', 'college', 'tutor'])->get());
    }

    public function show($id)
    {
        $student = Student::with(['user', 'college', 'tutor', 'areas'])->findOrFail($id);
        return response()->json($student);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'ci' => 'required|string|max:20',
            'fechaNacimiento' => 'required|date',
            'curso' => 'required|integer',
            'colegio' => 'required|exists:colegios,id',
            'tutorId' => 'nullable|exists:tutores,id',
            'email' => 'required|email|unique:cuentas',
            'password' => 'required|min:6',
            'celular' => 'required|string|max:20',
            'nombreTutor' => 'required|string|max:255',
            'apellidosTutor' => 'required|string|max:255',
            'emailTutor' => 'required|email',
            'celularTutor' => 'required|string|max:20',
        ]);

        DB::beginTransaction();
        try {
            // Crear usuario en la tabla cuentas
            $user = User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'tipo_usuario' => 'estudiante',
                'nombre' => $validated['nombre'],
                'apellidos' => $validated['apellido'],
                'ci' => $validated['ci'],
            ]);

            // Crear estudiante
            $student = Student::create([
                'cuenta_id' => $user->id,
                'fecha_nacimiento' => $validated['fechaNacimiento'],
                'curso' => $validated['curso'],
                'colegio_id' => $validated['colegio'],
                'celular' => $validated['celular'],
                'nombre_tutor' => $validated['nombreTutor'],
                'apellido_tutor' => $validated['apellidosTutor'],
                'email_tutor' => $validated['emailTutor'],
                'celular_tutor' => $validated['celularTutor'],
            ]);

            DB::commit();
            return response()->json($student->load(['user', 'college', 'tutor']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al crear estudiante desde StudentController:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error al crear estudiante: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'fecha_nacimiento' => 'date',
            'curso' => 'integer',
            'colegio_id' => 'exists:colegios,id',
            'celular' => 'string|max:20',
            'nombre_tutor' => 'string|max:255',
            'apellido_tutor' => 'string|max:255',
            'email_tutor' => 'email',
            'celular_tutor' => 'string|max:20',
        ]);

        $student->update($validated);

        return response()->json($student->load(['user', 'college', 'tutor']));
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();

        return response()->json(null, 204);
    }

    public function getAreas($id)
    {
        $student = Student::findOrFail($id);
        return response()->json($student->areas);
    }

    public function getCurrentProfile(Request $request)
    {
        try {
            // Obtener el usuario autenticado
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['message' => 'Usuario no autenticado'], 401);
            }
            
            if ($user->tipo_usuario !== 'estudiante') {
                return response()->json(['message' => 'El usuario no es un estudiante'], 403);
            }
            
            // Obtener los datos del estudiante de la tabla estudiantes
            $estudiante = Student::select('fecha_nacimiento', 'curso', 'colegio_id', 'celular', 'nombre_tutor', 'apellido_tutor', 'email_tutor', 'celular_tutor')
                ->with(['college:id,nombre'])
                ->where('cuenta_id', $user->id)
                ->first();
                
            if (!$estudiante) {
                return response()->json(['message' => 'Perfil de estudiante no encontrado'], 404);
            }
            
            // Combinar los datos del usuario y los datos del estudiante
            $perfilCompleto = [
                'nombre' => $user->nombre,
                'apellido' => $user->apellidos,
                'ci' => $user->ci,
                'email' => $user->email,
                'fecha_nacimiento' => $estudiante->fecha_nacimiento,
                'curso' => $estudiante->curso,
                'colegio' => $estudiante->college ? $estudiante->college->nombre : null,
                'celular' => $estudiante->celular,
                'nombre_tutor' => $estudiante->nombre_tutor,
                'apellido_tutor' => $estudiante->apellido_tutor,
                'email_tutor' => $estudiante->email_tutor,
                'celular_tutor' => $estudiante->celular_tutor
            ];
            
            return response()->json($perfilCompleto);
            
        } catch (\Exception $e) {
            \Log::error('Error al obtener el perfil del estudiante: ' . $e->getMessage());
            return response()->json(['message' => 'Error al obtener el perfil: ' . $e->getMessage()], 500);
        }
    }
    
    public function getAvailableAreas($id)
    {
        $student = Student::findOrFail($id);
        $enrolledAreas = $student->areas->pluck('id');
        $availableAreas = Area::whereNotIn('id', $enrolledAreas)->get();
        
        return response()->json($availableAreas);
    }

    public function enrollInAreas(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'area_ids' => 'required|array',
            'area_ids.*' => 'exists:areas,id',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['area_ids'] as $areaId) {
                $student->areas()->attach($areaId, [
                    'estado' => 'activo',
                    'fecha_inscripcion' => now(),
                ]);
            }

            // Crear orden de pago
            $montoPorArea = 16.00; // Este valor debería venir de la configuración
            $montoTotal = count($validated['area_ids']) * $montoPorArea;

            $ordenPago = $student->ordenesPago()->create([
                'monto' => $montoTotal,
                'estado' => 'pendiente',
            ]);

            // Crear detalles de la orden
            foreach ($validated['area_ids'] as $areaId) {
                $ordenPago->detalles()->create([
                    'area_id' => $areaId,
                    'monto' => $montoPorArea,
                ]);
            }

            DB::commit();
            return response()->json([
                'message' => 'Inscripción exitosa',
                'orden_pago' => $ordenPago->load('detalles'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al inscribir en áreas'], 500);
        }
    }

    public function getStudentsByCollege($collegeId)
    {
        try {
            $students = Student::with(['user', 'college'])
                ->where('colegio_id', $collegeId)
                ->get();
            
            return response()->json($students);
        } catch (\Exception $e) {
            \Log::error('Error al obtener estudiantes por colegio: ' . $e->getMessage());
            return response()->json(['message' => 'Error al obtener estudiantes del colegio'], 500);
        }
    }

    public function getStudentsByTutor($tutorId)
    {
        try {
            // Obtener el tutor y su colegio
            $tutor = \App\Models\Tutor::with('cuenta')->findOrFail($tutorId);
            
            // Obtener todos los estudiantes del colegio del tutor
            $students = Student::with(['user', 'college'])
                ->where('colegio_id', $tutor->colegio_id)
                ->get();
            
            return response()->json($students);
        } catch (\Exception $e) {
            \Log::error('Error al obtener estudiantes por tutor: ' . $e->getMessage());
            return response()->json(['message' => 'Error al obtener estudiantes del tutor'], 500);
        }
    }
} 