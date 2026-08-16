<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('water_brewing_cost', 10, 4)->default(0);
            $table->decimal('water_cleaning_cost', 10, 4)->default(0);
            $table->decimal('electricity_cost', 10, 4)->default(0);
            $table->decimal('co2_cost', 10, 4)->default(0);
            $table->decimal('hourly_rate', 10, 4)->default(0);
            $table->decimal('fuel_consumption', 10, 4)->default(0);
            $table->decimal('fuel_cost', 10, 4)->default(0);
            $table->boolean('include_fuel_in_costs')->default(false);
            $table->boolean('include_depreciation_in_costs')->default(false);
            $table->decimal('equipment_volume', 10, 4)->default(0);
            $table->decimal('max_power', 10, 4)->default(0);
            $table->decimal('water_cost_per_cubic_meter', 10, 4)->default(0);
            $table->decimal('gas_cost_per_cubic_meter', 10, 4)->default(0);
            $table->decimal('osmosis_water_cost', 10, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
