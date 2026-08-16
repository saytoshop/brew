@extends('layouts.app')

@section('title', $brew->recipe ? $brew->recipe->name : 'Варка #' . $brew->id)

@section('content')
<div class="main">
    <div class="page-head" style="margin-bottom: 24px;">
        <div>
            <a href="{{ route('brews.index') }}" style="font-size: 13px; color: #6b7280;">&larr; Назад к варкам</a>
            <h1 style="margin-top: 8px;">
                {{ $brew->recipe ? $brew->recipe->name : 'Варка #' . $brew->id }}
                @if($brew->is_modified)
                    <span class="badge badge-amber" style="margin-left: 8px;">🔧 Модифицирована</span>
                @endif
            </h1>
            <p class="page-sub">Дата: {{ $brew->created_at->format('d.m.Y H:i') }}</p>
        </div>
    </div>

    <div class="card card-pad" style="margin-bottom: 24px;">
        <div class="info-grid">
            <div class="info-item">
                <div class="k">Рецепт</div>
                <div class="v">{{ $brew->recipe ? $brew->recipe->name : '-' }}</div>
            </div>
            <div class="info-item">
                <div class="k">Объем (л)</div>
                <div class="v">{{ $brew->volume_actual ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="k">Себестоимость (л)</div>
                <div class="v">{{ $brew->cost_per_liter ? number_format($brew->cost_per_liter, 2) : '-' }}</div>
            </div>
            <div class="info-item">
                <div class="k">Изменён</div>
                <div class="v">{{ $brew->is_modified ? 'Да' : 'Нет' }}</div>
            </div>
        </div>
    </div>

    @if($brew->is_modified && $brew->modified_diff)
        <div class="card card-pad" style="margin-bottom: 24px;">
            <div class="section-title" style="margin-bottom: 16px;">🔀 Изменения в рецепте</div>
            <p class="hint" style="margin-bottom: 12px;">Сравнение с исходным рецептом:</p>
            
            @if(isset($brew->modified_diff['ingredients']) && is_array($brew->modified_diff['ingredients']))
                <div class="card card-table">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Ингредиент</th>
                                <th class="num">Количество</th>
                                <th class="num">Время добавления (мин)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($brew->modified_diff['ingredients'] as $ing)
                                <tr>
                                    <td>{{ $ing['ingredient_name'] ?? 'Неизвестно' }}</td>
                                    <td class="num">{{ $ing['quantity'] ?? '-' }}</td>
                                    <td class="num">{{ $ing['add_time_minutes'] ?? '0' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    @if($brew->ingredients && $brew->ingredients->count() > 0)
        <div class="card card-table">
            <div class="table-head">
                <h2 class="section-title">Ингредиенты</h2>
            </div>
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Категория</th>
                        <th class="num">Количество</th>
                        <th class="num">Цена за ед.</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $groupedIngredients = $brew->ingredients->groupBy('ingredient.category.name');
                    @endphp
                    @foreach($groupedIngredients as $categoryName => $categoryIngredients)
                        <tr class="group-row">
                            <td colspan="4">{{ $categoryName }}</td>
                        </tr>
                        @foreach($categoryIngredients as $bi)
                            @php
                                $isModifiedIngredient = false;
                                if ($brew->is_modified && isset($brew->modified_diff['ingredients'])) {
                                    foreach ($brew->modified_diff['ingredients'] as $modIng) {
                                        if (($modIng['ingredient_id'] ?? null) == $bi->ingredient_id) {
                                            $isModifiedIngredient = true;
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            <tr style="{{ $isModifiedIngredient ? 'background: #fff9e6;' : '' }}">
                                <td>
                                    {{ $bi->ingredient->name ?? 'Неизвестно' }}
                                    @if($isModifiedIngredient)
                                        <span class="badge badge-amber" style="font-size: 10px; padding: 2px 6px; margin-left: 6px;">изм.</span>
                                    @endif
                                </td>
                                <td>{{ $bi->ingredient->category->name ?? '-' }}</td>
                                <td class="num">{{ $bi->quantity_used }} {{ $bi->ingredient->unit->name ?? '' }}</td>
                                <td class="num">{{ number_format($bi->price_per_unit, 2) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($brew->comments && $brew->comments->count() > 0)
        <div class="card card-pad" style="margin-top: 24px;">
            <h2 class="section-title" style="margin-bottom: 16px;">Комментарии</h2>
            @foreach($brew->comments as $comment)
                <div style="padding: 12px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px;">
                    <div style="color: #6b7280; font-size: 12px; margin-bottom: 4px;">
                        {{ $comment->created_at->format('d.m.Y H:i') }}
                    </div>
                    <div>{{ $comment->content }}</div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
