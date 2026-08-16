@extends('layouts.app')

@section('title', $recipe->name)

@section('content')
<div class="main">
    <div class="page-head" style="margin-bottom: 24px;">
        <div>
            <a href="{{ route('recipes.index') }}" style="font-size: 13px; color: #6b7280;">&larr; Назад к рецептам</a>
            <h1 style="margin-top: 8px;">{{ $recipe->name }}</h1>
            @if($recipe->description)
                <p class="page-sub">{{ Str::limit($recipe->description, 150) }}</p>
            @endif
        </div>
        <div class="actions-row">
            <a href="{{ route('recipes.edit', $recipe->id) }}" class="btn btn-outline">Редактировать</a>
        </div>
    </div>

    <div class="card card-pad" style="margin-bottom: 24px;">
        <div class="info-grid">
            <div class="info-item">
                <div class="k">Объем сусла</div>
                <div class="v">{{ $recipe->wort_volume ?? '-' }} л</div>
            </div>
            <div class="info-item">
                <div class="k">Начальная плотность</div>
                <div class="v">{{ $recipe->og_target ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="k">Конечная плотность</div>
                <div class="v">{{ $recipe->fg_target ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="k">Горечь (IBU)</div>
                <div class="v">{{ $recipe->ibu_target ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="k">Цвет (EBC)</div>
                <div class="v">{{ $recipe->color_ebc ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="k">Алкоголь (ABV)</div>
                <div class="v">{{ $recipe->abv_target ?? '-' }} %</div>
            </div>
        </div>
    </div>

    <div class="card card-table">
        <div class="table-head">
            <h2 class="section-title">Ингредиенты</h2>
        </div>
        
        @if($groupedIngredients->isEmpty())
            <div style="padding: 20px; text-align: center; color: #6b7280; font-size: 14px;">
                Ингредиенты не добавлены
            </div>
        @else
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th class="num">Количество</th>
                        <th class="num">Время (мин)</th>
                        <th>Примечание</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupedIngredients as $categoryName => $ingredients)
                        <tr class="group-row">
                            <td colspan="4">{{ $categoryName }}</td>
                        </tr>
                        @foreach($ingredients as $ingredient)
                            <tr>
                                <td>{{ $ingredient->ingredient->name ?? 'Неизвестно' }}</td>
                                <td class="num">
                                    {{ $ingredient->quantity }} {{ $ingredient->ingredient->unit->name ?? '' }}
                                </td>
                                <td class="num">{{ $ingredient->add_time_minutes ?? '-' }}</td>
                                <td>{{ $ingredient->note ?? '-' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
