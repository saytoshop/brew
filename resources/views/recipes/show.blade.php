@extends('layouts.app')

@section('title', $recipe->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('recipes.index') }}" class="text-blue-600 hover:text-blue-800">&larr; Назад к рецептам</a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h1 class="text-3xl font-bold mb-4">{{ $recipe->name }}</h1>
        
        @if($recipe->description)
            <p class="text-gray-700 mb-6">{{ $recipe->description }}</p>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-gray-50 p-4 rounded">
                <h3 class="font-semibold text-gray-600">Объем сусла</h3>
                <p class="text-xl">{{ $recipe->wort_volume ?? '-' }} л</p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
                <h3 class="font-semibold text-gray-600">Начальная плотность</h3>
                <p class="text-xl">{{ $recipe->og_target ?? '-' }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
                <h3 class="font-semibold text-gray-600">Конечная плотность</h3>
                <p class="text-xl">{{ $recipe->fg_target ?? '-' }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
                <h3 class="font-semibold text-gray-600">Горечь (IBU)</h3>
                <p class="text-xl">{{ $recipe->ibu_target ?? '-' }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
                <h3 class="font-semibold text-gray-600">Цвет (EBC)</h3>
                <p class="text-xl">{{ $recipe->color_ebc ?? '-' }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
                <h3 class="font-semibold text-gray-600">Алкоголь (ABV)</h3>
                <p class="text-xl">{{ $recipe->abv_target ?? '-' }} %</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-6">Ингредиенты</h2>
        
        @if($groupedIngredients->isEmpty())
            <p class="text-gray-500 italic">Ингредиенты не добавлены</p>
        @else
            @foreach($groupedIngredients as $categoryName => $ingredients)
                <div class="mb-6">
                    <h3 class="text-xl font-semibold text-gray-700 mb-3 border-b pb-2">{{ $categoryName }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Название</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Количество</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Время добавления (мин)</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Примечание</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($ingredients as $ingredient)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $ingredient->ingredient->name ?? 'Неизвестно' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                            {{ $ingredient->quantity }} {{ $ingredient->ingredient->unit->name ?? '' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                            {{ $ingredient->add_time_minutes ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                            {{ $ingredient->note ?? '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
@endsection
