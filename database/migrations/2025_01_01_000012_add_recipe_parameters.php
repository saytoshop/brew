<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->decimal('wort_volume', 8, 2)->nullable()->after('description');
            $table->decimal('og_target', 6, 4)->nullable()->after('wort_volume');
            $table->decimal('fg_target', 6, 4)->nullable()->after('og_target');
            $table->decimal('ibu_target', 6, 2)->nullable()->after('fg_target');
            $table->decimal('color_ebc', 6, 2)->nullable()->after('ibu_target');
            $table->decimal('abv_target', 5, 2)->nullable()->after('color_ebc');
            $table->decimal('batch_size', 8, 2)->nullable()->after('abv_target');
            $table->integer('boil_time')->default(60)->after('batch_size');
            $table->integer('efficiency')->default(75)->after('boil_time');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn([
                'wort_volume', 'og_target', 'fg_target', 'ibu_target', 
                'color_ebc', 'abv_target', 'batch_size', 'boil_time', 'efficiency'
            ]);
        });
    }
};
