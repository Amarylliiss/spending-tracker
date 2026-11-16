import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/login', component: () => import('./pages/LoginPage.vue'), meta: { guest: true } },
        { path: '/register', component: () => import('./pages/RegisterPage.vue'), meta: { guest: true } },
        { path: '/', component: () => import('./pages/DashboardPage.vue') },
        { path: '/expenses', component: () => import('./pages/ExpensesPage.vue') },
        { path: '/rules', component: () => import('./pages/RulesPage.vue') },
        { path: '/categories', component: () => import('./pages/CategoriesPage.vue') },
        { path: '/:pathMatch(.*)*', redirect: '/' },
    ],
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();
    await auth.bootstrap();

    if (to.meta.guest && auth.user) {
        return '/';
    }

    if (!to.meta.guest && !auth.user) {
        return '/login';
    }
});

export default router;
