import { createRouter, createWebHashHistory } from 'vue-router';
import Settings from './pages/Settings.vue';
import Pricing from './pages/Pricing.vue';

// Create router
const router = createRouter({
    history: createWebHashHistory(),
    routes: [
        { path: '/', component: Pricing },
        { path: '/settings', component: Settings },
    ]
});

export default router;