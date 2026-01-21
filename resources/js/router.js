import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

// Layouts
import AuthLayout from '@/components/layout/AuthLayout.vue';
import AppLayout from '@/components/layout/AppLayout.vue';

// Pages
import Login from '@/pages/Login.vue';
import TwoFactor from '@/pages/TwoFactor.vue';
import Dashboard from '@/pages/Dashboard.vue';
import Domains from '@/pages/Domains.vue';
import DomainImport from '@/pages/DomainImport.vue';
import Sites from '@/pages/Sites.vue';
import SiteCreate from '@/pages/SiteCreate.vue';
import SiteEdit from '@/pages/SiteEdit.vue';
import Templates from '@/pages/Templates.vue';
import Backlinks from '@/pages/Backlinks.vue';
import Posts from '@/pages/Posts.vue';
import DnsAccounts from '@/pages/DnsAccounts.vue';
import Servers from '@/pages/Servers.vue';
import Settings from '@/pages/Settings.vue';
import SettingsAi from '@/pages/SettingsAi.vue';
import SettingsKeitaro from '@/pages/SettingsKeitaro.vue';
import SettingsAnalytics from '@/pages/SettingsAnalytics.vue';
import Profile from '@/pages/Profile.vue';

const routes = [
    // Auth routes
    {
        path: '/login',
        component: AuthLayout,
        children: [
            {
                path: '',
                name: 'login',
                component: Login,
                meta: { guest: true },
            },
            {
                path: '/2fa',
                name: '2fa',
                component: TwoFactor,
                meta: { guest: true },
            },
        ],
    },

    // App routes (authenticated)
    {
        path: '/',
        component: AppLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: Dashboard,
            },
            {
                path: 'domains',
                name: 'domains',
                component: Domains,
            },
            {
                path: 'domains/import',
                name: 'domains.import',
                component: DomainImport,
            },
            {
                path: 'sites',
                name: 'sites',
                component: Sites,
            },
            {
                path: 'sites/create',
                name: 'sites.create',
                component: SiteCreate,
            },
            {
                path: 'sites/:id/edit',
                name: 'sites.edit',
                component: SiteEdit,
                props: true,
            },
            {
                path: 'templates',
                name: 'templates',
                component: Templates,
            },
            {
                path: 'backlinks',
                name: 'backlinks',
                component: Backlinks,
            },
            {
                path: 'posts',
                name: 'posts',
                component: Posts,
            },
            {
                path: 'dns-accounts',
                name: 'dns-accounts',
                component: DnsAccounts,
            },
            {
                path: 'servers',
                name: 'servers',
                component: Servers,
            },
            {
                path: 'settings',
                name: 'settings',
                component: Settings,
            },
            {
                path: 'settings/ai',
                name: 'settings.ai',
                component: SettingsAi,
            },
            {
                path: 'settings/keitaro',
                name: 'settings.keitaro',
                component: SettingsKeitaro,
            },
            {
                path: 'settings/analytics',
                name: 'settings.analytics',
                component: SettingsAnalytics,
            },
            {
                path: 'profile',
                name: 'profile',
                component: Profile,
            },
        ],
    },

    // 404
    {
        path: '/:pathMatch(.*)*',
        redirect: '/',
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation guard
router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();
    const isAuthenticated = authStore.isAuthenticated;

    if (to.meta.requiresAuth && !isAuthenticated) {
        next({ name: 'login' });
    } else if (to.meta.guest && isAuthenticated) {
        next({ name: 'dashboard' });
    } else {
        next();
    }
});

export default router;
