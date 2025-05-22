<?php

namespace App\Http\Controllers;

use App\Models\College;
use Illuminate\Http\Request;

class CollegeController extends Controller
{
    public function index()
    {
        return response()->json(College::all());
    }

    public function show($id)
    {
        $college = College::findOrFail($id);
        return response()->json($college);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:colegios',
            'direccion' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
        ]);

        $college = College::create($validated);

        return response()->json($college, 201);
    }

    public function update(Request $request, $id)
    {
        $college = College::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'string|max:255|unique:colegios,nombre,' . $id,
            'direccion' => 'string|max:255',
            'telefono' => 'string|max:20',
        ]);

        $college->update($validated);

        return response()->json($college);
    }

    public function destroy($id)
    {
        $college = College::findOrFail($id);
        $college->delete();

        return response()->json(null, 204);
    }
} 