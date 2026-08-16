@extends('layouts.app')

@section('title', 'Настройки')
@section('content')
<div class="stack" id="settings-app" v-cloak>
    <div class="page-head">
        <div>
            <h1>Настройки</h1>
            <p class="page-sub">Параметры расчёта себестоимости и работа с данными</p>
        </div>
    </div>

    <div class="card card-pad">
        <div class="section-title">💰 Коммунальные тарифы</div>
        <div class="settings-grid" style="margin-top: 12px;">
            <div class="field">
                <label>Стоимость 1 кВт·ч (₽)</label>
                <input class="input" type="number" step="0.01" v-model="settings.energy_cost_per_kwh">
            </div>
            <div class="field">
                <label>Мощность нагревателя (кВт)</label>
                <input class="input" type="number" step="0.1" v-model="settings.heater_power_kw">
            </div>
            <div class="field">
                <label>Стоимость 1 л питьевой воды (₽)</label>
                <input class="input" type="number" step="0.01" v-model="settings.water_drinking_cost">
            </div>
            <div class="field">
                <label>Стоимость 1 л технической воды (₽)</label>
                <input class="input" type="number" step="0.01" v-model="settings.water_technical_cost">
            </div>
            <div class="field">
                <label>Расход воды чиллером (л/мин)</label>
                <input class="input" type="number" step="0.1" v-model="settings.chiller_flow_l_per_min">
            </div>
            <div class="field">
                <label>Время кипячения (мин)</label>
                <input class="input" type="number" v-model="settings.boil_time_minutes">
            </div>
            <div class="field">
                <label>Воды на 1 л пива (л/л)</label>
                <input class="input" type="number" step="0.1" v-model="settings.water_per_liter_ratio">
            </div>
        </div>
        <button class="btn btn-primary" @click="saveSettings" :disabled="saving">💾 Сохранить настройки</button>
    </div>

    <div class="card card-pad">
        <div class="section-title">📁 Импорт / Экспорт</div>
        <p class="hint" style="margin-bottom: 12px;">Управление базой данных: экспорт и импорт SQLite файлов.</p>
        <div class="actions-row">
            <button class="btn btn-outline" @click="exportDb" :disabled="exporting">📤 Экспорт текущей БД</button>
            <button class="btn btn-outline" @click="showImportModal = true">📥 Импорт БД из файла</button>
        </div>
    </div>

    <!-- Модалка импорта -->
    <div class="modal-overlay" v-if="showImportModal" @click.self="showImportModal = false" v-cloak>
        <div class="modal">
            <div class="modal-head">
                <div class="modal-title">Импорт базы данных</div>
                <button class="modal-close" @click="showImportModal = false">✕</button>
            </div>
            <div class="modal-body">
                <p class="hint" style="margin-bottom: 12px;">Выберите файл backup_*.sqlite для импорта. Внимание: все текущие данные будут удалены!</p>
                <div class="field">
                    <label>Файл БД</label>
                    <input type="file" ref="importFile" accept=".sqlite" class="input">
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn btn-outline" @click="showImportModal = false">Отмена</button>
                <button class="btn btn-primary" @click="importDb" :disabled="importing">Импортировать</button>
            </div>
        </div>
    </div>
</div>

<script>
const { createApp, ref, reactive, onMounted } = Vue;

createApp({
    setup() {
        const settings = reactive({
            energy_cost_per_kwh: '',
            heater_power_kw: '',
            water_drinking_cost: '',
            water_technical_cost: '',
            chiller_flow_l_per_min: '',
            boil_time_minutes: '',
            water_per_liter_ratio: '',
        });
        const saving = ref(false);
        const exporting = ref(false);
        const showImportModal = ref(false);
        const importing = ref(false);

        const loadSettings = async () => {
            try {
                const res = await fetch('/api/v1/settings');
                if (res.ok) {
                    const data = await res.json();
                    for (const key in settings) {
                        const setting = data.find(s => s.key === key);
                        if (setting) settings[key] = setting.value;
                    }
                }
            } catch (e) { console.error(e); }
        };

        const saveSettings = async () => {
            saving.value = true;
            try {
                const payload = Object.entries(settings).map(([key, value]) => ({ key, value }));
                const res = await fetch('/api/v1/settings', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify(payload)
                });
                if (res.ok) alert('Настройки сохранены');
            } catch (e) { console.error(e); }
            finally { saving.value = false; }
        };

        const exportDb = async () => {
            exporting.value = true;
            try {
                const res = await fetch('/api/v1/settings/export-db', { method: 'POST' });
                if (res.ok) {
                    const blob = await res.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'backup_' + new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-') + '.sqlite';
                    a.click();
                }
            } catch (e) { console.error(e); }
            finally { exporting.value = false; }
        };

        const importDb = async () => {
            importing.value = true;
            try {
                const file = $refs.importFile.files[0];
                if (!file) {
                    alert('Выберите файл для импорта');
                    importing.value = false;
                    return;
                }

                const formData = new FormData();
                formData.append('file', file);

                const res = await fetch('/api/v1/settings/import-db', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: formData
                });

                const data = await res.json();
                if (res.ok) {
                    alert(data.message || 'Импорт выполнен успешно');
                    showImportModal.value = false;
                    // Перезагружаем страницу для обновления данных
                    window.location.reload();
                } else {
                    alert(data.error || 'Ошибка импорта');
                }
            } catch (e) {
                console.error(e);
                alert('Ошибка импорта: ' + e.message);
            }
            finally { importing.value = false; }
        };

        onMounted(loadSettings);

        return { settings, saving, exporting, showImportModal, importing, saveSettings, exportDb, importDb };
    }
}).mount('#settings-app');
</script>
@endsection
