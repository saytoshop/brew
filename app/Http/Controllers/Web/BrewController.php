<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Brew;
use Illuminate\View\View;

class BrewController extends Controller
{
    public function index(): View
    {
        $brews = Brew::with('recipe')->orderBy('created_at', 'desc')->get();
        return view('brews.index', compact('brews'));
    }

    public function show(Brew $brew): View
    {
        return view('brews.show', compact('brew'));
    }
}
