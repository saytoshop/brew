@extends('layouts.app')

@section('title', 'Редактировать рецепт: ' . ($recipe->name ?? 'Новый'))

@section('content')
    <div class="main">
        <!-- Header -->
        <div class="page-head">
            <div>
                <h1>{{ $recipe->id ? 'Редактировать рецепт' : 'Новый рецепт' }}</h1>
                <p class="page-sub">Управление ингредиентами рецепта</p>
            </div>
            <div class="actions-row">
                @if($recipe->id)
                    <a href="{{ route('recipes.show', $recipe->id) }}" class="btn btn-outline">Отмена</a>
                    <button type="button" form="recipe-form" class="btn btn-amber" onclick="brewRecipe(false)">🔥 Сварить</button>
                    <button type="button" form="recipe-form" class="btn btn-amber" onclick="brewRecipe(true)">🔥 Сварить + Сохранить</button>
                    <button type="submit" form="recipe-form" class="btn btn-primary">Сохранить</button>
                @else
                    <a href="{{ route('recipes.index') }}" class="btn btn-outline">Отмена</a>
                    <button type="submit" form="recipe-form" class="btn btn-primary">Создать</button>
                @endif
            </div>
        </div>

        <form id="recipe-form" action="{{ $recipe->id ? route('recipes.update', $recipe->id) : route('recipes.store') }}" method="POST">
            @csrf
            @if($recipe->id)
                @method('PUT')
            @endif

            <!-- Main Info Card -->
            <div class="card card-pad" style="margin-top: 24px;">
                <div class="field">
                    <label for="name" class="field-label">Название рецепта</label>
                    <input type="text" id="name" name="name" class="input" value="{{ old('name', $recipe->name ?? '') }}" required>
                </div>

                <div class="field">
                    <label for="description" class="field-label">Описание</label>
                    <textarea id="description" name="description" class="input" rows="4">{{ old('description', $recipe->description ?? '') }}</textarea>
                </div>

                <div class="settings-grid" style="margin-top: 16px;">
                    <div class="field">
                        <label for="batch_size" class="field-label">Объем партии (л)</label>
                        <input type="number" step="0.1" id="batch_size" name="batch_size" class="input" value="{{ old('batch_size', $recipe->batch_size ?? '') }}">
                    </div>
                    <div class="field">
                        <label for="boil_time" class="field-label">Время кипячения (мин)</label>
                        <input type="number" id="boil_time" name="boil_time" class="input" value="{{ old('boil_time', $recipe->boil_time ?? 60) }}">
                    </div>
                    <div class="field">
                        <label for="efficiency" class="field-label">Эффективность варки (%)</label>
                        <input type="number" id="efficiency" name="efficiency" class="input" value="{{ old('efficiency', $recipe->efficiency ?? 75) }}">
                    </div>
                </div>
            </div>

            <!-- Two Column Layout for Ingredients -->
            <div class="settings-grid" style="margin-top: 24px; gap: 20px;">
                <!-- Left Column: Recipe Ingredients -->
                <div>
                    <div class="card card-table" style="height: 100%;">
                        <div class="table-head">
                            <div class="section-title">Ингредиенты рецепта</div>
                        </div>
                        <div style="padding: 0 20px 20px; max-height: 500px; overflow-y: auto;">
                            <div id="recipe-ingredients-container">
                                <!-- Категории ингредиентов будут здесь -->
                            </div>
                            <div id="empty-ingredients-message" class="text-center text-muted" style="padding: 2rem; color: #6b7280; font-size: 13px; display: none;">
                                Нет ингредиентов. Кликните на ингредиент справа, чтобы добавить.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Stock Ingredients -->
                <div>
                    <div class="card card-table" style="height: 100%;">
                        <div class="table-head">
                            <div class="section-title">Ингредиенты на складе</div>
                        </div>
                        <div style="padding: 0 20px 20px; max-height: 500px; overflow-y: auto;">
                            @if($stockIngredients->isEmpty())
                                <div class="text-center text-muted" style="padding: 2rem; color: #6b7280; font-size: 13px;">
                                    Склад пуст или все ингредиенты уже добавлены в рецепт.
                                </div>
                            @else
                                @foreach($stockIngredients as $categoryName => $ingredients)
                                    <div class="ingredient-category" style="margin-bottom: 16px;">
                                        <div style="font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .03em; margin-bottom: 8px;">
                                            {{ $categoryName }}
                                        </div>
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            @foreach($ingredients as $ingredient)
                                                <div class="stock-ingredient-item" 
                                                     style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; border-radius: 8px; cursor: pointer; transition: background-color 0.15s;"
                                                     onmouseover="this.style.backgroundColor='#f9fafb'"
                                                     onmouseout="this.style.backgroundColor='transparent'"
                                                     onclick="addStockIngredient({{ json_encode($ingredient) }})">
                                                    <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                                                        <strong style="font-size: 13px; color: #1a1a1a;">{{ $ingredient['name'] }}</strong>
                                                        <span style="font-size: 11px; color: #6b7280;">{{ $ingredient['total_quantity'] }} {{ $ingredient['unit_name'] }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 24px; display: flex; gap: 10px;">
                <a href="{{ route('recipes.index') }}" class="btn btn-outline">Отмена</a>
                <button type="submit" class="btn btn-primary">{{ $recipe->id ? 'Сохранить изменения' : 'Создать рецепт' }}</button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            const allIngredients = @json($ingredientsList);
            let ingredients = [];
            let hasUnsavedChanges = false;

            // Инициализация ингредиентов рецепта
            @if($recipeIngredients->isNotEmpty())
                @foreach($recipeIngredients as $categoryName => $catIngredients)
                    @foreach($catIngredients as $ri)
                        ingredients.push({
                            ingredient_id: {{ $ri['ingredient_id'] }},
                            ingredient_name: '{{ addslashes($ri['ingredient_name']) }}',
                            quantity: {{ $ri['quantity'] }},
                            add_time_minutes: {{ $ri['add_time_minutes'] }},
                            unit_name: '{{ addslashes($ri['unit_name']) }}',
                            category_name: '{{ addslashes($categoryName) }}'
                        });
                    @endforeach
                @endforeach
            @endif

            function renderIngredients() {
                const container = document.getElementById('recipe-ingredients-container');
                const emptyMsg = document.getElementById('empty-ingredients-message');
                container.innerHTML = '';

                if (ingredients.length === 0) {
                    emptyMsg.style.display = 'block';
                    return;
                }
                emptyMsg.style.display = 'none';

                // Группируем ингредиенты по категориям
                const grouped = ingredients.reduce((acc, item) => {
                    const cat = item.category_name || 'Без категории';
                    if (!acc[cat]) acc[cat] = [];
                    acc[cat].push(item);
                    return acc;
                }, {});

                // Рендерим по категориям
                Object.keys(grouped).sort().forEach(categoryName => {
                    const categoryDiv = document.createElement('div');
                    categoryDiv.style.marginBottom = '16px';

                    // Заголовок категории
                    const catHeader = document.createElement('div');
                    catHeader.style.cssText = 'font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .03em; margin-bottom: 8px;';
                    catHeader.textContent = categoryName;
                    categoryDiv.appendChild(catHeader);

                    // Список ингредиентов категории
                    const listDiv = document.createElement('div');
                    listDiv.style.cssText = 'display: flex; flex-direction: column; gap: 4px;';

                    grouped[categoryName].forEach((item, index) => {
                        const rowDiv = document.createElement('div');
                        rowDiv.style.cssText = 'display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 8px; background: #fff;';
                        
                        // Название ингредиента (просто текст)
                        const nameSpan = document.createElement('span');
                        nameSpan.style.cssText = 'flex: 1; font-size: 13px; color: #1a1a1a;';
                        nameSpan.textContent = item.ingredient_name;
                        rowDiv.appendChild(nameSpan);

                        // Количество
                        const qtyInput = document.createElement('input');
                        qtyInput.type = 'number';
                        qtyInput.step = '0.01';
                        qtyInput.min = '0';
                        qtyInput.name = `ingredients[${index}][quantity]`;
                        qtyInput.value = item.quantity;
                        qtyInput.required = true;
                        qtyInput.style.cssText = 'width: 80px; padding: 6px 8px; font-size: 13px; border: 1px solid #d9dde3; border-radius: 6px;';
                        qtyInput.onchange = () => { hasUnsavedChanges = true; };
                        rowDiv.appendChild(qtyInput);

                        // Единица измерения (текст)
                        const unitSpan = document.createElement('span');
                        unitSpan.style.cssText = 'font-size: 12px; color: #6b7280; width: 40px;';
                        unitSpan.textContent = item.unit_name;
                        rowDiv.appendChild(unitSpan);

                        // Время добавления (скрытое поле, по умолчанию 0)
                        const timeInput = document.createElement('input');
                        timeInput.type = 'hidden';
                        timeInput.name = `ingredients[${index}][add_time]`;
                        timeInput.value = item.add_time_minutes || 0;
                        rowDiv.appendChild(timeInput);

                        // Скрытые поля
                        const ingIdInput = document.createElement('input');
                        ingIdInput.type = 'hidden';
                        ingIdInput.name = `ingredients[${index}][ingredient_id]`;
                        ingIdInput.value = item.ingredient_id;
                        rowDiv.appendChild(ingIdInput);

                        // Кнопка удаления
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'icon-btn danger';
                        removeBtn.textContent = '×';
                        removeBtn.onclick = () => { removeIngredient(index); hasUnsavedChanges = true; };
                        rowDiv.appendChild(removeBtn);

                        listDiv.appendChild(rowDiv);
                    });

                    categoryDiv.appendChild(listDiv);
                    container.appendChild(categoryDiv);
                });
            }

            function removeIngredient(index) {
                ingredients.splice(index, 1);
                renderIngredients();
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
                    unit_name: stockIng.unit_name,
                    category_name: stockIng.category_name
                });
                hasUnsavedChanges = true;
                renderIngredients();
            }

            function brewRecipe(saveFirst) {
                if (!{{ $recipe->id ? 'true' : 'false' }}) {
                    alert('Сначала создайте рецепт');
                    return;
                }

                if (saveFirst && hasUnsavedChanges) {
                    // Сначала сохраняем изменения, потом варим
                    document.getElementById('recipe-form').action = "{{ route('recipes.update', $recipe->id) }}";
                    document.getElementById('recipe-form').submit();
                    // После сохранения редирект на варку будет обработан сервером
                    return;
                }

                // Варим без сохранения (или с уже сохраненными изменениями)
                const diffData = hasUnsavedChanges ? JSON.stringify({
                    ingredients: ingredients.map(i => ({
                        ingredient_id: i.ingredient_id,
                        ingredient_name: i.ingredient_name,
                        quantity: i.quantity,
                        add_time_minutes: i.add_time_minutes
                    }))
                }) : null;

                // Отправляем форму на создание варки
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/brews';
                
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = document.querySelector('meta[name="csrf-token"]').content;
                form.appendChild(csrf);

                const recipeId = document.createElement('input');
                recipeId.type = 'hidden';
                recipeId.name = 'recipe_id';
                recipeId.value = {{ $recipe->id }};
                form.appendChild(recipeId);

                if (diffData) {
                    const isModified = document.createElement('input');
                    isModified.type = 'hidden';
                    isModified.name = 'is_modified';
                    isModified.value = '1';
                    form.appendChild(isModified);

                    const modifiedDiff = document.createElement('input');
                    modifiedDiff.type = 'hidden';
                    modifiedDiff.name = 'modified_diff';
                    modifiedDiff.value = diffData;
                    form.appendChild(modifiedDiff);
                }

                document.body.appendChild(form);
                form.submit();
            }

            // Initialize
            document.addEventListener('DOMContentLoaded', renderIngredients);
        </script>
    @endpush
@endsection
