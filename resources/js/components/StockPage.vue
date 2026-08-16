<template>
    <div class="page-container">
        <div class="page-head">
            <div>
                <h1>Склад</h1>
                <p class="page-sub">Учёт остатков ингредиентов</p>
            </div>
            <button class="btn btn-primary" @click="openIntakeModal">📦 Приёмка</button>
        </div>
    
        <!-- Прогноз -->
        <div class="card" v-if="forecast.length > 0">
            <div class="forecast">
                <span class="forecast-title">Прогноз:</span>
                <div class="forecast-items">
                    <div class="forecast-item" v-for="f in forecast" :key="f.ingredient_name">
                        <span class="forecast-emoji">{{ f.emoji }}</span>
                        <span class="forecast-val">{{ f.varoks }} варок</span>
                        <span class="forecast-name">{{ f.name }}</span>
                    </div>
                </div>
            </div>
        </div>
    
        <!-- Остатки -->
        <div class="card card-table">
            <div class="table-head">
                <div class="section-title">Текущие остатки</div>
            </div>
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Ингредиент</th>
                        <th>Категория</th>
                        <th class="num">Количество</th>
                        <th class="num">Цена за ед. (₽)</th>
                        <th class="num">Возраст партии (мес)</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="group in groupedStock" :key="group.category">
                        <tr class="group-row"><td colspan="5">{{ group.category }}</td></tr>
                        <tr v-for="item in group.items" :key="item.ingredient_id">
                            <td>{{ item.ingredient_name }}</td>
                            <td>{{ item.category }}</td>
                            <td class="num">{{ item.total_quantity }} {{ item.unit }}</td>
                            <td class="num">{{ formatPrice(item.avg_price) }}</td>
                            <td class="num">{{ item.old_batch_age_months }}</td>
                        </tr>
                    </template>
                    <tr v-if="stock.length === 0">
                        <td colspan="5" class="empty-row">Склад пуст</td>
                    </tr>
                </tbody>
            </table>
            <div style="padding: 12px 20px;">
                <button class="btn btn-outline btn-sm" @click="copyStockList">📋 Скопировать список остатков</button>
            </div>
        </div>
    
        <!-- Модалка приёмки -->
        <div class="modal-overlay" v-if="intakeOpen" @click.self="intakeOpen = false">
            <div class="modal">
                <div class="modal-head">
                    <div class="modal-title">Приёмка ингредиента</div>
                    <button class="modal-close" @click="intakeOpen = false">✕</button>
                </div>
                <div class="modal-body">
                    <div class="field">
                        <label>Ингредиент</label>
                        <select class="input" v-model="intake.ingredient_id">
                            <option value="" disabled>Выберите…</option>
                            <option v-for="ing in ingredients" :key="ing.id" :value="ing.id">{{ ing.name }} ({{ ing.category_name }}, {{ ing.unit_name }})</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Количество</label>
                        <input class="input" type="number" step="0.01" v-model.number="intake.quantity" placeholder="0">
                    </div>
                    <div class="field">
                        <label>Цена за единицу (₽)</label>
                        <input class="input" type="number" step="0.01" v-model.number="intake.price_per_unit" placeholder="0">
                    </div>
                    <div class="field">
                        <label>Дата закупки</label>
                        <input class="input" type="date" v-model="intake.purchase_date">
                    </div>
                </div>
                <div class="modal-foot">
                    <button class="btn btn-outline" @click="intakeOpen = false">Отмена</button>
                    <button class="btn btn-primary" @click="submitIntake" :disabled="!canSubmitIntake">Сохранить</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';

const stock = ref([]);
const ingredients = ref([]);
const forecast = ref([]);
const intakeOpen = ref(false);
const intake = reactive({ ingredient_id: '', quantity: '', price_per_unit: '', purchase_date: new Date().toISOString().split('T')[0] });

const canSubmitIntake = computed(() => intake.ingredient_id && intake.quantity > 0 && intake.price_per_unit > 0);

const groupedStock = computed(() => {
    const map = new Map();
    for (const item of stock.value) {
        if (!map.has(item.category)) map.set(item.category, []);
        map.get(item.category).push(item);
    }
    const result = [];
    for (const [cat, items] of map) result.push({ category: cat, items });
    return result;
});

const loadStock = async () => {
    try {
        const res = await fetch('/api/v1/stock');
        if (res.ok) stock.value = await res.json();
    } catch (e) { console.error(e); }
};

const loadIngredients = async () => {
    try {
        const res = await fetch('/api/v1/ingredients');
        if (res.ok) ingredients.value = await res.json();
    } catch (e) { console.error(e); }
};

const loadForecast = async () => {
    try {
        const res = await fetch('/api/v1/stock/forecast');
        if (res.ok) forecast.value = await res.json();
    } catch (e) { console.error(e); }
};

const openIntakeModal = () => { intakeOpen.value = true; };

const submitIntake = async () => {
    if (!canSubmitIntake.value) return;
    try {
        const res = await fetch('/api/v1/stock/receipts', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            body: JSON.stringify(intake)
        });
        if (res.ok) {
            await loadStock();
            intakeOpen.value = false;
            intake.ingredient_id = ''; intake.quantity = ''; intake.price_per_unit = '';
        }
    } catch (e) { console.error(e); }
};

const copyStockList = async () => {
    const lines = stock.value.map(s => `${s.ingredient_name} (${s.category}): ${s.total_quantity} ${s.unit} (старая партия ${s.old_batch_age_months} мес)`);
    const text = lines.join('\n');
    try {
        await navigator.clipboard.writeText(text);
        alert('Скопировано в буфер обмена');
    } catch (e) { console.error(e); }
};

const formatPrice = (p) => p ? new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(p) : '—';

onMounted(async () => {
    await loadIngredients();
    await loadStock();
    await loadForecast();
});
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
}
.btn-primary { background: #3b82f6; color: white; }
.btn-outline { background: transparent; border: 1px solid #ddd; }
.btn-sm { padding: 4px 10px; font-size: 13px; }
.card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.forecast {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}
.forecast-title {
    font-weight: bold;
}
.forecast-items {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}
.forecast-item {
    display: flex;
    align-items: center;
    gap: 5px;
    background: #f0f9ff;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 13px;
}
.card-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
}
.table-head {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}
.section-title {
    font-weight: bold;
    font-size: 16px;
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
.group-row td {
    background: #f3f4f6;
    font-weight: bold;
}
.empty-row {
    text-align: center;
    color: #6b7280;
}
.modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
.modal {
    background: white;
    border-radius: 8px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}
.modal-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}
.modal-title {
    font-size: 18px;
    font-weight: bold;
}
.modal-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
}
.modal-body {
    padding: 20px;
}
.modal-foot {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 15px 20px;
    border-top: 1px solid #eee;
}
.field {
    margin-bottom: 15px;
}
.field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}
.input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
</style>
