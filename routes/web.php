<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\EquipmentController;
use App\Http\Controllers\Web\StockController;
use App\Http\Controllers\Web\RecipeController;
use App\Http\Controllers\Web\BrewController;
use App\Http\Controllers\Web\SettingsController;

// Web routes (Blade pages)
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/equipment', [EquipmentController::class, 'index'])->name('equipment.index');
Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
Route::get('/recipes/create', [RecipeController::class, 'create'])->name('recipes.create');
Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])->name('recipes.show');
Route::get('/recipes/{recipe}/edit', [RecipeController::class, 'edit'])->name('recipes.edit');
Route::get('/brews', [BrewController::class, 'index'])->name('brews.index');
Route::get('/brews/{brew}', [BrewController::class, 'show'])->name('brews.show');
Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
