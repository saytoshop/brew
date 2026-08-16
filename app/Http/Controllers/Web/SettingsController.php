<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = DB::table('user_settings')
            ->where('user_id', auth()->id())
            ->first();

        if (!$settings) {
            // Создаем настройки по умолчанию, если их нет
            DB::table('user_settings')->insert([
                'user_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $settings = DB::table('user_settings')->where('user_id', auth()->id())->first();
        }

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'water_brewing_cost' => 'required|numeric|min:0',
            'water_cleaning_cost' => 'required|numeric|min:0',
            'electricity_cost' => 'required|numeric|min:0',
            'co2_cost' => 'required|numeric|min:0',
            'hourly_rate' => 'required|numeric|min:0',
            'fuel_consumption' => 'required|numeric|min:0',
            'fuel_cost' => 'required|numeric|min:0',
            'include_fuel_in_costs' => 'boolean',
            'include_depreciation_in_costs' => 'boolean',
            'equipment_volume' => 'required|numeric|min:0',
            'max_power' => 'required|numeric|min:0',
            'water_cost_per_cubic_meter' => 'required|numeric|min:0',
            'gas_cost_per_cubic_meter' => 'required|numeric|min:0',
            'osmosis_water_cost' => 'required|numeric|min:0',
        ]);

        $validated['updated_at'] = now();
        $validated['include_fuel_in_costs'] = $request->has('include_fuel_in_costs');
        $validated['include_depreciation_in_costs'] = $request->has('include_depreciation_in_costs');

        DB::table('user_settings')
            ->where('user_id', auth()->id())
            ->update($validated);

        return redirect()->back()->with('success', 'Настройки успешно сохранены.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:sqlite,db,sqlite3',
        ]);

        $file = $request->file('import_file');
        $tempPath = $file->getRealPath();

        try {
            // Подключаемся к временному файлу SQLite
            $sourceDb = new \SQLite3($tempPath);
            
            if (!$sourceDb) {
                return response()->json(['success' => false, 'message' => 'Не удалось подключиться к базе данных'], 500);
            }

            DB::beginTransaction();

            $imported = [];

            // Импорт категорий
            $imported['categories'] = $this->importCategoriesFromSqlite($sourceDb);

            // Создание справочника единиц измерения из supplies
            $imported['units'] = $this->importUnitsFromSqlite($sourceDb);

            // Импорт ингредиентов (из supplies)
            $imported['ingredients'] = $this->importIngredientsFromSqlite($sourceDb);

            // Импорт оборудования
            $imported['equipment'] = $this->importEquipmentFromSqlite($sourceDb);

            // Импорт партий на складах (из inventory)
            $imported['stock_batches'] = $this->importStockBatchesFromSqlite($sourceDb);

            // Импорт рецептов
            $imported['recipes'] = $this->importRecipesFromSqlite($sourceDb);

            // Импорт ингредиентов рецептов
            $imported['recipe_ingredients'] = $this->importRecipeIngredientsFromSqlite($sourceDb);

            // Импорт варок (из brewing_sessions)
            $imported['brews'] = $this->importBrewsFromSqlite($sourceDb);

            // Импорт использованных ингредиентов в варках (из inventory_transactions)
            $imported['brew_ingredients'] = $this->importBrewIngredientsFromSqlite($sourceDb);

            // Импорт настроек пользователя
            $imported['settings'] = $this->importUserSettingsFromSqlite($sourceDb);

            DB::commit();
            $sourceDb->close();
            
            $totalCount = array_sum($imported);
            return response()->json(['success' => true, 'imported' => $totalCount, 'details' => $imported]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ошибка импорта: ' . $e->getMessage()], 500);
        }
    }

    private function importCategoriesFromSqlite(\SQLite3 $sourceDb): int
    {
        $result = $sourceDb->query('SELECT * FROM categories');
        $count = 0;
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            DB::table('categories')->updateOrInsert(
                ['name' => $row['name']],
                [
                    'created_at' => $row['created_at'] ? now()->parse($row['created_at']) : now(),
                    'updated_at' => $row['updated_at'] ? now()->parse($row['updated_at']) : now(),
                ]
            );
            $count++;
        }
        
        return $count;
    }

    private function importUnitsFromSqlite(\SQLite3 $sourceDb): int
    {
        $result = $sourceDb->query('SELECT DISTINCT unit FROM supplies WHERE unit IS NOT NULL AND unit != ""');
        $count = 0;
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $unitName = trim($row['unit']);
            if (!empty($unitName)) {
                DB::table('units')->updateOrInsert(
                    ['name' => $unitName],
                    ['created_at' => now(), 'updated_at' => now()]
                );
                $count++;
            }
        }
        
        // Добавляем стандартные единицы, если их нет
        $defaultUnits = ['кг', 'г', 'л', 'мл', 'шт'];
        foreach ($defaultUnits as $unit) {
            $exists = DB::table('units')->where('name', $unit)->exists();
            if (!$exists) {
                DB::table('units')->insert(['name' => $unit, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        
        return $count;
    }

    private function determineUnitId(string $name, string $originalUnit): ?int
    {
        $nameLower = mb_strtolower($name);
        
        // Определяем единицу измерения по типу ингредиента
        if (str_contains($nameLower, 'дрожж') || str_contains($nameLower, 'yeast')) {
            $unitName = 'г';
        } elseif (str_contains($nameLower, 'хмель') || str_contains($nameLower, 'hop')) {
            $unitName = 'г';
        } elseif (str_contains($nameLower, 'солод') || str_contains($nameLower, 'malt')) {
            $unitName = 'кг';
        } elseif (str_contains($nameLower, 'вода') || str_contains($nameLower, 'water')) {
            $unitName = 'л';
        } elseif (str_contains($originalUnit, 'кг')) {
            $unitName = 'кг';
        } elseif (str_contains($originalUnit, 'г')) {
            $unitName = 'г';
        } elseif (str_contains($originalUnit, 'л')) {
            $unitName = 'л';
        } elseif (str_contains($originalUnit, 'мл')) {
            $unitName = 'мл';
        } else {
            $unitName = 'кг'; // По умолчанию
        }
        
        $unit = DB::table('units')->where('name', $unitName)->first();
        return $unit?->id;
    }

    private function importIngredientsFromSqlite(\SQLite3 $sourceDb): int
    {
        $result = $sourceDb->query('SELECT s.*, c.name as category_name FROM supplies s JOIN categories c ON s.category_id = c.id');
        $count = 0;
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $category = DB::table('categories')->where('name', $row['category_name'])->first();
            
            if (!$category) {
                continue;
            }

            $unitId = $this->determineUnitId($row['name'], $row['unit'] ?? '');
            
            if (!$unitId) {
                continue;
            }
            
            DB::table('ingredients')->updateOrInsert(
                [
                    'name' => $row['name'],
                    'category_id' => $category->id,
                ],
                [
                    'unit_id' => $unitId,
                    'created_at' => $row['created_at'] ? now()->parse($row['created_at']) : now(),
                    'updated_at' => $row['updated_at'] ? now()->parse($row['updated_at']) : now(),
                ]
            );
            $count++;
        }
        
        return $count;
    }

    private function importEquipmentFromSqlite(\SQLite3 $sourceDb): int
    {
        $result = $sourceDb->query('SELECT * FROM equipment');
        $count = 0;
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            DB::table('equipment')->updateOrInsert(
                ['name' => $row['name']],
                [
                    'price' => $row['cost'] ?? 0,
                    'purchase_date' => $row['created_at'] ? now()->parse($row['created_at'])->format('Y-m-d') : null,
                    'created_at' => $row['created_at'] ? now()->parse($row['created_at']) : now(),
                    'updated_at' => $row['updated_at'] ? now()->parse($row['updated_at']) : now(),
                ]
            );
            $count++;
        }
        
        return $count;
    }

    private function importStockBatchesFromSqlite(\SQLite3 $sourceDb): int
    {
        $result = $sourceDb->query('
            SELECT i.*, s.name as supply_name, s.unit as supply_unit 
            FROM inventory i 
            JOIN supplies s ON i.supply_id = s.id
        ');
        $count = 0;
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $ingredient = DB::table('ingredients')
                ->where('name', $row['supply_name'])
                ->first();
            
            if (!$ingredient) {
                continue;
            }
            
            DB::table('stock_batches')->updateOrInsert(
                [
                    'ingredient_id' => $ingredient->id,
                    'purchase_date' => $row['created_at'] ? now()->parse($row['created_at'])->format('Y-m-d') : now()->format('Y-m-d')
                ],
                [
                    'quantity' => $row['quantity'] ?? 0,
                    'price_per_unit' => $row['cost'] ?? 0,
                    'created_at' => $row['created_at'] ? now()->parse($row['created_at']) : now(),
                    'updated_at' => $row['updated_at'] ? now()->parse($row['updated_at']) : now(),
                ]
            );
            $count++;
        }
        
        return $count;
    }

    private function importRecipesFromSqlite(\SQLite3 $sourceDb): int
    {
        $result = $sourceDb->query('SELECT * FROM recipes');
        $count = 0;
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            DB::table('recipes')->updateOrInsert(
                ['name' => $row['name']],
                [
                    'description' => $row['description'] ?? null,
                    'created_at' => $row['created_at'] ? now()->parse($row['created_at']) : now(),
                    'updated_at' => $row['updated_at'] ? now()->parse($row['updated_at']) : now(),
                ]
            );
            $count++;
        }
        
        return $count;
    }

    private function importRecipeIngredientsFromSqlite(\SQLite3 $sourceDb): int
    {
        $result = $sourceDb->query('
            SELECT ri.*, r.name as recipe_name, s.name as supply_name 
            FROM recipe_ingredients ri 
            JOIN recipes r ON ri.recipe_id = r.id 
            JOIN supplies s ON ri.supply_id = s.id
        ');
        $count = 0;
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $recipe = DB::table('recipes')->where('name', $row['recipe_name'])->first();
            $ingredient = DB::table('ingredients')->where('name', $row['supply_name'])->first();
            
            if (!$recipe || !$ingredient) {
                continue;
            }
            
            DB::table('recipe_ingredients')->updateOrInsert(
                [
                    'recipe_id' => $recipe->id,
                    'ingredient_id' => $ingredient->id,
                ],
                [
                    'quantity' => $row['quantity'] ?? 0,
                    'add_time_minutes' => $row['add_time'] ?? 0,
                    'created_at' => $row['created_at'] ? now()->parse($row['created_at']) : now(),
                    'updated_at' => $row['updated_at'] ? now()->parse($row['updated_at']) : now(),
                ]
            );
            $count++;
        }
        
        return $count;
    }

    private function importBrewsFromSqlite(\SQLite3 $sourceDb): int
    {
        $result = $sourceDb->query('SELECT bs.*, r.name as recipe_name FROM brewing_sessions bs LEFT JOIN recipes r ON bs.recipe_id = r.id');
        $count = 0;
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $recipeId = null;
            if ($row['recipe_name']) {
                $recipe = DB::table('recipes')->where('name', $row['recipe_name'])->first();
                $recipeId = $recipe?->id;
            }
            
            $costPerLiter = null;
            if (($row['total_cost'] ?? 0) > 0 && ($row['batch_volume'] ?? 0) > 0) {
                $costPerLiter = round($row['total_cost'] / $row['batch_volume'], 2);
            }
            
            // Используем уникальное сочетание для предотвращения дублей
            $brewKey = [
                'recipe_id' => $recipeId,
                'created_at' => $row['created_at'] ? now()->parse($row['created_at'])->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
            ];
            
            DB::table('brews')->updateOrInsert($brewKey, [
                'volume_actual' => $row['batch_volume'] ?? null,
                'cost_per_liter' => $costPerLiter,
                'is_modified' => false,
                'updated_at' => $row['updated_at'] ? now()->parse($row['updated_at']) : now(),
            ]);
            $count++;
        }
        
        return $count;
    }

    private function importBrewIngredientsFromSqlite(\SQLite3 $sourceDb): int
    {
        $result = $sourceDb->query("
            SELECT it.*, s.name as supply_name, bs.created_at as brew_created_at
            FROM inventory_transactions it 
            JOIN supplies s ON it.supply_id = s.id
            LEFT JOIN brewing_sessions bs ON it.related_brewing_session_id = bs.id
            WHERE it.type = 'deduct_auto' AND it.related_brewing_session_id IS NOT NULL
        ");
        $count = 0;
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $ingredient = DB::table('ingredients')->where('name', $row['supply_name'])->first();
            
            if (!$ingredient) {
                continue;
            }
            
            // Находим варку по времени создания (с допустимым отклонением)
            $brewCreatedAt = $row['brew_created_at'] ? now()->parse($row['brew_created_at'])->format('Y-m-d H:i:s') : null;
            
            $brew = null;
            if ($brewCreatedAt) {
                $brew = DB::table('brews')
                    ->where('created_at', $brewCreatedAt)
                    ->first();
            }
            
            // Если не нашли точное совпадение, ищем ближайшую варку без recipe_id или с тем же временем
            if (!$brew) {
                $brew = DB::table('brews')
                    ->orderBy('created_at', 'desc')
                    ->first();
            }
            
            if (!$brew) {
                continue;
            }
            
            // Находим первую доступную партию ингредиента
            $batch = DB::table('stock_batches')
                ->where('ingredient_id', $ingredient->id)
                ->first();
            
            if (!$batch) {
                continue;
            }
            
            // Проверяем, нет ли уже такой записи
            $exists = DB::table('brew_ingredients')
                ->where('brew_id', $brew->id)
                ->where('ingredient_id', $ingredient->id)
                ->where('batch_id', $batch->id)
                ->where('quantity_used', $row['quantity'] ?? 0)
                ->exists();
            
            if ($exists) {
                continue;
            }
            
            DB::table('brew_ingredients')->insert([
                'brew_id' => $brew->id,
                'ingredient_id' => $ingredient->id,
                'batch_id' => $batch->id,
                'quantity_used' => $row['quantity'] ?? 0,
                'price_per_unit' => $batch->price_per_unit ?? 0,
                'created_at' => $row['created_at'] ? now()->parse($row['created_at']) : now(),
                'updated_at' => $row['updated_at'] ? now()->parse($row['updated_at']) : now(),
            ]);
            $count++;
        }
        
        return $count;
    }

    private function importUserSettingsFromSqlite(\SQLite3 $sourceDb): int
    {
        $result = $sourceDb->query('SELECT * FROM user_settings LIMIT 1');
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$row) {
            return 0;
        }
        
        $count = 0;
        $settingsMap = [
            'water_brewing_cost' => 'water_brewing_cost',
            'water_cleaning_cost' => 'water_cleaning_cost',
            'electricity_cost' => 'electricity_cost',
            'co2_cost' => 'co2_cost',
            'hourly_rate' => 'hourly_rate',
            'fuel_consumption' => 'fuel_consumption',
            'fuel_cost' => 'fuel_cost',
            'include_fuel_in_costs' => 'include_fuel_in_costs',
            'include_depreciation_in_costs' => 'include_depreciation_in_costs',
            'equipment_volume' => 'equipment_volume',
            'max_power' => 'max_power',
            'water_cost_per_cubic_meter' => 'water_cost_per_cubic_meter',
            'gas_cost_per_cubic_meter' => 'gas_cost_per_cubic_meter',
            'osmosis_water_cost' => 'osmosis_water_cost',
        ];
        
        foreach ($settingsMap as $sourceKey => $targetKey) {
            if (isset($row[$sourceKey])) {
                DB::table('settings')->updateOrInsert(
                    ['key' => $targetKey],
                    ['value' => (string) $row[$sourceKey]]
                );
                $count++;
            }
        }
        
        return $count;
    }
}
