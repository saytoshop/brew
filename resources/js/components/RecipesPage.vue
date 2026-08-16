<template>
    <div class="page-container">
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
                            <div class="recipe-name">{{ r.name }}</div>
                            <span class="recipe-brews-badge">🔥 <b>{{ r.brews_count }}</b> {{ plural(r.brews_count) }}</span>
                        </div>
                    </div>
                    <a :href="'/recipes/' + r.id + '/edit'" class="btn btn-outline btn-sm">✏️</a>
                </div>
                <div style="font-size: 12px; color: #6b7280;">{{ r.ingredients_count }} ингредиентов</div>
                <a :href="'/recipes/' + r.id" class="btn btn-outline btn-sm" style="width: 100%">Открыть рецепт</a>
            </div>
            <div v-if="recipes.length === 0" class="card card-pad" style="text-align: center; color: #6b7280;">
                Рецептов пока нет. Создайте первый!
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const recipes = ref([]);

const plural = (n) => {
    const forms = ['варка', 'варки', 'варок'];
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
</script>

<style scoped>
.page-container {
    padding: 20px;
}
.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
}
.btn-primary { background: #3b82f6; color: white; }
.btn-outline { background: transparent; border: 1px solid #ddd; color: #374151; }
.btn-sm { padding: 4px 10px; font-size: 13px; }
.recipes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.recipe-card {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.recipe-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.recipe-header-left {
    display: flex;
    gap: 10px;
    align-items: flex-start;
}
.recipe-emoji {
    font-size: 24px;
}
.recipe-name {
    font-weight: bold;
    font-size: 16px;
}
.recipe-brews-badge {
    font-size: 12px;
    color: #6b7280;
    background: #f3f4f6;
    padding: 2px 8px;
    border-radius: 12px;
}
.card-pad {
    padding: 40px 20px;
}
</style>
