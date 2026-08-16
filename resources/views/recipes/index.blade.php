@extends('layouts.app')

@section('title', 'Рецепты')
@section('content')
<div class="main">
    <div class="page-head">
        <div>
            <h1>Рецепты</h1>
            <p class="page-sub">Управление рецептами пива</p>
        </div>
        <a href="{{ route('recipes.create') }}" class="btn btn-primary">+ Новый рецепт</a>
    </div>

    @if($recipes->isEmpty())
        <div class="card card-pad" style="margin-top: 24px; text-align: center; color: #6b7280;">
            Рецептов пока нет. Создайте первый рецепт!
        </div>
    @else
        <div class="recipes-grid" style="margin-top: 24px;">
            @foreach($recipes as $recipe)
                <div class="card recipe-card">
                    <div class="recipe-top">
                        <div class="recipe-header-left">
                            <div class="recipe-emoji">🍺</div>
                            <div class="recipe-name-wrap">
                                <div class="recipe-name">{{ $recipe->name }}</div>
                                @if(isset($recipe->brews_count) && $recipe->brews_count > 0)
                                    <span class="recipe-brews-badge">Варок: <b>{{ $recipe->brews_count }}</b></span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Список ингредиентов по категориям -->
                    <div class="recipe-ing-block">
                        @php
                            $groupedIngredients = $recipe->recipeIngredients && $recipe->recipeIngredients->count() > 0 
                                ? $recipe->recipeIngredients->groupBy('ingredient.category.name')
                                : collect();
                        @endphp
                        
                        @if($groupedIngredients->isNotEmpty())
                            @foreach($groupedIngredients as $categoryName => $ingredients)
                                <div class="recipe-ing-category">
                                    {{ $categoryName }}
                                </div>
                                <div class="recipe-ing-list">
                                    @foreach($ingredients as $item)
                                        <div class="recipe-ing-item">
                                            <span>{{ $item->ingredient->name }}</span>
                                            <span>{{ $item->quantity }} {{ $item->ingredient->unit->name ?? '' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        @else
                            <div class="recipe-ing-category">Нет ингредиентов</div>
                        @endif
                    </div>

                    <a href="{{ route('recipes.show', $recipe->id) }}" class="btn btn-outline btn-sm" style="align-self: flex-start; margin-top: auto;">
                        Посмотреть
                    </a>
                    <a href="{{ route('recipes.edit', $recipe->id) }}" class="btn btn-outline btn-sm" style="align-self: flex-start;">
                        Редактировать
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
