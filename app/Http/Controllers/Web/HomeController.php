<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\Brew;
use App\Models\Ingredient;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $stats = [
            'recipes_count' => Recipe::count(),
            'brews_count' => Brew::count(),
            'ingredients_count' => Ingredient::count(),
        ];

        return view('home.index', compact('stats'));
    }
}
