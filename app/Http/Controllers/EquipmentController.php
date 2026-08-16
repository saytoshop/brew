<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EquipmentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Equipment::orderBy('created_at', 'desc')->get());
    }

    public function show(Equipment $equipment): JsonResponse
    {
        return response()->json($equipment);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
        ]);

        $equipment = Equipment::create($validated);
        return response()->json($equipment, 201);
    }

    public function update(Request $request, Equipment $equipment): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
        ]);

        $equipment->update($validated);
        return response()->json($equipment);
    }

    public function destroy(Equipment $equipment): JsonResponse
    {
        $equipment->delete();
        return response()->json(null, 204);
    }
}
