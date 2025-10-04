import { createRouter, createWebHashHistory } from 'vue-router';
import Settings from './pages/Settings.vue';
import Pricing from './pages/Pricing.vue';
import Product from './pages/Product.vue';
import Documentation from './pages/Documentation.vue';

// Create router
const router = createRouter({
    history: createWebHashHistory(),
    routes: [
        { path: '/', component: Pricing },
        { path: '/settings', component: Settings },
        { path: '/product', component: Product },
        { path: '/documentation', component: Documentation }
    ]
});

export default router;