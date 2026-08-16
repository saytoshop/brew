<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use PDO;

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
            'sqlite_file' => 'required|file|mimes:sqlite,db',
        ]);

        $file = $request->file('sqlite_file');
        $tempPath = $file->getRealPath();

        // Таблицы, которые НЕ нужно импортировать (системные или с отличающейся структурой)
        $excludedTables = [
            'users', 'migrations', 'password_reset_tokens', 'failed_jobs',
            'jobs', 'job_batches', 'cache', 'cache_locks', 'sessions',
            'sqlite_sequence', 'sqlite_master'
        ];

        try {
            // Подключаемся к временному файлу SQLite
            $sourceDb = new PDO("sqlite:$tempPath");
            $sourceDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Получаем список таблиц в источнике
            $sourceTables = $sourceDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
                ->fetchAll(\PDO::FETCH_COLUMN);

            DB::beginTransaction();

            foreach ($sourceTables as $tableName) {
                // Пропускаем исключенные таблицы
                if (in_array($tableName, $excludedTables)) {
                    continue;
                }

                // Проверяем, существует ли такая таблица в нашей БД
                if (!Schema::hasTable($tableName)) {
                    Log::warning("Table $tableName not found in destination. Skipping.");
                    continue;
                }

                // Получаем колонки источника
                $sourceColumnsStmt = $sourceDb->query("PRAGMA table_info('$tableName')");
                $sourceColumns = $sourceColumnsStmt->fetchAll(\PDO::FETCH_COLUMN, 1); // Имя колонки во втором столбце

                // Получаем колонки назначения (нашей БД)
                $destColumns = Schema::getColumnListing($tableName);

                // Находим пересечение колонок (только те, что есть в обеих БД)
                $commonColumns = array_intersect($sourceColumns, $destColumns);

                if (empty($commonColumns)) {
                    Log::warning("No common columns for table $tableName. Skipping.");
                    continue;
                }

                // Формируем список колонок для выборки
                $colsList = implode(', ', array_map(fn($c) => "\"$c\"", $commonColumns));

                // Выбираем данные из источника
                $rows = $sourceDb->query("SELECT $colsList FROM \"$tableName\"")->fetchAll(\PDO::FETCH_ASSOC);

                if (empty($rows)) {
                    continue;
                }

                // Вставляем данные в нашу БД
                // Используем upsert или игнорирование дублей по ID, если это уместно.
                // Для простоты используем insert, но можно добавить логику обновления.
                // Важно: если есть автоинкремент ID, нужно разрешить вставку явных ID

                foreach ($rows as $row) {
                    // Очищаем данные от лишних ключей (на всякий случай)
                    $cleanRow = [];
                    foreach ($commonColumns as $col) {
                        if (array_key_exists($col, $row)) {
                            $cleanRow[$col] = $row[$col];
                        }
                    }

                    // Если есть ID, пробуем вставить, иначе создаем новую
                    if (isset($cleanRow['id'])) {
                        DB::table($tableName)->updateOrInsert(['id' => $cleanRow['id']], $cleanRow);
                    } else {
                        DB::table($tableName)->insert($cleanRow);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Импорт успешно завершен!']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ошибка импорта: ' . $e->getMessage()], 500);
        }
    }
}
