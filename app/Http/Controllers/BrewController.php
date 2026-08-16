<?php

namespace App\Http\Controllers;

use App\Models\Brew;
use App\Models\BrewIngredient;
use App\Models\BrewComment;
use App\Models\Recipe;
use App\Models\StockBatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BrewController extends Controller
{
    public function index()
    {
        $brews = Brew::with('recipe')->orderBy('created_at', 'desc')->get();
        return view('brews.index', compact('brews'));
    }

    public function show(Brew $brew)
    {
        $brew->load(['recipe', 'ingredients.ingredient.category', 'comments']);
        return view('brews.show', compact('brew'));
    }

    public function data(): JsonResponse
    {
        $brews = Brew::with('recipe')->orderBy('created_at', 'desc')->get()->map(function ($brew) {
            return [
                'id' => $brew->id,
                'recipe_id' => $brew->recipe_id,
                'recipe_name' => $brew->recipe ? $brew->recipe->name : null,
                'volume_actual' => (float) $brew->volume_actual,
                'cost_per_liter' => (float) $brew->cost_per_liter,
                'is_modified' => (bool) $brew->is_modified,
                'modified_diff' => $brew->modified_diff,
                'created_at' => $brew->created_at,
            ];
        });
        return response()->json($brews);
    }

    public function showData(Brew $brew): JsonResponse
    {
        $brew->load(['recipe', 'ingredients.ingredient.category', 'comments']);
        
        $ingredients = $brew->ingredients->map(function ($bi) {
            return [
                'id' => $bi->id,
                'ingredient_name' => $bi->ingredient->name,
                'category' => $bi->ingredient->category->name,
                'quantity_used' => (float) $bi->quantity_used,
                'price_per_unit' => (float) $bi->price_per_unit,
            ];
        });

        $comments = $brew->comments->map(function ($c) {
            return [
                'id' => $c->id,
                'content' => $c->content,
                'created_at' => $c->created_at,
            ];
        });

        return response()->json([
            'id' => $brew->id,
            'recipe_name' => $brew->recipe ? $brew->recipe->name : null,
            'volume_actual' => (float) $brew->volume_actual,
            'cost_per_liter' => (float) $brew->cost_per_liter,
            'is_modified' => (bool) $brew->is_modified,
            'modified_diff' => $brew->modified_diff,
            'created_at' => $brew->created_at,
            'ingredients' => $ingredients,
            'comments' => $comments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipe_id' => 'nullable|exists:recipes,id',
            'is_modified' => 'boolean',
            'modified_diff' => 'nullable|string',
            'ingredients' => 'required_if:is_modified,true|array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
            'ingredients.*.add_time' => 'integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Парсим modified_diff если это строка JSON
            $modifiedDiff = null;
            if (!empty($validated['modified_diff']) && is_string($validated['modified_diff'])) {
                $modifiedDiff = json_decode($validated['modified_diff'], true);
            } elseif (!empty($validated['modified_diff']) && is_array($validated['modified_diff'])) {
                $modifiedDiff = $validated['modified_diff'];
            }

            // Создаём запись о варке
            $brew = Brew::create([
                'recipe_id' => $validated['recipe_id'] ?? null,
                'is_modified' => !empty($validated['is_modified']),
                'modified_diff' => $modifiedDiff,
            ]);

            // Определяем состав для списания
            if (!empty($validated['is_modified']) && !empty($validated['ingredients'])) {
                $ingredientsToUse = $validated['ingredients'];
            } else {
                // Берём из рецепта
                $recipe = Recipe::with('recipeIngredients')->find($validated['recipe_id']);
                if (!$recipe) throw new \Exception('Рецепт не найден');
                $ingredientsToUse = $recipe->recipeIngredients->map(function ($ri) {
                    return [
                        'ingredient_id' => $ri->ingredient_id,
                        'quantity' => $ri->quantity,
                        'add_time_minutes' => $ri->add_time_minutes,
                    ];
                })->toArray();
            }

            // FIFO списание
            foreach ($ingredientsToUse as $ing) {
                $needed = $ing['quantity'];
                $batches = StockBatch::where('ingredient_id', $ing['ingredient_id'])
                    ->where('quantity', '>', 0)
                    ->orderBy('purchase_date', 'asc')
                    ->get();

                $totalAvailable = $batches->sum('quantity');
                if ($totalAvailable < $needed) {
                    throw new \Exception("Недостаточно ингредиента ID {$ing['ingredient_id']}. Доступно: $totalAvailable, нужно: $needed");
                }

                foreach ($batches as $batch) {
                    if ($needed <= 0) break;
                    
                    $deduct = min($needed, $batch->quantity);
                    BrewIngredient::create([
                        'brew_id' => $brew->id,
                        'ingredient_id' => $ing['ingredient_id'],
                        'batch_id' => $batch->id,
                        'quantity_used' => $deduct,
                        'price_per_unit' => $batch->price_per_unit,
                    ]);
                    
                    $batch->decrement('quantity', $deduct);
                    $needed -= $deduct;
                }
            }

            DB::commit();
            
            // Если запрос от формы - редирект на страницу варки
            if (!$request->expectsJson()) {
                return redirect()->route('brews.show', $brew->id)->with('success', 'Варка создана');
            }
            
            return response()->json($brew, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            if (!$request->expectsJson()) {
                return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
            }
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function complete(Request $request, Brew $brew): JsonResponse
    {
        $validated = $request->validate([
            'volume_actual' => 'required|numeric|min:1',
        ]);

        // Расчёт себестоимости
        $rawCost = DB::table('brew_ingredients')
            ->where('brew_id', $brew->id)
            ->select(DB::raw('SUM(quantity_used * price_per_unit) as total'))
            ->value('total') ?? 0;

        // Утилиты из настроек
        $settings = [];
        foreach (['energy_cost_per_kwh', 'heater_power_kw', 'water_drinking_cost', 'water_technical_cost', 'chiller_flow_l_per_min', 'boil_time_minutes', 'water_per_liter_ratio'] as $key) {
            $settings[$key] = (float) DB::table('settings')->where('key', $key)->value('value') ?? 0;
        }

        // energy_cost = energy_cost_per_kwh × heater_power_kw × (boil_time_minutes / 60) × 3
        $energyCost = $settings['energy_cost_per_kwh'] * $settings['heater_power_kw'] * ($settings['boil_time_minutes'] / 60) * 3;
        
        // water_drinking_total = volume_actual × water_per_liter_ratio × water_drinking_cost
        $waterDrinkingTotal = $validated['volume_actual'] * $settings['water_per_liter_ratio'] * $settings['water_drinking_cost'];
        
        // water_technical_total = chiller_flow_l_per_min × (boil_time_minutes / 60) × water_technical_cost
        $waterTechnicalTotal = $settings['chiller_flow_l_per_min'] * ($settings['boil_time_minutes'] / 60) * $settings['water_technical_cost'];

        $utilitiesCost = $energyCost + $waterDrinkingTotal + $waterTechnicalTotal;
        $totalCost = $rawCost + $utilitiesCost;
        $costPerLiter = $totalCost / $validated['volume_actual'];

        $brew->update([
            'volume_actual' => $validated['volume_actual'],
            'cost_per_liter' => $costPerLiter,
        ]);

        return response()->json($brew);
    }

    public function comments($brewId): JsonResponse
    {
        $comments = BrewComment::where('brew_id', $brewId)->orderBy('created_at', 'desc')->get();
        return response()->json($comments);
    }

    public function addComment(Request $request, $brewId): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $comment = BrewComment::create([
            'brew_id' => $brewId,
            'content' => $validated['content'],
        ]);

        return response()->json($comment, 201);
    }

    public function updateComment(Request $request, BrewComment $comment): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $comment->update($validated);
        return response()->json($comment);
    }

    public function deleteComment(BrewComment $comment): JsonResponse
    {
        $comment->delete();
        return response()->json(null, 204);
    }
}
