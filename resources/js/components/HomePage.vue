<template>
    <div class="page-container">
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
                    <div class="stat-num">{{ stats.recipes_count }}</div>
                    <div class="stat-label">Рецептов</div>
                </div>
            </div>
            <div class="card stat-card">
                <div class="stat-icon">🔥</div>
                <div>
                    <div class="stat-num">{{ stats.brews_count }}</div>
                    <div class="stat-label">Варок</div>
                </div>
            </div>
            <div class="card stat-card">
                <div class="stat-icon">🌾</div>
                <div>
                    <div class="stat-num">{{ stats.ingredients_count }}</div>
                    <div class="stat-label">Ингредиентов</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const stats = ref({
    recipes_count: 0,
    brews_count: 0,
    ingredients_count: 0
});

onMounted(async () => {
    try {
        const res = await fetch('/api/v1/home/stats');
        if (res.ok) {
            stats.value = await res.json();
        }
    } catch (e) {
        console.error('Ошибка загрузки статистики', e);
    }
});
</script>

<style scoped>
.page-container {
    padding: 20px;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.stat-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
}
.stat-icon {
    font-size: 40px;
}
.stat-num {
    font-size: 24px;
    font-weight: bold;
}
.stat-label {
    color: #6b7280;
    font-size: 14px;
}
</style>
