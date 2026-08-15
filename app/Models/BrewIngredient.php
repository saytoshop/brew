<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrewIngredient extends Model
{
    protected $fillable = ['brew_id', 'ingredient_id', 'batch_id', 'quantity_used', 'price_per_unit'];

    protected $casts = [
        'quantity_used' => 'decimal:4',
        'price_per_unit' => 'decimal:2',
    ];

    public function brew(): BelongsTo
    {
        return $this->belongsTo(Brew::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }
}
