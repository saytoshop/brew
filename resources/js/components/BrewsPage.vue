<template>
    <div class="page-container">
        <div class="page-head">
            <div>
                <h1>Варки</h1>
                <p class="page-sub">История всех варок</p>
            </div>
            <button class="btn btn-primary" @click="startBrew">🔥 Начать варку</button>
        </div>
    
        <div class="card card-table">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Рецепт</th>
                        <th class="num">Объём (л)</th>
                        <th class="num">Себестоимость / л (₽)</th>
                        <th style="width: 120px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="b in brews" :key="b.id">
                        <td>{{ formatDate(b.created_at) }}</td>
                        <td>
                            <template v-if="b.is_modified">
                                <span class="badge badge-amber">🔧 Модифицирована</span>
                                <div class="cell-sub">{{ b.recipe_name || 'Без рецепта' }}</div>
                            </template>
                            <template v-else>{{ b.recipe_name || 'Без рецепта' }}</template>
                        </td>
                        <td class="num">{{ b.volume_actual || '—' }}</td>
                        <td class="num">{{ formatCost(b.cost_per_liter) }}</td>
                        <td>
                            <div class="actions-cell">
                                <a :href="'/brews/' + b.id" class="btn btn-outline btn-sm">Открыть</a>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="brews.length === 0">
                        <td colspan="5" class="empty-row">Варок пока нет</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const brews = ref([]);

const loadBrews = async () => {
    try {
        const res = await fetch('/api/v1/brews');
        if (res.ok) brews.value = await res.json();
    } catch (e) { console.error(e); }
};

const startBrew = () => {
    window.location.href = '/recipes';
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('ru-RU') : '';
const formatCost = (c) => c ? new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(c) : '—';

onMounted(loadBrews);
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
.card-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
}
.tbl {
    width: 100%;
    border-collapse: collapse;
}
.tbl th, .tbl td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}
.tbl th {
    background: #f9fafb;
    font-weight: 600;
}
.num {
    text-align: right;
}
.actions-cell {
    display: flex;
    gap: 5px;
}
.empty-row {
    text-align: center;
    color: #6b7280;
}
.badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
}
.badge-amber {
    background: #fef3c7;
    color: #92400e;
}
.cell-sub {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}
</style>
