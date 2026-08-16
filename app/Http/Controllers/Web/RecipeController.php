<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\Ingredient;
use App\Models\Unit;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function index(): View
    {
        $recipes = Recipe::withCount('brews')->with('recipeIngredients')->orderBy('created_at', 'desc')->get();
        return view('recipes.index', compact('recipes'));
    }

    public function show(Recipe $recipe): View
    {
        $recipe->load(['recipeIngredients.ingredient.category', 'recipeIngredients.ingredient.unit']);
        
        $groupedIngredients = $recipe->recipeIngredients->groupBy(function($ingredient) {
            return $ingredient->ingredient->category->name ?? 'Без категории';
        });

        return view('recipes.show', compact('recipe', 'groupedIngredients'));
    }

    public function create(): View
    {
        $ingredientsList = Ingredient::with('category')->orderBy('name')->get();
        $units = Unit::all();
        
        return view('recipes.create', compact('ingredientsList', 'units'));
    }

    public function edit(Recipe $recipe): View
    {
        $recipe->load('recipeIngredients');
        $ingredientsList = Ingredient::with('category')->orderBy('name')->get();
        $units = Unit::all();
        
        return view('recipes.edit', compact('recipe', 'ingredientsList', 'units'));
    }
}
