<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\IngredientController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\RecipeController as ApiRecipeController;
use App\Http\Controllers\Api\BrewController;
use App\Http\Controllers\Api\SettingController;

// API v1 routes
Route::prefix('api/v1')->group(function () {
    // Справочники
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('units', UnitController::class);
    Route::apiResource('ingredients', IngredientController::class);
    Route::apiResource('equipment', EquipmentController::class);
    
    // Склад
    Route::get('stock', [StockController::class, 'index']);
    Route::post('stock/receipts', [StockController::class, 'receipt']);
    Route::get('stock/forecast', [StockController::class, 'forecast']);
    
    // Рецепты
    Route::apiResource('recipes', ApiRecipeController::class);
    
    // Варки
    Route::apiResource('brews', BrewController::class)->only(['index', 'show', 'store']);
    Route::put('brews/{brew}/complete', [BrewController::class, 'complete']);
    Route::get('brews/{brew}/comments', [BrewController::class, 'comments']);
    Route::post('brews/{brew}/comments', [BrewController::class, 'addComment']);
    Route::put('comments/{comment}', [BrewController::class, 'updateComment']);
    Route::delete('comments/{comment}', [BrewController::class, 'deleteComment']);
    
    // Настройки
    Route::get('settings', [SettingController::class, 'index']);
    Route::put('settings', [SettingController::class, 'update']);
    Route::post('settings/export-db', [SettingController::class, 'exportDb']);
});
