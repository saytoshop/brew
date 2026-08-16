<template>
    <div class="settings-container">
        <h1>Настройки</h1>

        <!-- Форма настроек пользователя -->
        <div class="card">
            <h2>Финансовые настройки</h2>
            <form @submit.prevent="saveSettings">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Стоимость воды (варка) за м³</label>
                        <input type="number" step="0.01" v-model="settings.water_brewing_cost" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Стоимость воды (мойка) за м³</label>
                        <input type="number" step="0.01" v-model="settings.water_cleaning_cost" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Стоимость электричества за кВт·ч</label>
                        <input type="number" step="0.01" v-model="settings.electricity_cost" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Стоимость CO₂ за грамм</label>
                        <input type="number" step="0.01" v-model="settings.co2_cost" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Часовая ставка (труд)</label>
                        <input type="number" step="0.01" v-model="settings.hourly_rate" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Расход топлива (л/варка)</label>
                        <input type="number" step="0.01" v-model="settings.fuel_consumption" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Стоимость топлива за литр</label>
                        <input type="number" step="0.01" v-model="settings.fuel_cost" class="form-input">
                    </div>
                </div>

                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="settings.include_fuel_in_costs">
                        Включать топливо в расчет себестоимости
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="settings.include_depreciation_in_costs">
                        Включать амортизацию в расчет себестоимости
                    </label>
                </div>

                <h3 style="margin: 20px 0 15px; font-size: 16px;">Настройки ингредиентов</h3>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="settings.show_zero_stock_ingredients">
                        Показывать ингредиенты с нулевым остатком на складе
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="settings.show_existing_recipe_ingredients">
                        Показывать ингредиенты, уже добавленные в рецепт
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" :disabled="loading">
                    {{ loading ? 'Сохранение...' : 'Сохранить настройки' }}
                </button>
            </form>
        </div>

        <!-- Секция импорта -->
        <div class="card">
            <h2>Импорт данных</h2>
            <p class="text-muted">Импортируйте данные из стороннего приложения (SQLite формат).</p>
            
            <div class="import-area">
                <input type="file" ref="fileInput" accept=".sqlite,.db,.sqlite3" class="file-input" @change="handleFileSelect">
                
                <button 
                    @click="uploadFile" 
                    class="btn btn-success" 
                    :disabled="!selectedFile || uploading"
                >
                    {{ uploading ? 'Импорт...' : '📥 Импорт БД из файла' }}
                </button>

                <div v-if="importMessage" :class="['message', importSuccess ? 'message-success' : 'message-error']">
                    {{ importMessage }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';

const loading = ref(false);
const uploading = ref(false);
const selectedFile = ref(null);
const importMessage = ref('');
const importSuccess = ref(false);
const fileInput = ref(null);

const settings = reactive({
    water_brewing_cost: 0.02,
    water_cleaning_cost: 0.01,
    electricity_cost: 5,
    co2_cost: 0.05,
    hourly_rate: 0,
    fuel_consumption: 0,
    fuel_cost: 0,
    include_fuel_in_costs: false,
    include_depreciation_in_costs: false,
    show_zero_stock_ingredients: false,
    show_existing_recipe_ingredients: false,
});

// Загрузка текущих настроек
onMounted(async () => {
    try {
        const response = await fetch('/api/v1/settings');
        if (response.ok) {
            const data = await response.json();
            // Обновляем только существующие ключи в settings
            Object.keys(settings).forEach(key => {
                if (data[key] !== undefined) {
                    // Преобразуем булевы значения
                    if (key.includes('include') || key.includes('show')) {
                        settings[key] = data[key] === 'true';
                    } else {
                        settings[key] = parseFloat(data[key]) || 0;
                    }
                }
            });
        }
    } catch (e) {
        console.error('Ошибка загрузки настроек', e);
    }
});

const saveSettings = async () => {
    loading.value = true;
    try {
        // Формируем данные в формате ключ-значение, который ожидает API
        const settingsData = {
            water_brewing_cost: String(settings.water_brewing_cost),
            water_cleaning_cost: String(settings.water_cleaning_cost),
            electricity_cost: String(settings.electricity_cost),
            co2_cost: String(settings.co2_cost),
            hourly_rate: String(settings.hourly_rate),
            fuel_consumption: String(settings.fuel_consumption),
            fuel_cost: String(settings.fuel_cost),
            include_fuel_in_costs: String(settings.include_fuel_in_costs),
            include_depreciation_in_costs: String(settings.include_depreciation_in_costs),
            show_zero_stock_ingredients: String(settings.show_zero_stock_ingredients),
            show_existing_recipe_ingredients: String(settings.show_existing_recipe_ingredients),
        };

        const response = await fetch('/api/v1/settings', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(settingsData)
        });

        if (response.ok) {
            alert('Настройки сохранены!');
        } else {
            alert('Ошибка сохранения');
        }
    } catch (e) {
        console.error(e);
        alert('Ошибка сети');
    } finally {
        loading.value = false;
    }
};

const handleFileSelect = (event) => {
    selectedFile.value = event.target.files[0];
    importMessage.value = '';
};

const uploadFile = async () => {
    if (!selectedFile.value) return;

    uploading.value = true;
    importMessage.value = '';
    
    const formData = new FormData();
    formData.append('import_file', selectedFile.value);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    try {
        const response = await fetch('/settings/import', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (response.ok) {
            importSuccess.value = true;
            importMessage.value = `Успешно! Импортировано записей: ${result.imported}`;
            setTimeout(() => window.location.reload(), 2000);
        } else {
            importSuccess.value = false;
            importMessage.value = result.message || 'Ошибка импорта';
        }
    } catch (e) {
        console.error(e);
        importSuccess.value = false;
        importMessage.value = 'Ошибка соединения с сервером';
    } finally {
        uploading.value = false;
    }
};
</script>

<style scoped>
.settings-container {
    max-width: 800px;
    margin: 0 auto;
}
.card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}
.form-input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.checkbox-group {
    margin-bottom: 20px;
}
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    cursor: pointer;
}
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}
.btn-primary { background: #3b82f6; color: white; }
.btn-success { background: #10b981; color: white; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.import-area {
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: flex-start;
}
.file-input {
    width: 100%;
    padding: 10px;
    border: 1px dashed #ccc;
    border-radius: 4px;
}
.message {
    margin-top: 10px;
    padding: 10px;
    border-radius: 4px;
    width: 100%;
}
.message-success { background: #d1fae5; color: #065f46; }
.message-error { background: #fee2e2; color: #991b1b; }
.text-muted { color: #666; margin-bottom: 15px; }
</style>
