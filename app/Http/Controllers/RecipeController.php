<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    public function index()
    {
        $recipes = Recipe::withCount('brews')->with(['recipeIngredients.ingredient.category', 'recipeIngredients.ingredient.unit'])
            ->orderBy('created_at', 'desc')
            ->get();
        return view('recipes.index', compact('recipes'));
    }

    public function create()
    {
        return view('recipes.create');
    }

    public function show(Recipe $recipe)
    {
        $recipe->load(['recipeIngredients.ingredient.category', 'recipeIngredients.ingredient.unit']);
        $groupedIngredients = $recipe->recipeIngredients && $recipe->recipeIngredients->count() > 0 
            ? $recipe->recipeIngredients->groupBy('ingredient.category.name')
            : collect();
        return view('recipes.show', compact('recipe', 'groupedIngredients'));
    }

    public function edit(Recipe $recipe)
    {
        $recipe->load(['recipeIngredients.ingredient.category', 'recipeIngredients.ingredient.unit']);
        return view('recipes.edit', compact('recipe'));
    }

    public function data(): JsonResponse
    {
        $recipes = Recipe::withCount('brews')->with('recipeIngredients.ingredient.category')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($recipe) {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'description' => $recipe->description,
                    'brews_count' => $recipe->brews_count,
                    'ingredients_count' => $recipe->recipeIngredients ? $recipe->recipeIngredients->count() : 0,
                ];
            });
        return response()->json($recipes);
    }

    public function showData(Recipe $recipe): JsonResponse
    {
        $recipe->load(['recipeIngredients.ingredient.category', 'recipeIngredients.ingredient.unit']);

        // Расчёт себестоимости по FIFO (самая старая партия)
        $ingredients = $recipe->recipeIngredients->map(function ($ri) {
            $oldestBatch = DB::table('stock_batches')
                ->where('ingredient_id', $ri->ingredient_id)
                ->where('quantity', '>', 0)
                ->orderBy('purchase_date', 'asc')
                ->first();

            $hasStock = $oldestBatch && $oldestBatch->quantity >= $ri->quantity;
            $price = $oldestBatch ? $oldestBatch->price_per_unit : 0;

            return [
                'id' => $ri->ingredient->id,
                'name' => $ri->ingredient->name,
                'category' => $ri->ingredient->category->name,
                'unit' => $ri->ingredient->unit->name,
                'quantity' => (float)$ri->quantity,
                'add_time_minutes' => (int)$ri->add_time_minutes,
                'price_per_unit' => (float)$price,
                'cost' => (float)($ri->quantity * $price),
                'has_stock' => $hasStock,
            ];
        });

        $totalCost = $ingredients->sum('cost');

        return response()->json([
            'id' => $recipe->id,
            'name' => $recipe->name,
            'description' => $recipe->description,
            'ingredients' => $ingredients,
            'total_cost' => (float)$totalCost,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ingredients' => 'required|array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
            'ingredients.*.add_time_minutes' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $recipe = Recipe::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? '',
            ]);

            foreach ($validated['ingredients'] as $ing) {
                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'ingredient_id' => $ing['ingredient_id'],
                    'quantity' => $ing['quantity'],
                    'add_time_minutes' => $ing['add_time_minutes'],
                ]);
            }

            DB::commit();
            return response()->json($recipe->load('recipeIngredients'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Recipe $recipe): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ingredients' => 'required|array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
            'ingredients.*.add_time_minutes' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $recipe->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? '',
            ]);

            $recipe->recipeIngredients()->delete();
            foreach ($validated['ingredients'] as $ing) {
                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'ingredient_id' => $ing['ingredient_id'],
                    'quantity' => $ing['quantity'],
                    'add_time_minutes' => $ing['add_time_minutes'],
                ]);
            }

            DB::commit();
            return response()->json($recipe->load('recipeIngredients'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Recipe $recipe): JsonResponse
    {
        $recipe->delete();
        return response()->json(null, 204);
    }
}
