import { createApp, ref, reactive, computed, onMounted } from 'vue';

const VueGlobal = {
    createApp,
    ref,
    reactive,
    computed,
    onMounted
};

if (typeof window !== 'undefined') {
    window.Vue = VueGlobal;
    console.log('Vue attached to window object', window.Vue);
}