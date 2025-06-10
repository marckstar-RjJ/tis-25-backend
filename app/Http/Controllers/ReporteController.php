<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use App\Models\Estudiante;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    /**
     * Obtener todos los reportes
     */
    public function index(Request $request)
    {
        $query = Reporte::with(['estudiante', 'area']);

        // Filtros
        if ($request->has('fecha_inicio')) {
            $query->where('fecha_registro', '>=', $request->fecha_inicio);
        }
        if ($request->has('fecha_fin')) {
            $query->where('fecha_registro', '<=', $request->fecha_fin);
        }
        if ($request->has('estado_pago')) {
            $query->where('estado_pago', $request->estado_pago);
        }
        if ($request->has('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        $reportes = $query->orderBy('fecha_registro', 'desc')->paginate(10);
        return response()->json($reportes);
    }

    /**
     * Obtener estadísticas generales
     */
    public function estadisticas()
    {
        $estadisticas = [
            'total_inscritos' => Estudiante::count(),
            'total_pagados' => Reporte::where('estado_pago', 'pagado')->count(),
            'total_pendientes' => Reporte::where('estado_pago', 'pendiente')->count(),
            'total_rechazados' => Reporte::where('estado_pago', 'rechazado')->count(),
            'monto_total' => Reporte::where('estado_pago', 'pagado')->sum('monto_pagado'),
            'areas_populares' => Area::withCount('reportes')
                ->orderBy('reportes_count', 'desc')
                ->take(5)
                ->get()
        ];

        return response()->json($estadisticas);
    }

    /**
     * Obtener reporte por ID
     */
    public function show($id)
    {
        $reporte = Reporte::with(['estudiante', 'area'])->findOrFail($id);
        return response()->json($reporte);
    }

    /**
     * Crear nuevo reporte
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'area_id' => 'required|exists:areas,id',
            'fecha_registro' => 'required|date',
            'fecha_inscripcion' => 'required|date',
            'fecha_pago' => 'nullable|date',
            'estado_pago' => 'required|in:pendiente,pagado,rechazado',
            'monto_pagado' => 'nullable|numeric',
            'observaciones' => 'nullable|string'
        ]);

        $reporte = Reporte::create($validated);
        return response()->json($reporte, 201);
    }

    /**
     * Actualizar reporte existente
     */
    public function update(Request $request, $id)
    {
        $reporte = Reporte::findOrFail($id);

        $validated = $request->validate([
            'estudiante_id' => 'exists:estudiantes,id',
            'area_id' => 'exists:areas,id',
            'fecha_registro' => 'date',
            'fecha_inscripcion' => 'date',
            'fecha_pago' => 'nullable|date',
            'estado_pago' => 'in:pendiente,pagado,rechazado',
            'monto_pagado' => 'nullable|numeric',
            'observaciones' => 'nullable|string'
        ]);

        $reporte->update($validated);
        return response()->json($reporte);
    }

    /**
     * Eliminar reporte
     */
    public function destroy($id)
    {
        $reporte = Reporte::findOrFail($id);
        $reporte->delete();
        return response()->json(null, 204);
    }

    /**
     * Obtener reporte por rango de fechas
     */
    public function reportePorFechas(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio'
        ]);

        $reportes = Reporte::with(['estudiante', 'area'])
            ->whereBetween('fecha_registro', [$request->fecha_inicio, $request->fecha_fin])
            ->get();

        return response()->json($reportes);
    }
} 