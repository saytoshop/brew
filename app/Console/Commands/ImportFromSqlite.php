<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportFromSqlite extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-from-sqlite {--source=import.sqlite : Source SQLite file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from external SQLite database with different structure';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sourceFile = $this->option('source');
        
        if (!file_exists($sourceFile)) {
            $this->error("Source file {$sourceFile} not found");
            return self::FAILURE;
        }

        $this->info("Importing data from {$sourceFile}...");

        // Connect to source SQLite database
        $sourceDb = new \SQLite3($sourceFile);
        
        if (!$sourceDb) {
            $this->error("Failed to connect to source database");
            return self::FAILURE;
        }

        try {
            DB::beginTransaction();

            // Import users
            $this->importUsers($sourceDb);

            // Import categories (need to create default unit first)
            $this->importCategories($sourceDb);

            // Import units (create default unit if needed)
            $this->ensureDefaultUnit();

            // Import ingredients (supplies in source)
            $this->importIngredients($sourceDb);

            // Import equipment
            $this->importEquipment($sourceDb);

            // Import stock batches (inventory in source)
            $this->importStockBatches($sourceDb);

            // Import recipes
            $this->importRecipes($sourceDb);

            // Import recipe ingredients
            $this->importRecipeIngredients($sourceDb);

            // Import brews (brewing_sessions in source)
            $this->importBrews($sourceDb);

            DB::commit();
            $this->info('Import completed successfully!');
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Import failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        } finally {
            $sourceDb->close();
        }
    }

    private function importUsers(\SQLite3 $sourceDb): void
    {
        $this->info('Importing users...');
        
        $result = $sourceDb->query('SELECT * FROM users');
        $count = 0;
        
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
            $count++;
        }
        
        $this->info("Imported {$count} users");
    }

    private function ensureDefaultUnit(): void
    {
        $this->info('Ensuring default units exist...');
        
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

    private function importCategories(\SQLite3 $sourceDb): void
    {
        $this->info('Importing categories...');
        
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
        
        $this->info("Imported {$count} categories");
    }

    private function importIngredients(\SQLite3 $sourceDb): void
    {
        $this->info('Importing ingredients (from supplies)...');
        
        $result = $sourceDb->query('SELECT s.*, c.name as category_name FROM supplies s JOIN categories c ON s.category_id = c.id');
        $count = 0;
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $category = DB::table('categories')->where('name', $row['category_name'])->first();
            
            if (!$category) {
                $this->warn("Category '{$row['category_name']}' not found for ingredient '{$row['name']}'");
                continue;
            }

            // Determine unit based on typical usage
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
            $count++;
        }
        
        $this->info("Imported {$count} ingredients");
    }

    private function determineUnit(string $name, string $originalUnit): string
    {
        $nameLower = mb_strtolower($name);
        
        // Check for typical units based on ingredient type
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
        
        return 'кг'; // Default
    }

    private function importEquipment(\SQLite3 $sourceDb): void
    {
        $this->info('Importing equipment...');
        
        $result = $sourceDb->query('SELECT * FROM equipment');
        $count = 0;
        
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
            $count++;
        }
        
        $this->info("Imported {$count} equipment items");
    }

    private function importStockBatches(\SQLite3 $sourceDb): void
    {
        $this->info('Importing stock batches (from inventory)...');
        
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
                $this->warn("Ingredient '{$row['supply_name']}' not found for stock batch");
                continue;
            }
            
            // Generate a unique identifier for this batch
            $batchKey = md5($row['supply_name'] . '_' . $row['quantity'] . '_' . $row['created_at']);
            
            DB::table('stock_batches')->updateOrInsert(
                ['ingredient_id' => $ingredient->id, 'purchase_date' => $row['created_at'] ? now()->parse($row['created_at'])->format('Y-m-d') : now()->format('Y-m-d')],
                [
                    'quantity' => $row['quantity'],
                    'price_per_unit' => $row['cost'] > 0 ? $row['cost'] : 0,
                    'created_at' => $row['created_at'] ? now()->parse($row['created_at']) : now(),
                    'updated_at' => $row['updated_at'] ? now()->parse($row['updated_at']) : now(),
                ]
            );
            $count++;
        }
        
        $this->info("Imported {$count} stock batches");
    }

    private function importRecipes(\SQLite3 $sourceDb): void
    {
        $this->info('Importing recipes...');
        
        $result = $sourceDb->query('SELECT * FROM recipes');
        $count = 0;
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            DB::table('recipes')->updateOrInsert(
                ['name' => $row['name']],
                [
                    'description' => $row['description'],
                    'created_at' => $row['created_at'] ? now()->parse($row['created_at']) : now(),
                    'updated_at' => $row['updated_at'] ? now()->parse($row['updated_at']) : now(),
                ]
            );
            $count++;
        }
        
        $this->info("Imported {$count} recipes");
    }

    private function importRecipeIngredients(\SQLite3 $sourceDb): void
    {
        $this->info('Importing recipe ingredients...');
        
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
                $this->warn("Recipe or ingredient not found for '{$row['recipe_name']}' - '{$row['supply_name']}'");
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
            $count++;
        }
        
        $this->info("Imported {$count} recipe ingredients");
    }

    private function importBrews(\SQLite3 $sourceDb): void
    {
        $this->info('Importing brews (from brewing_sessions)...');
        
        $result = $sourceDb->query('SELECT bs.*, r.name as recipe_name FROM brewing_sessions bs LEFT JOIN recipes r ON bs.recipe_id = r.id');
        $count = 0;
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $recipeId = null;
            if ($row['recipe_name']) {
                $recipe = DB::table('recipes')->where('name', $row['recipe_name'])->first();
                $recipeId = $recipe?->id;
            }
            
            // Calculate cost per liter if we have total cost and volume
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
            $count++;
        }
        
        $this->info("Imported {$count} brews");
    }
}
