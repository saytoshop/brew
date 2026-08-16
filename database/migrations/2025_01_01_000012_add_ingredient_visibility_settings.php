<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Добавляем настройки по умолчанию, если их нет
        DB::table('settings')->updateOrInsert(
            ['key' => 'show_zero_stock_ingredients'],
            ['value' => 'false', 'created_at' => now(), 'updated_at' => now()]
        );
        
        DB::table('settings')->updateOrInsert(
            ['key' => 'show_existing_recipe_ingredients'],
            ['value' => 'false', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'show_zero_stock_ingredients',
            'show_existing_recipe_ingredients'
        ])->delete();
    }
};
