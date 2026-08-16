<?php

namespace App\Http\Controllers;

use App\Models\StockBatch;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index()
    {
        return view('stock.index');
    }

    public function data(): JsonResponse
    {
        $stock = DB::table('ingredients')
            ->join('categories', 'ingredients.category_id', '=', 'categories.id')
            ->join('units', 'ingredients.unit_id', '=', 'units.id')
            ->leftJoin('stock_batches', 'ingredients.id', '=', 'stock_batches.ingredient_id')
            ->select(
                'ingredients.id as ingredient_id',
                'ingredients.name as ingredient_name',
                'categories.name as category',
                'units.name as unit',
                DB::raw('COALESCE(SUM(stock_batches.quantity), 0) as total_quantity'),
                DB::raw('MIN(stock_batches.purchase_date) as oldest_batch_date'),
                DB::raw('AVG(stock_batches.price_per_unit) as avg_price')
            )
            ->groupBy('ingredients.id', 'categories.name', 'units.name')
            ->havingRaw('COALESCE(SUM(stock_batches.quantity), 0) > 0')
            ->get()
            ->map(function ($item) {
                $oldestDate = $item->oldest_batch_date ? new \DateTime($item->oldest_batch_date) : null;
                $ageMonths = $oldestDate ? floor($oldestDate->diff(new \DateTime())->days / 30) : 0;
                return [
                    'ingredient_id' => $item->ingredient_id,
                    'ingredient_name' => $item->ingredient_name,
                    'category' => $item->category,
                    'unit' => $item->unit,
                    'total_quantity' => (float) $item->total_quantity,
                    'old_batch_age_months' => (int) $ageMonths,
                    'avg_price' => (float) ($item->avg_price ?? 0),
                ];
            });

        return response()->json($stock);
    }

    public function receipt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity' => 'required|numeric|min:0.01',
            'price_per_unit' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
        ]);

        StockBatch::create($validated);
        return response()->json(['message' => 'Партия создана'], 201);
    }

    public function forecast(): JsonResponse
    {
        // Берём последние 10 варок
        $recentBrews = DB::table('brews')->orderBy('created_at', 'desc')->limit(10)->get(['id']);
        if ($recentBrews->isEmpty()) {
            return response()->json([]);
        }

        $brewIds = $recentBrews->pluck('id');
        
        // Средний расход по ингредиентам
        $avgUsage = DB::table('brew_ingredients')
            ->whereIn('brew_id', $brewIds)
            ->select('ingredient_id', DB::raw('AVG(quantity_used) as avg_qty'))
            ->groupBy('ingredient_id')
            ->pluck('avg_qty', 'ingredient_id');

        // Текущие остатки
        $stockData = DB::table('ingredients')
            ->leftJoin('stock_batches', 'ingredients.id', '=', 'stock_batches.ingredient_id')
            ->select('ingredients.id', DB::raw('COALESCE(SUM(stock_batches.quantity), 0) as total'))
            ->groupBy('ingredients.id')
            ->pluck('total', 'id');

        $emojiMap = ['Солод' => '🌾', 'Хмель' => '🌿', 'Дрожжи' => '🧫', 'Добавки' => '➕', 'Моющие средства' => '🧼'];

        $forecast = [];
        foreach ($avgUsage as $ingId => $avgQty) {
            $totalStock = $stockData->get($ingId, 0);
            if ($avgQty <= 0 || $totalStock <= 0) continue;
            
            $varoks = floor($totalStock / $avgQty);
            $ingredient = Ingredient::with('category')->find($ingId);
            if (!$ingredient) continue;

            $emoji = $emojiMap[$ingredient->category->name] ?? '📦';
            $forecast[] = [
                'ingredient_name' => $ingredient->name,
                'name' => $ingredient->name,
                'varoks' => (int) $varoks,
                'emoji' => $emoji,
            ];
        }

        return response()->json($forecast);
    }
}
