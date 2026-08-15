<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function index(): View
    {
        $recipes = Recipe::withCount('brews')->orderBy('created_at', 'desc')->get();
        return view('recipes.index', compact('recipes'));
    }

    public function show(Recipe $recipe): View
    {
        return view('recipes.show', compact('recipe'));
    }

    public function create(): View
    {
        return view('recipes.create');
    }

    public function edit(Recipe $recipe): View
    {
        return view('recipes.edit', compact('recipe'));
    }
}
