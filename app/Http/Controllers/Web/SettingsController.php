<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.index');
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'import_file' => 'required|file|mimes:sqlite,db,sqlite3'
        ]);

        try {
            $file = $request->file('import_file');
            $tempPath = $file->storeAs('imports', uniqid() . '.sqlite', 'public');
            $fullPath = storage_path('app/public/' . $tempPath);

            // Открываем SQLite базу
            $sqlite = new \PDO('sqlite:' . $fullPath);
            $sqlite->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $imported = 0;

            // Импортируем пользователей
            $users = $sqlite->query('SELECT * FROM users')->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($users as $user) {
                DB::table('users')->updateOrInsert(
                    ['email' => $user['email']],
                    [
                        'name' => $user['name'],
                        'password' => $user['password'],
                        'role' => $user['role'] ?? 'user',
                        'created_at' => $user['created_at'] ?? null,
                        'updated_at' => $user['updated_at'] ?? null,
                    ]
                );
                $imported++;
            }

            // Импортируем категории
            $categories = $sqlite->query('SELECT * FROM categories')->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($categories as $category) {
                DB::table('categories')->updateOrInsert(
                    ['name' => $category['name'], 'user_id' => $category['user_id']],
                    [
                        'color' => $category['color'] ?? 'gray',
                        'is_system' => $category['is_system'] ?? 0,
                        'created_at' => $category['created_at'] ?? null,
                        'updated_at' => $category['updated_at'] ?? null,
                    ]
                );
                $imported++;
            }

            // Импортируем supplies (ингредиенты)
            $supplies = $sqlite->query('SELECT * FROM supplies')->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($supplies as $supply) {
                DB::table('supplies')->updateOrInsert(
                    ['name' => $supply['name'], 'user_id' => $supply['user_id']],
                    [
                        'category_id' => $supply['category_id'],
                        'unit' => $supply['unit'],
                        'notes' => $supply['notes'] ?? null,
                        'created_at' => $supply['created_at'] ?? null,
                        'updated_at' => $supply['updated_at'] ?? null,
                    ]
                );
                $imported++;
            }

            // Импортируем оборудование
            $equipment = $sqlite->query('SELECT * FROM equipment')->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($equipment as $item) {
                DB::table('equipment')->updateOrInsert(
                    ['name' => $item['name'], 'user_id' => $item['user_id']],
                    [
                        'cost' => $item['cost'],
                        'notes' => $item['notes'] ?? null,
                        'created_at' => $item['created_at'] ?? null,
                        'updated_at' => $item['updated_at'] ?? null,
                    ]
                );
                $imported++;
            }

            // Импортируем рецепты
            $recipes = $sqlite->query('SELECT * FROM recipes')->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($recipes as $recipe) {
                DB::table('recipes')->updateOrInsert(
                    ['name' => $recipe['name'], 'user_id' => $recipe['user_id']],
                    [
                        'description' => $recipe['description'] ?? null,
                        'style' => $recipe['style'] ?? 'Other',
                        'target_volume' => $recipe['target_volume'] ?? 20,
                        'boil_time' => $recipe['boil_time'] ?? 60,
                        'mash_water_volume' => $recipe['mash_water_volume'] ?? 25,
                        'sparge_water_volume' => $recipe['sparge_water_volume'] ?? 15,
                        'target_og' => $recipe['target_og'] ?? null,
                        'target_fg' => $recipe['target_fg'] ?? null,
                        'target_abv' => $recipe['target_abv'] ?? null,
                        'target_ibu' => $recipe['target_ibu'] ?? null,
                        'instructions' => $recipe['instructions'] ?? null,
                        'mash_steps' => $recipe['mash_steps'] ?? null,
                        'created_at' => $recipe['created_at'] ?? null,
                        'updated_at' => $recipe['updated_at'] ?? null,
                    ]
                );
                $imported++;
            }

            // Импортируем варки
            $brews = $sqlite->query('SELECT * FROM brewing_sessions')->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($brews as $brew) {
                DB::table('brewing_sessions')->updateOrInsert(
                    ['name' => $brew['name'], 'user_id' => $brew['user_id']],
                    [
                        'recipe_id' => $brew['recipe_id'] ?? null,
                        'batch_volume' => $brew['batch_volume'] ?? 0,
                        'water_brewing_volume' => $brew['water_brewing_volume'] ?? 0,
                        'water_cleaning_volume' => $brew['water_cleaning_volume'] ?? 0,
                        'water_cooling_volume' => $brew['water_cooling_volume'] ?? 0,
                        'electricity_kwh' => $brew['electricity_kwh'] ?? 0,
                        'co2_grams' => $brew['co2_grams'] ?? 0,
                        'started_at' => $brew['started_at'] ?? null,
                        'finished_at' => $brew['finished_at'] ?? null,
                        'total_cost' => $brew['total_cost'] ?? null,
                        'notes' => $brew['notes'] ?? null,
                        'created_at' => $brew['created_at'] ?? null,
                        'updated_at' => $brew['updated_at'] ?? null,
                    ]
                );
                $imported++;
            }

            // Удаляем временный файл
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            return response()->json(['success' => true, 'imported' => $imported]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
