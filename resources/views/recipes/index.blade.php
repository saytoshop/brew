@extends('layouts.app')

@section('title', 'Рецепты')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Рецепты</h1>
        <a href="{{ route('recipes.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            + Новый рецепт
        </a>
    </div>

    @if($recipes->isEmpty())
        <div class="bg-white rounded-lg shadow-md p-6 text-center text-gray-500">
            Рецептов пока нет. Создайте первый рецепт!
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($recipes as $recipe)
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 overflow-hidden flex flex-col">
                    <div class="p-6 flex-grow">
                        <h2 class="text-xl font-bold mb-2 text-gray-800">{{ $recipe->name }}</h2>
                        
                        @if($recipe->description)
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ Str::limit($recipe->description, 100) }}</p>
                        @endif

                        <!-- Список ингредиентов -->
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">Ингредиенты:</h3>
                            @if($recipe->recipeIngredients && $recipe->recipeIngredients->count() > 0)
                                @php
                                    $groupedIngredients = $recipe->recipeIngredients->groupBy('ingredient.category.name');
                                @endphp
                                <ul class="space-y-1 text-sm text-gray-700">
                                    @foreach($groupedIngredients as $categoryName => $ingredients)
                                        <li class="font-semibold text-gray-600 mt-2 border-b border-gray-200 pb-1">{{ $categoryName }}</li>
                                        @foreach($ingredients as $item)
                                            <li class="flex justify-between pl-2">
                                                <span>{{ $item->ingredient->name }}</span>
                                                <span class="font-medium">{{ $item->quantity }} {{ $item->ingredient->unit->name ?? '' }}</span>
                                            </li>
                                        @endforeach
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-gray-400 text-sm italic">Нет ингредиентов</p>
                            @endif
                        </div>

                        <div class="space-y-2 mb-4 pt-4 border-t border-gray-100">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Объем сусла:</span>
                                <span class="font-medium">{{ $recipe->wort_volume ?? '-' }} л</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Начальная плотность:</span>
                                <span class="font-medium">{{ $recipe->og_target ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Варок по рецепту:</span>
                                <span class="font-medium">{{ $recipe->brews_count ?? 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Кнопки действий -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex space-x-3">
                        <a href="{{ route('recipes.show', $recipe->id) }}" 
                           class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded transition-colors duration-200">
                            Посмотреть
                        </a>
                        <a href="{{ route('recipes.edit', $recipe->id) }}" 
                           class="flex-1 text-center bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded transition-colors duration-200">
                            Редактировать
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
