<template>
    <div class="page-container">
        <div class="page-head">
            <div>
                <h1>Оборудование</h1>
                <p class="page-sub">Учёт оборудования пивоварни</p>
            </div>
            <button class="btn btn-primary" @click="openCreateModal">+ Добавить оборудование</button>
        </div>
    
        <div class="card card-table">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th class="num">Цена (₽)</th>
                        <th class="num">Дата покупки</th>
                        <th style="width: 120px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in items" :key="item.id">
                        <td>{{ item.name }}</td>
                        <td class="num">{{ formatPrice(item.price) }}</td>
                        <td class="num">{{ formatDate(item.purchase_date) }}</td>
                        <td>
                            <div class="actions-cell">
                                <button class="icon-btn" title="Редактировать" @click="openEditModal(item)">✏️</button>
                                <button class="icon-btn danger" title="Удалить" @click="deleteItem(item.id)">🗑️</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="items.length === 0">
                        <td colspan="4" class="empty-row">Оборудования пока нет</td>
                    </tr>
                </tbody>
            </table>
        </div>
    
        <!-- Модальное окно создания/редактирования -->
        <div class="modal-overlay" v-if="modalOpen" @click.self="closeModal">
            <div class="modal">
                <div class="modal-head">
                    <div class="modal-title">{{ isEditing ? 'Редактирование' : 'Новое оборудование' }}</div>
                    <button class="modal-close" @click="closeModal">✕</button>
                </div>
                <div class="modal-body">
                    <div class="field">
                        <label>Название</label>
                        <input class="input" type="text" v-model="form.name" placeholder="Например, Котёл 50л">
                    </div>
                    <div class="field">
                        <label>Цена (₽)</label>
                        <input class="input" type="number" step="0.01" v-model="form.price" placeholder="0">
                    </div>
                    <div class="field">
                        <label>Дата покупки</label>
                        <input class="input" type="date" v-model="form.purchase_date">
                    </div>
                </div>
                <div class="modal-foot">
                    <button class="btn btn-outline" @click="closeModal">Отмена</button>
                    <button class="btn btn-primary" @click="saveItem" :disabled="!canSave">Сохранить</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';

const items = ref([]);
const modalOpen = ref(false);
const isEditing = ref(false);
const form = reactive({ id: null, name: '', price: '', purchase_date: '' });

const canSave = computed(() => form.name && form.price);

const loadItems = async () => {
    try {
        const res = await fetch('/api/v1/equipment');
        if (res.ok) {
            items.value = await res.json();
        }
    } catch (e) { console.error(e); }
};

const openCreateModal = () => {
    isEditing.value = false;
    form.id = null; form.name = ''; form.price = ''; form.purchase_date = new Date().toISOString().split('T')[0];
    modalOpen.value = true;
};

const openEditModal = (item) => {
    isEditing.value = true;
    form.id = item.id;
    form.name = item.name;
    form.price = item.price;
    form.purchase_date = item.purchase_date;
    modalOpen.value = true;
};

const closeModal = () => { modalOpen.value = false; };

const saveItem = async () => {
    if (!canSave.value) return;
    const url = isEditing.value ? `/api/v1/equipment/${form.id}` : '/api/v1/equipment';
    const method = isEditing.value ? 'PUT' : 'POST';
    
    try {
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            body: JSON.stringify({ name: form.name, price: parseFloat(form.price), purchase_date: form.purchase_date })
        });
        if (res.ok) {
            await loadItems();
            closeModal();
        }
    } catch (e) { console.error(e); }
};

const deleteItem = async (id) => {
    if (!confirm('Удалить оборудование?')) return;
    try {
        const res = await fetch(`/api/v1/equipment/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
        });
        if (res.ok) await loadItems();
    } catch (e) { console.error(e); }
};

const formatPrice = (price) => new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(price);
const formatDate = (d) => d ? new Date(d).toLocaleDateString('ru-RU') : '';

onMounted(loadItems);
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
.icon-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 16px;
    padding: 4px;
}
.icon-btn.danger:hover {
    opacity: 0.7;
}
.empty-row {
    text-align: center;
    color: #6b7280;
}
</style>
