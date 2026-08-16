<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SQLite3;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Setting::all(['id', 'key', 'value']));
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            '*' => 'required|array',
            '*.key' => 'required|string',
            '*.value' => 'required|string',
        ]);

        foreach ($validated as $item) {
            Setting::updateOrCreate(['key' => $item['key']], ['value' => $item['value']]);
        }

        return response()->json(['message' => 'Настройки сохранены']);
    }

    public function exportDb(): \Illuminate\Http\Response
    {
        $source = database_path('database.sqlite');
        $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sqlite';
        $dest = storage_path('app/exports/' . $filename);

        if (!file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        copy($source, $dest);

        return response()->download($dest)->deleteFileAfterSend(false);
    }

    public function importDb(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:sqlite,db',
        ]);

        $uploadedFile = $request->file('file');
        $tempPath = $uploadedFile->storeAs('imports', 'import_' . time() . '.sqlite');
        $fullPath = storage_path('app/' . $tempPath);

        try {
            DB::beginTransaction();

            $this->performImport($fullPath);

            DB::commit();

            // Удаляем временный файл
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            return response()->json(['message' => 'Импорт выполнен успешно']);
        } catch (\Exception $e) {
            DB::rollBack();

            // Удаляем временный файл при ошибке
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            return response()->json(['error' => 'Ошибка импорта: ' . $e->getMessage()], 422);
        }
    }

    private function performImport(string $sourceFile): void
    {
        if (!file_exists($sourceFile)) {
            throw new \Exception("Файл не найден");
        }

        $sourceDb = new SQLite3($sourceFile);
        
        if (!$sourceDb) {
            throw new \Exception("Не удалось подключиться к базе данных");
        }

        try {
            // Импорт пользователей
            $this->importUsers($sourceDb);

            // Импорт категорий
            $this->ensureDefaultUnit();
            $this->importCategories($sourceDb);

            // Импорт ингредиентов (supplies в источнике)
            $this->importIngredients($sourceDb);

            // Импорт оборудования
            $this->importEquipment($sourceDb);

            // Импорт запасов (inventory в источнике)
            $this->importStockBatches($sourceDb);

            // Импорт рецептов
            $this->importRecipes($sourceDb);

            // Импорт ингредиентов рецептов
            $this->importRecipeIngredients($sourceDb);

            // Импорт варок (brewing_sessions в источнике)
            $this->importBrews($sourceDb);

        } finally {
            $sourceDb->close();
        }
    }

    private function importUsers(SQLite3 $sourceDb): void
    {
        $result = $sourceDb->query('SELECT * FROM users');
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            DB::table('users')->updateOrInsert(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => $row['password'],
                    'remember_token' => $row['remember_token'],
                    'email_verified_at' => $row['email_verified_at'] ? now()->parse($row['email_verified_at']) : null,
                    'created_at' => $row['created_at'] ? now()->parse($row['created_at']) : now(),
                    'updated_at' => $row['updated_at'] ? now()->parse($row['updated_at']) : now(),
                ]
            );
        }
    }

    private function ensureDefaultUnit(): void
    {
        $units = [
            ['name' => 'кг'],
            ['name' => 'г'],
            ['name' => 'л'],
            ['name' => 'мл'],
            ['name' => 'шт'],
        ];
        
        foreach ($units as $unit) {
            DB::table('units')->updateOrInsert(
                ['name' => $unit['name']],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function importCategories(SQLite3 $sourceDb): void
    {
        $result = $sourceDb->query('SELECT * FROM categories');
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            DB::table('categories')->updateOrInsert(
                ['name' => $row['name']],
                [
                    'created_at' => $row['created_at'] ? now()->parse($row['created_at']) : now(),
                    'updated_at' => $row['updated_at'] ? now()->parse($row['updated_at']) : now(),
                ]
            );
        }
    }

    private function importIngredients(SQLite3 $sourceDb): void
    {
        $result = $sourceDb->query('SELECT s.*, c.name as category_name FROM supplies s JOIN categories c ON s.category_id = c.id');
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $category = DB::table('categories')->where('name', $row['category_name'])->first();
            
            if (!$category) {
                continue;
            }

            $unitName = $this->determineUnit($row['name'], $row['unit']);
            $unit = DB::table('units')->where('name', $unitName)->first();
            
            if (!$unit) {
                $unitName = 'шт';
                $unit = DB::table('units')->where('name', $unitName)->first();
            }
            
            DB::table('ingredients')->updateOrInsert(
                [
                    'name' => $row['name'],
                    'category_id' => $category->id,
                ],
                [
                    'unit_id' => $unit->id,
                    'created_at' => $row['created_at'] ? now()->parse($row['created_at']) : now(),
                    'updated_at' => $row['updated_at'] ? now()->parse($row['updated_at']) : now(),
                ]
            );
        }
    }

    private function determineUnit(string $name, string $originalUnit): string
    {
        $nameLower = mb_strtolower($name);
        
        if (str_contains($nameLower, 'дрожж') || str_contains($nameLower, 'yeast')) {
            return 'г';
        }
        
        if (str_contains($nameLower, 'хмель') || str_contains($nameLower, 'hop')) {
            return 'г';
        }
        
        if (str_contains($nameLower, 'солод') || str_contains($nameLower, 'malt')) {
            return 'кг';
        }
        
        if (str_contains($nameLower, 'вода') || str_contains($nameLower, 'water')) {
            return 'л';
        }
        
        if (str_contains($originalUnit, 'кг')) {
            return 'кг';
        }
        
        if (str_contains($originalUnit, 'г')) {
            return 'г';
        }
        
        if (str_contains($originalUnit, 'л')) {
            return 'л';
        }
        
        if (str_contains($originalUnit, 'мл')) {
            return 'мл';
        }
        
        return 'кг';
    }

    private function importEquipment(SQLite3 $sourceDb): void
    {
        $result = $sourceDb->query('SELECT * FROM equipment');
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            DB::table('equipment')->updateOrInsert(
                ['name' => $row['name']],
                [
                    'price' => $row['cost'],
                    'purchase_date' => $row['created_at'] ? now()->parse($row['created_at'])->format('Y-m-d') : null,
                    'created_at' => $row['created_at'] ? now()->parse($row['created_at']) : now(),
                    'updated_at' => $row['updated_at'] ? now()->parse($row['updated_at']) : now(),
                ]
            );
        }
    }

    private function importStockBatches(SQLite3 $sourceDb): void
    {
        $result = $sourceDb->query('
            SELECT i.*, s.name as supply_name, s.unit as supply_unit 
            FROM inventory i 
            JOIN supplies s ON i.supply_id = s.id
        ');
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $ingredient = DB::table('ingredients')
                ->where('name', $row['supply_name'])
                ->first();
            
            if (!$ingredient) {
                continue;
            }
            
            DB::table('stock_batches')->updateOrInsert(
                ['ingredient_id' => $ingredient->id, 'purchase_date' => $row['created_at'] ? now()->parse($row['created_at'])->format('Y-m-d') : now()->format('Y-m-d')],
                [
                    'quantity' => $row['quantity'],
                    'price_per_unit' => $row['cost'] > 0 ? $row['cost'] : 0,
                    'created_at' => $row['created_at'] ? now()->parse($row['created_at']) : now(),
                    'updated_at' => $row['updated_at'] ? now()->parse($row['updated_at']) : now(),
                ]
            );
        }
    }

    private function importRecipes(SQLite3 $sourceDb): void
    {
        $result = $sourceDb->query('SELECT * FROM recipes');
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            DB::table('recipes')->updateOrInsert(
                ['name' => $row['name']],
                [
                    'description' => $row['description'],
                    'created_at' => $row['created_at'] ? now()->parse($row['created_at']) : now(),
                    'updated_at' => $row['updated_at'] ? now()->parse($row['updated_at']) : now(),
                ]
            );
        }
    }

    private function importRecipeIngredients(SQLite3 $sourceDb): void
    {
        $result = $sourceDb->query('
            SELECT ri.*, r.name as recipe_name, s.name as supply_name 
            FROM recipe_ingredients ri 
            JOIN recipes r ON ri.recipe_id = r.id 
            JOIN supplies s ON ri.supply_id = s.id
        ');
        
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
                    'quantity' => $row['quantity'],
                    'add_time_minutes' => $row['add_time'] ?? 0,
                    'created_at' => $row['created_at'] ? now()->parse($row['created_at']) : now(),
                    'updated_at' => $row['updated_at'] ? now()->parse($row['updated_at']) : now(),
                ]
            );
        }
    }

    private function importBrews(SQLite3 $sourceDb): void
    {
        $result = $sourceDb->query('SELECT bs.*, r.name as recipe_name FROM brewing_sessions bs LEFT JOIN recipes r ON bs.recipe_id = r.id');
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $recipeId = null;
            if ($row['recipe_name']) {
                $recipe = DB::table('recipes')->where('name', $row['recipe_name'])->first();
                $recipeId = $recipe?->id;
            }
            
            $costPerLiter = null;
            if ($row['total_cost'] && $row['batch_volume'] > 0) {
                $costPerLiter = $row['total_cost'] / $row['batch_volume'];
            }
            
            DB::table('brews')->updateOrInsert(
                ['recipe_id' => $recipeId, 'created_at' => $row['created_at']],
                [
                    'volume_actual' => $row['batch_volume'] > 0 ? $row['batch_volume'] : null,
                    'cost_per_liter' => $costPerLiter,
                    'is_modified' => false,
                    'created_at' => $row['created_at'] ? now()->parse($row['created_at']) : now(),
                    'updated_at' => $row['updated_at'] ? now()->parse($row['updated_at']) : now(),
                ]
            );
        }
    }
}
