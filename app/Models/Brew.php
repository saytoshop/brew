<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brew extends Model
{
    protected $fillable = ['recipe_id', 'volume_actual', 'cost_per_liter', 'is_modified', 'modified_diff'];

    protected $casts = [
        'volume_actual' => 'decimal:2',
        'cost_per_liter' => 'decimal:2',
        'is_modified' => 'boolean',
        'modified_diff' => 'array',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(BrewIngredient::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BrewComment::class);
    }
}
