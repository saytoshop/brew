@extends('layouts.app')

@section('title', 'Рецепты')
@section('content')
<div class="stack" id="recipes-app">
    <div class="page-head">
        <div>
            <h1>Рецепты</h1>
            <p class="page-sub">Ваша коллекция рецептов</p>
        </div>
        <a href="/recipes/create" class="btn btn-primary">+ Новый рецепт</a>
    </div>

    <div class="recipes-grid">
        <div class="card recipe-card" v-for="r in recipes" :key="r.id">
            <div class="recipe-top">
                <div class="recipe-header-left">
                    <span class="recipe-emoji">🍺</span>
                    <div class="recipe-name-wrap">
                        <div class="recipe-name">@{{ r.name }}</div>
                        <span class="recipe-brews-badge">🔥 <b>@{{ r.brews_count }}</b> @{{ plural(r.brews_count, ['варка', 'варки', 'варок']) }}</span>
                    </div>
                </div>
                <a :href="'/recipes/' + r.id + '/edit'" class="btn btn-outline btn-sm">✏️</a>
            </div>
            <div style="font-size: 12px; color: #6b7280;">@{{ r.ingredients_count }} ингредиентов</div>
            <a :href="'/recipes/' + r.id" class="btn btn-outline btn-sm" style="width: 100%">Открыть рецепт</a>
        </div>
        <div v-if="recipes.length === 0" class="card card-pad" style="text-align: center; color: #6b7280;">
            Рецептов пока нет. Создайте первый!
        </div>
    </div>
</div>

<script>
const { createApp, ref, onMounted } = Vue;

createApp({
    setup() {
        const recipes = ref([]);

        const plural = (n, forms) => {
            const n1 = Math.abs(n) % 100;
            const n2 = n1 % 10;
            if (n1 > 10 && n1 < 20) return forms[2];
            if (n2 > 1 && n2 < 5) return forms[1];
            if (n2 === 1) return forms[0];
            return forms[2];
        };

        const loadRecipes = async () => {
            try {
                const res = await fetch('/api/v1/recipes');
                if (res.ok) recipes.value = await res.json();
            } catch (e) { console.error(e); }
        };

        onMounted(loadRecipes);

        return { recipes, plural };
    }
}).mount('#recipes-app');
</script>
@endsection
