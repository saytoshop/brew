@extends('layouts.app')

@section('title', 'Главная')
@section('content')
<div class="stack">
    <div class="page-head">
        <div>
            <h1>Главная</h1>
            <p class="page-sub">Статистика пивоварни</p>
        </div>
    </div>

    <div class="stats-grid">
        <div class="card stat-card">
            <div class="stat-icon">🍺</div>
            <div>
                <div class="stat-num">{{ $stats['recipes_count'] }}</div>
                <div class="stat-label">Рецептов</div>
            </div>
        </div>
        <div class="card stat-card">
            <div class="stat-icon">🔥</div>
            <div>
                <div class="stat-num">{{ $stats['brews_count'] }}</div>
                <div class="stat-label">Варок</div>
            </div>
        </div>
        <div class="card stat-card">
            <div class="stat-icon">🌾</div>
            <div>
                <div class="stat-num">{{ $stats['ingredients_count'] }}</div>
                <div class="stat-label">Ингредиентов</div>
            </div>
        </div>
    </div>
</div>
@endsection
