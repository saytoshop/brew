<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Setting;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed categories
        $categories = ['Солод', 'Хмель', 'Дрожжи', 'Добавки', 'Моющие средства'];
        foreach ($categories as $name) {
            Category::create(['name' => $name]);
        }

        // Seed units
        $units = ['кг', 'г', 'шт', 'упаковка', 'л', 'мл'];
        foreach ($units as $name) {
            Unit::create(['name' => $name]);
        }

        // Seed default settings
        $settings = [
            'energy_cost_per_kwh' => '5.5',
            'heater_power_kw' => '2.5',
            'water_drinking_cost' => '8',
            'water_technical_cost' => '3',
            'chiller_flow_l_per_min' => '6',
            'boil_time_minutes' => '60',
            'water_per_liter_ratio' => '1.5',
        ];
        foreach ($settings as $key => $value) {
            Setting::create(['key' => $key, 'value' => $value]);
        }
    }
}
