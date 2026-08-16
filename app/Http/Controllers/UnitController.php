<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UnitController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Unit::orderBy('name')->get());
    }

    public function show(Unit $unit): JsonResponse
    {
        return response()->json($unit);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:units,name',
        ]);

        $unit = Unit::create($validated);
        return response()->json($unit, 201);
    }

    public function update(Request $request, Unit $unit): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:units,name,' . $unit->id,
        ]);

        $unit->update($validated);
        return response()->json($unit);
    }

    public function destroy(Unit $unit): JsonResponse
    {
        $unit->delete();
        return response()->json(null, 204);
    }
}
