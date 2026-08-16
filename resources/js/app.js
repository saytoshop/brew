import { createApp } from 'vue';
import HomePage from './components/HomePage.vue';
import EquipmentPage from './components/EquipmentPage.vue';
import StockPage from './components/StockPage.vue';
import RecipesPage from './components/RecipesPage.vue';
import BrewsPage from './components/BrewsPage.vue';
import SettingsPage from './components/SettingsPage.vue';

console.log('Hello from Vite!');

// Авто-монтирование компонентов на страницах
document.addEventListener('DOMContentLoaded', () => {
    // Монтируем компонент главной если есть элемент с id="home-app"
    const homeEl = document.getElementById('home-app');
    if (homeEl) {
        createApp(HomePage).mount(homeEl);
    }

    // Монтируем компонент оборудования если есть элемент с id="equipment-app"
    const equipmentEl = document.getElementById('equipment-app');
    if (equipmentEl) {
        createApp(EquipmentPage).mount(equipmentEl);
    }

    // Монтируем компонент склада если есть элемент с id="stock-app"
    const stockEl = document.getElementById('stock-app');
    if (stockEl) {
        createApp(StockPage).mount(stockEl);
    }

    // Монтируем компонент рецептов если есть элемент с id="recipes-app"
    const recipesEl = document.getElementById('recipes-app');
    if (recipesEl) {
        createApp(RecipesPage).mount(recipesEl);
    }

    // Монтируем компонент варок если есть элемент с id="brews-app"
    const brewsEl = document.getElementById('brews-app');
    if (brewsEl) {
        createApp(BrewsPage).mount(brewsEl);
    }

    // Монтируем компонент настроек если есть элемент с id="settings-app"
    const settingsEl = document.getElementById('settings-app');
    if (settingsEl) {
        createApp(SettingsPage).mount(settingsEl);
    }

    console.log('Vue app initialized');
});