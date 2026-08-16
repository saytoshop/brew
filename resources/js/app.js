import { createApp, ref, reactive, computed, onMounted } from 'vue';

console.log('Hello from Vite!');

// Экспортируем Vue глобально для использования в blade-шаблонах
if (typeof window !== 'undefined') {
    window.Vue = { createApp, ref, reactive, computed, onMounted };
}