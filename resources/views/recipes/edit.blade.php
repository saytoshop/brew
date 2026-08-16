@extends('layouts.app')

@section('title', 'Редактировать рецепт: ' . ($recipe->name ?? 'Новый'))

@section('content')
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div class="header-content">
                <h1 class="page-title">
                    <a href="{{ route('recipes.index') }}" class="back-link" style="text-decoration: none; color: inherit;">←</a>
                    {{ $recipe->id ? 'Редактировать рецепт' : 'Новый рецепт' }}
                </h1>
                <div class="header-actions">
                    @if($recipe->id)
                        <a href="{{ route('recipes.show', $recipe->id) }}" class="btn btn-outline">Отмена</a>
                        <button type="submit" form="recipe-form" class="btn btn-primary">Сохранить</button>
                    @else
                        <a href="{{ route('recipes.index') }}" class="btn btn-outline">Отмена</a>
                        <button type="submit" form="recipe-form" class="btn btn-primary">Создать</button>
                    @endif
                </div>
            </div>
        </div>

        <form id="recipe-form" action="{{ $recipe->id ? route('recipes.update', $recipe->id) : route('recipes.store') }}" method="POST">
            @csrf
            @if($recipe->id)
                @method('PUT')
            @endif

            <!-- Main Info Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Основная информация</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="name" class="form-label">Название рецепта</label>
                        <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $recipe->name ?? '') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Описание</label>
                        <textarea id="description" name="description" class="form-control" rows="4">{{ old('description', $recipe->description ?? '') }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="batch_size" class="form-label">Объем партии (л)</label>
                                <input type="number" step="0.1" id="batch_size" name="batch_size" class="form-control" value="{{ old('batch_size', $recipe->batch_size ?? '') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="boil_time" class="form-label">Время кипячения (мин)</label>
                                <input type="number" id="boil_time" name="boil_time" class="form-control" value="{{ old('boil_time', $recipe->boil_time ?? 60) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="efficiency" class="form-label">Эффективность варки (%)</label>
                                <input type="number" id="efficiency" name="efficiency" class="form-control" value="{{ old('efficiency', $recipe->efficiency ?? 75) }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout for Ingredients -->
            <div class="row">
                <!-- Left Column: Recipe Ingredients -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Ингредиенты рецепта</h2>
                            <button type="button" class="btn btn-sm btn-outline" onclick="addIngredientRow()">+ Добавить</button>
                        </div>
                        <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                            <table class="table" id="ingredients-table">
                                <thead>
                                <tr>
                                    <th style="width: 40%;">Ингредиент</th>
                                    <th style="width: 25%;">Количество</th>
                                    <th style="width: 20%;">Время (мин)</th>
                                    <th style="width: 15%;"></th>
                                </tr>
                                </thead>
                                <tbody id="ingredients-list">
                                <!-- Rows will be added via JS -->
                                </tbody>
                            </table>
                            <div id="empty-ingredients-message" class="text-center text-muted" style="padding: 2rem;">
                                Нет ингредиентов. Нажмите "+ Добавить", чтобы начать.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Stock Ingredients -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Ингредиенты на складе</h2>
                        </div>
                        <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                            @if($stockIngredients->isEmpty())
                                <div class="text-center text-muted" style="padding: 2rem;">
                                    Склад пуст. Добавьте ингредиенты на склад.
                                </div>
                            @else
                                @foreach($stockIngredients as $categoryName => $ingredients)
                                    <div class="ingredient-category" style="margin-bottom: 1.5rem;">
                                        <h4 style="margin-bottom: 0.75rem; color: var(--primary-color); border-bottom: 1px solid #e0e0e0; padding-bottom: 0.5rem;">
                                            {{ $categoryName }}
                                        </h4>
                                        @foreach($ingredients as $ingredient)
                                            <div class="stock-ingredient-item" 
                                                 style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; cursor: pointer; transition: background-color 0.2s;"
                                                 onmouseover="this.style.backgroundColor='#f5f5f5'"
                                                 onmouseout="this.style.backgroundColor='transparent'"
                                                 onclick="addStockIngredient({{ json_encode($ingredient) }})">
                                                <div>
                                                    <strong>{{ $ingredient['name'] }}</strong>
                                                    <div style="font-size: 0.85em; color: #666;">
                                                        Доступно: {{ $ingredient['total_quantity'] }} {{ $ingredient['unit_name'] }}
                                                    </div>
                                                </div>
                                                <span class="btn btn-sm btn-outline" style="pointer-events: none;">+ Добавить</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('recipes.index') }}" class="btn btn-outline">Отмена</a>
                <button type="submit" class="btn btn-primary">{{ $recipe->id ? 'Сохранить изменения' : 'Создать рецепт' }}</button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            const allIngredients = @json($ingredientsList);
            let ingredients = [];

            // Инициализация ингредиентов рецепта
            @if($recipeIngredients->isNotEmpty())
                @foreach($recipeIngredients as $categoryName => $catIngredients)
                    @foreach($catIngredients as $ri)
                        ingredients.push({
                            ingredient_id: {{ $ri['ingredient_id'] }},
                            ingredient_name: '{{ addslashes($ri['ingredient_name']) }}',
                            quantity: {{ $ri['quantity'] }},
                            add_time_minutes: {{ $ri['add_time_minutes'] }},
                            unit_name: '{{ addslashes($ri['unit_name']) }}'
                        });
                    @endforeach
                @endforeach
            @endif

            function renderIngredients() {
                const tbody = document.getElementById('ingredients-list');
                const emptyMsg = document.getElementById('empty-ingredients-message');
                tbody.innerHTML = '';

                if (ingredients.length === 0) {
                    emptyMsg.style.display = 'block';
                    return;
                }
                emptyMsg.style.display = 'none';

                ingredients.forEach((item, index) => {
                    const row = document.createElement('tr');

                    // Ingredient Select
                    let options = '<option value="">Выберите ингредиент</option>';
                    allIngredients.forEach(ing => {
                        const selected = ing.id == item.ingredient_id ? 'selected' : '';
                        options += `<option value="${ing.id}" data-unit="${ing.unit_id}" ${selected}>${ing.name}</option>`;
                    });

                    row.innerHTML = `
                <td>
                    <select name="ingredients[${index}][ingredient_id]" class="form-control ingredient-select" onchange="updateUnit(${index})">
                        ${options}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" name="ingredients[${index}][quantity]" class="form-control" value="${item.quantity || ''}" required>
                </td>
                <td>
                    <input type="number" name="ingredients[${index}][add_time]" class="form-control" value="${item.add_time_minutes || 0}">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeIngredient(${index})">×</button>
                </td>
            `;
                    tbody.appendChild(row);
                });
            }

            function addIngredientRow() {
                ingredients.push({ ingredient_id: '', ingredient_name: '', quantity: '', add_time_minutes: 0, unit_name: '' });
                renderIngredients();
            }

            function removeIngredient(index) {
                ingredients.splice(index, 1);
                renderIngredients();
            }

            function updateUnit(index) {
                const select = document.querySelector(`select[name="ingredients[${index}][ingredient_id]"]`);
                const option = select.options[select.selectedIndex];
                const unitId = option.getAttribute('data-unit');

                // Find unit name by ID
                const allUnits = @json($units);
                const unitObj = allUnits.find(u => u.id == unitId);
                
                const unitInput = document.querySelector(`input[name="ingredients[${index}][unit_name]"]`);
                if (unitInput && unitObj) {
                    unitInput.value = unitObj.name;
                }
            }

            function addStockIngredient(stockIng) {
                // Проверяем, есть ли уже этот ингредиент в рецепте
                const exists = ingredients.some(ing => ing.ingredient_id === stockIng.id);
                if (exists) {
                    alert('Этот ингредиент уже добавлен в рецепт');
                    return;
                }

                // Добавляем ингредиент в рецепт
                ingredients.push({
                    ingredient_id: stockIng.id,
                    ingredient_name: stockIng.name,
                    quantity: 0,
                    add_time_minutes: 0,
                    unit_name: stockIng.unit_name
                });
                renderIngredients();
            }

            // Initialize
            document.addEventListener('DOMContentLoaded', renderIngredients);
        </script>
    @endpush
@endsection
