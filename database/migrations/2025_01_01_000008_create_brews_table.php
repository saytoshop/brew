<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('volume_actual', 8, 2)->nullable();
            $table->decimal('cost_per_liter', 10, 2)->nullable();
            $table->boolean('is_modified')->default(false);
            $table->json('modified_diff')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brews');
    }
};
