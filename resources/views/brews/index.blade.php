@extends('layouts.app')

@section('title', 'Варки')
@section('content')
<div class="stack" id="brews-app">
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
                    <td>@{{ formatDate(b.created_at) }}</td>
                    <td>
                        <template v-if="b.is_modified">
                            <span class="badge badge-amber">🔧 Модифицирована</span>
                            <div class="cell-sub">@{{ b.recipe_name || 'Без рецепта' }}</div>
                        </template>
                        <template v-else>@{{ b.recipe_name || 'Без рецепта' }}</template>
                    </td>
                    <td class="num">@{{ b.volume_actual || '—' }}</td>
                    <td class="num">@{{ formatCost(b.cost_per_liter) }}</td>
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

<script>
const { createApp, ref, onMounted } = Vue;

createApp({
    setup() {
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

        return { brews, startBrew, formatDate, formatCost };
    }
}).mount('#brews-app');
</script>
@endsection
