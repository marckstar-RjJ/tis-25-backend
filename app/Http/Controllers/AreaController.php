<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Area::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:areas',
            'descripcion' => 'required|string',
            'estado' => 'required|boolean',
        ]);

        $area = Area::create($validated);

        return response()->json($area, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $area = Area::findOrFail($id);
        return response()->json($area);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $area = Area::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'string|max:255|unique:areas,nombre,' . $id,
            'descripcion' => 'string',
            'estado' => 'boolean',
        ]);

        $area->update($validated);

        return response()->json($area);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $area = Area::findOrFail($id);
        $area->delete();

        return response()->json(null, 204);
    }

    public function getActiveAreas()
    {
        return response()->json(Area::where('estado', true)->get());
    }
}
