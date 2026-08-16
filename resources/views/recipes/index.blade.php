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
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-xl font-bold mb-2 text-gray-800">{{ $recipe->name }}</h2>
                        
                        @if($recipe->description)
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ Str::limit($recipe->description, 100) }}</p>
                        @endif

                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Объем сусла:</span>
                                <span class="font-medium">{{ $recipe->wort_volume ?? '-' }} л</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Начальная плотность:</span>
                                <span class="font-medium">{{ $recipe->og_target ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Горечь (IBU):</span>
                                <span class="font-medium">{{ $recipe->ibu_target ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Ингредиентов:</span>
                                <span class="font-medium">{{ $recipe->recipeIngredients->count() ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Варок по рецепту:</span>
                                <span class="font-medium">{{ $recipe->brews_count ?? 0 }}</span>
                            </div>
                        </div>

                        <a href="{{ route('recipes.show', $recipe->id) }}" 
                           class="block w-full text-center bg-blue-50 hover:bg-blue-100 text-blue-600 font-semibold py-2 px-4 rounded transition-colors duration-200">
                            Подробнее
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
