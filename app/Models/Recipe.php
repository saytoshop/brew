<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Recipe extends Model
{
    protected $fillable = [
        'name', 
        'description',
        'wort_volume',
        'og_target',
        'fg_target',
        'ibu_target',
        'color_ebc',
        'abv_target',
        'batch_size',
        'boil_time',
        'efficiency',
    ];

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function brews(): HasMany
    {
        return $this->hasMany(Brew::class);
    }
}
