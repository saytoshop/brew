@extends('layouts.app')

@section('title', 'Новый рецепт')

@section('content')
<div class="container">
    <!-- Header -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <a href="{{ route('recipes.index') }}" class="back-link" style="text-decoration: none; color: inherit;">←</a>
                Новый рецепт
            </h1>
            <div class="header-actions">
                <a href="{{ route('recipes.index') }}" class="btn btn-outline">Отмена</a>
                <button type="submit" form="recipe-form" class="btn btn-primary">Создать</button>
            </div>
        </div>
    </div>

    <form id="recipe-form" action="{{ route('recipes.store') }}" method="POST">
        @csrf

        <!-- Main Info Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Основная информация</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="name" class="form-label">Название рецепта</label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Описание</label>
                    <textarea id="description" name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="batch_size" class="form-label">Объем партии (л)</label>
                            <input type="number" step="0.1" id="batch_size" name="batch_size" class="form-control" value="{{ old('batch_size') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="boil_time" class="form-label">Время кипячения (мин)</label>
                            <input type="number" id="boil_time" name="boil_time" class="form-control" value="{{ old('boil_time', 60) }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="efficiency" class="form-label">Эффективность варки (%)</label>
                            <input type="number" id="efficiency" name="efficiency" class="form-control" value="{{ old('efficiency', 75) }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ingredients Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Ингредиенты</h2>
                <button type="button" class="btn btn-sm btn-outline" onclick="addIngredientRow()">+ Добавить ингредиент</button>
            </div>
            <div class="card-body">
                <table class="table" id="ingredients-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Ингредиент</th>
                            <th style="width: 20%;">Количество</th>
                            <th style="width: 15%;">Ед. изм.</th>
                            <th style="width: 15%;">Время добавления (мин)</th>
                            <th style="width: 10%;"></th>
                        </tr>
                    </thead>
                    <tbody id="ingredients-list">
                        <!-- Rows will be added via JS -->
                    </tbody>
                </table>
                <div id="empty-ingredients-message" class="text-center text-muted" style="padding: 2rem;">
                    Нет ингредиентов. Нажмите "+ Добавить ингредиент", чтобы начать.
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('recipes.index') }}" class="btn btn-outline">Отмена</a>
            <button type="submit" class="btn btn-primary">Создать рецепт</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    const ingredients = [];
    const allIngredients = @json($ingredientsList);
    const allUnits = @json($units);

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
                    <input type="text" name="ingredients[${index}][unit_name]" class="form-control unit-display" value="${item.unit_name || ''}" readonly>
                </td>
                <td>
                    <input type="number" name="ingredients[${index}][add_time]" class="form-control" value="${item.add_time || 0}">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeIngredient(${index})">×</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    function addIngredientRow() {
        ingredients.push({ ingredient_id: '', quantity: '', unit_name: '', add_time: 0 });
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
        const unitInput = document.querySelector(`input[name="ingredients[${index}][unit_name]"]`);
        
        const unitObj = allUnits.find(u => u.id == unitId);
        unitInput.value = unitObj ? unitObj.name : '';
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', renderIngredients);
</script>
@endpush
@endsection
