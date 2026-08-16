import { createApp } from 'vue';
import SettingsPage from './components/SettingsPage.vue';

console.log('Hello from Vite!');

// Авто-монтирование компонентов на страницах
document.addEventListener('DOMContentLoaded', () => {
    // Монтируем компонент настроек если есть элемент с id="settings-app"
    const settingsEl = document.getElementById('settings-app');
    if (settingsEl) {
        createApp(SettingsPage).mount(settingsEl);
    }

    console.log('Vue app initialized');
});