<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brew_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brew_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('stock_batches')->cascadeOnDelete();
            $table->decimal('quantity_used', 10, 4);
            $table->decimal('price_per_unit', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brew_ingredients');
    }
};
