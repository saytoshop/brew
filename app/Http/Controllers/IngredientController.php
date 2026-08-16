<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Category;
use App\Models\Unit;
use App\Models\StockBatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class IngredientController extends Controller
{
    public function index(): JsonResponse
    {
        $ingredients = Ingredient::with(['category', 'unit'])->get()->map(function ($ing) {
            return [
                'id' => $ing->id,
                'name' => $ing->name,
                'category_id' => $ing->category_id,
                'category_name' => $ing->category->name,
                'unit_id' => $ing->unit_id,
                'unit_name' => $ing->unit->name,
            ];
        });
        return response()->json($ingredients);
    }

    public function show(Ingredient $ingredient): JsonResponse
    {
        return response()->json($ingredient->load(['category', 'unit']));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
        ]);

        $ingredient = Ingredient::create($validated);
        return response()->json($ingredient->load(['category', 'unit']), 201);
    }

    public function update(Request $request, Ingredient $ingredient): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
        ]);

        $ingredient->update($validated);
        return response()->json($ingredient->load(['category', 'unit']));
    }

    public function destroy(Ingredient $ingredient): JsonResponse
    {
        $ingredient->delete();
        return response()->json(null, 204);
    }
}
