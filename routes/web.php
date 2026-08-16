<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\BrewController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\IngredientController;

// Web routes (Blade pages)
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/equipment', [EquipmentController::class, 'index'])->name('equipment.index');
Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
Route::get('/recipes/create', [RecipeController::class, 'create'])->name('recipes.create');
Route::post('/recipes', [RecipeController::class, 'store'])->name('recipes.store');
Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])->name('recipes.show');
Route::get('/recipes/{recipe}/edit', [RecipeController::class, 'edit'])->name('recipes.edit');
Route::put('/recipes/{recipe}', [RecipeController::class, 'update'])->name('recipes.update');
Route::get('/brews', [BrewController::class, 'index'])->name('brews.index');
Route::get('/brews/{brew}', [BrewController::class, 'show'])->name('brews.show');
Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
Route::post('/settings/import', [SettingController::class, 'import'])->name('settings.import');

// API routes (JSON responses for Vue components)
// Categories
Route::get('/api/v1/categories', [CategoryController::class, 'data']);
Route::get('/api/v1/categories/{category}', [CategoryController::class, 'showData']);
Route::post('/api/v1/categories', [CategoryController::class, 'store']);
Route::put('/api/v1/categories/{category}', [CategoryController::class, 'update']);
Route::delete('/api/v1/categories/{category}', [CategoryController::class, 'destroy']);

// Units
Route::get('/api/v1/units', [UnitController::class, 'data']);
Route::get('/api/v1/units/{unit}', [UnitController::class, 'showData']);
Route::post('/api/v1/units', [UnitController::class, 'store']);
Route::put('/api/v1/units/{unit}', [UnitController::class, 'update']);
Route::delete('/api/v1/units/{unit}', [UnitController::class, 'destroy']);

// Ingredients
Route::get('/api/v1/ingredients', [IngredientController::class, 'data']);
Route::get('/api/v1/ingredients/{ingredient}', [IngredientController::class, 'showData']);
Route::post('/api/v1/ingredients', [IngredientController::class, 'store']);
Route::put('/api/v1/ingredients/{ingredient}', [IngredientController::class, 'update']);
Route::delete('/api/v1/ingredients/{ingredient}', [IngredientController::class, 'destroy']);

// Equipment
Route::get('/api/v1/equipment', [EquipmentController::class, 'data']);
Route::get('/api/v1/equipment/{equipment}', [EquipmentController::class, 'show']);
Route::post('/api/v1/equipment', [EquipmentController::class, 'store']);
Route::put('/api/v1/equipment/{equipment}', [EquipmentController::class, 'update']);
Route::delete('/api/v1/equipment/{equipment}', [EquipmentController::class, 'destroy']);

// Stock
Route::get('/api/v1/stock', [StockController::class, 'data']);
Route::post('/api/v1/stock/receipts', [StockController::class, 'receipt']);
Route::get('/api/v1/stock/forecast', [StockController::class, 'forecast']);

// Recipes
Route::get('/api/v1/recipes', [RecipeController::class, 'data']);
Route::get('/api/v1/recipes/{recipe}', [RecipeController::class, 'showData']);
Route::post('/api/v1/recipes', [RecipeController::class, 'store']);
Route::put('/api/v1/recipes/{recipe}', [RecipeController::class, 'update']);
Route::delete('/api/v1/recipes/{recipe}', [RecipeController::class, 'destroy']);

// Brews
Route::get('/api/v1/brews', [BrewController::class, 'data']);
Route::get('/api/v1/brews/{brew}', [BrewController::class, 'showData']);
Route::post('/api/v1/brews', [BrewController::class, 'store']);
Route::put('/api/v1/brews/{brew}/complete', [BrewController::class, 'complete']);
Route::get('/api/v1/brews/{brew}/comments', [BrewController::class, 'comments']);
Route::post('/api/v1/brews/{brew}/comments', [BrewController::class, 'addComment']);
Route::put('/api/v1/comments/{comment}', [BrewController::class, 'updateComment']);
Route::delete('/api/v1/comments/{comment}', [BrewController::class, 'deleteComment']);

// Settings
Route::get('/api/v1/settings', [SettingController::class, 'index']);
Route::put('/api/v1/settings', [SettingController::class, 'update']);
Route::post('/api/v1/settings/export-db', [SettingController::class, 'exportDb']);
Route::post('/api/v1/settings/import-db', [SettingController::class, 'importDb']);
