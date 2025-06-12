<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
            'fecha_nacimiento' => 'required|date',
            'curso' => 'required|integer',
            'colegio_id' => 'required|exists:colegios,id',
            'tutor_id' => 'nullable|exists:tutores,id',
            'email' => 'required|email|unique:cuentas',
            'password' => 'required|min:6',
        ]);

        DB::beginTransaction();
        try {
            // Crear usuario
            $user = User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'tipo_usuario' => 'estudiante',
            ]);

            // Crear estudiante
            $student = Student::create([
                'cuenta_id' => $user->id,
                'nombre' => $validated['nombre'],
                'apellido' => $validated['apellido'],
                'ci' => $validated['ci'],
                'fecha_nacimiento' => $validated['fecha_nacimiento'],
                'curso' => $validated['curso'],
                'colegio_id' => $validated['colegio_id'],
                'tutor_id' => $validated['tutor_id'] ?? null,
            ]);

            DB::commit();
            return response()->json($student->load(['user', 'college', 'tutor']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear estudiante'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'string|max:255',
            'apellido' => 'string|max:255',
            'ci' => 'string|max:20',
            'fecha_nacimiento' => 'date',
            'curso' => 'integer',
            'colegio_id' => 'exists:colegios,id',
            'tutor_id' => 'nullable|exists:tutores,id',
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
            
            // Obtener los datos directamente de la tabla cuentas
            $perfilCompleto = [
                'nombre' => $user->nombre,
                'apellido' => $user->apellidos,
                'ci' => $user->ci,
                'curso' => $user->curso,
                'celular' => $user->celular,
                'email' => $user->email
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
} 