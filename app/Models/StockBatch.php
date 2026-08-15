<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockBatch extends Model
{
    protected $fillable = ['ingredient_id', 'quantity', 'price_per_unit', 'purchase_date'];

    protected $casts = [
        'quantity' => 'decimal:4',
        'price_per_unit' => 'decimal:2',
        'purchase_date' => 'date',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function brewIngredients(): HasMany
    {
        return $this->hasMany(BrewIngredient::class);
    }
}
