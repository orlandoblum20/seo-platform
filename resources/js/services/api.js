import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import router from '@/router';

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Request interceptor - add auth token
api.interceptors.request.use(
    (config) => {
        const authStore = useAuthStore();
        if (authStore.token) {
            config.headers.Authorization = `Bearer ${authStore.token}`;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

// Response interceptor - handle errors
api.interceptors.response.use(
    (response) => response,
    (error) => {
        const authStore = useAuthStore();

        if (error.response?.status === 401) {
            authStore.logout();
            router.push({ name: 'login' });
        }

        return Promise.reject(error);
    }
);

export default api;

// API methods
export const authApi = {
    login: (email, password) => api.post('/auth/login', { email, password }),
    verify2fa: (code) => api.post('/auth/verify-2fa', { code }),
    logout: () => api.post('/auth/logout'),
    me: () => api.get('/auth/me'),
    setup2fa: () => api.post('/auth/2fa/setup'),
    enable2fa: (code) => api.post('/auth/2fa/enable', { code }),
    disable2fa: (code, password) => api.post('/auth/2fa/disable', { code, password }),
    changePassword: (data) => api.post('/auth/change-password', data),
    loginHistory: () => api.get('/auth/login-history'),
};

export const dashboardApi = {
    index: () => api.get('/dashboard'),
    stats: (period = '7d') => api.get('/dashboard/stats', { params: { period } }),
    queueStatus: () => api.get('/queue-status'),
};

export const domainsApi = {
    list: (params) => api.get('/domains', { params }),
    get: (id) => api.get(`/domains/${id}`),
    update: (id, data) => api.put(`/domains/${id}`, data),
    delete: (id) => api.delete(`/domains/${id}`),
    import: (data) => api.post('/domains/import', data),
    bulkDelete: (ids) => api.post('/domains/bulk-delete', { domain_ids: ids }),
    bulkRecheckStatus: (ids) => api.post('/domains/bulk-recheck-status', { domain_ids: ids }),
    bulkSetupSsl: (ids) => api.post('/domains/bulk-setup-ssl', { domain_ids: ids }),
    recheckStatus: (id) => api.post(`/domains/${id}/recheck-status`),
    updateIp: (ids, ip) => api.post('/domains/update-ip', { domain_ids: ids, ip_address: ip }),
    checkSsl: (id) => api.get(`/domains/${id}/check-ssl`),
    getSslDetails: (id) => api.get(`/domains/${id}/ssl-details`),
    setupSsl: (id) => api.post(`/domains/${id}/setup-ssl`),
    stats: () => api.get('/domains-stats'),
    filterOptions: () => api.get('/domains/filter-options'),
    export: (params) => api.post('/domains/export', params),
};

export const sitesApi = {
    list: (params) => api.get('/sites', { params }),
    get: (id) => api.get(`/sites/${id}`),
    create: (data) => api.post('/sites', data),
    update: (id, data) => api.put(`/sites/${id}`, data),
    delete: (id) => api.delete(`/sites/${id}`),
    generate: (id) => api.post(`/sites/${id}/generate`),
    regenerateSection: (id, page, section) => api.post(`/sites/${id}/regenerate-section`, { page, section }),
    publish: (id) => api.post(`/sites/${id}/publish`),
    unpublish: (id) => api.post(`/sites/${id}/unpublish`),
    bulkCreate: (sites) => api.post('/sites/bulk-create', { sites }),
    bulkPublish: (ids) => api.post('/sites/bulk-publish', { site_ids: ids }),
    bulkUnpublish: (ids) => api.post('/sites/bulk-unpublish', { site_ids: ids }),
    preview: (id) => api.get(`/sites/${id}/preview`),
    stats: () => api.get('/sites-stats'),
    getAutopostSettings: (id) => api.get(`/sites/${id}/autopost-settings`),
    updateAutopostSettings: (id, data) => api.put(`/sites/${id}/autopost-settings`, data),
};

export const templatesApi = {
    list: (params) => api.get('/templates', { params }),
    get: (id) => api.get(`/templates/${id}`),
    create: (data) => api.post('/templates', data),
    update: (id, data) => api.put(`/templates/${id}`, data),
    delete: (id) => api.delete(`/templates/${id}`),
    duplicate: (id) => api.post(`/templates/${id}/duplicate`),
    reorder: (order) => api.post('/templates/reorder', { order }),
};

export const backlinksApi = {
    list: (params) => api.get('/backlinks', { params }),
    get: (id) => api.get(`/backlinks/${id}`),
    create: (data) => api.post('/backlinks', data),
    update: (id, data) => api.put(`/backlinks/${id}`, data),
    delete: (id) => api.delete(`/backlinks/${id}`),
    assignToSites: (data) => api.post('/backlinks/assign-to-sites', data),
    removeFromSites: (data) => api.post('/backlinks/remove-from-sites', data),
    randomizeAnchors: (data) => api.post('/backlinks/randomize-anchors', data),
};

export const postsApi = {
    list: (params) => api.get('/posts', { params }),
    get: (id) => api.get(`/posts/${id}`),
    create: (data) => api.post('/posts', data),
    update: (id, data) => api.put(`/posts/${id}`, data),
    delete: (id) => api.delete(`/posts/${id}`),
    publish: (id) => api.post(`/posts/${id}/publish`),
    bulkGenerate: (siteIds, type, schedule = true) => api.post('/posts/bulk-generate', { site_ids: siteIds, type, schedule }),
    bulkEnableAutopost: (data) => api.post('/autopost/bulk-enable', data),
    bulkDisableAutopost: (ids) => api.post('/autopost/bulk-disable', { site_ids: ids }),
    stats: () => api.get('/posts-stats'),
};

export const dnsAccountsApi = {
    list: (params) => api.get('/dns-accounts', { params }),
    get: (id) => api.get(`/dns-accounts/${id}`),
    create: (data) => api.post('/dns-accounts', data),
    update: (id, data) => api.put(`/dns-accounts/${id}`, data),
    delete: (id) => api.delete(`/dns-accounts/${id}`),
    verify: (id) => api.post(`/dns-accounts/${id}/verify`),
    sync: (id) => api.post(`/dns-accounts/${id}/sync`),
};

export const serversApi = {
    list: (params) => api.get('/servers', { params }),
    get: (id) => api.get(`/servers/${id}`),
    create: (data) => api.post('/servers', data),
    update: (id, data) => api.put(`/servers/${id}`, data),
    delete: (id) => api.delete(`/servers/${id}`),
    healthCheck: (id) => api.post(`/servers/${id}/health-check`),
    setPrimary: (id) => api.post(`/servers/${id}/set-primary`),
};

export const settingsApi = {
    index: () => api.get('/settings'),
    update: (settings) => api.put('/settings', { settings }),
    getAi: () => api.get('/settings/ai'),
    createAi: (data) => api.post('/settings/ai', data),
    updateAi: (id, data) => api.put(`/settings/ai/${id}`, data),
    deleteAi: (id) => api.delete(`/settings/ai/${id}`),
    testAi: (id) => api.post(`/settings/ai/${id}/test`),
    setDefaultAi: (id) => api.post(`/settings/ai/${id}/set-default`),
    getKeitaro: () => api.get('/settings/keitaro'),
    updateKeitaro: (data) => api.put('/settings/keitaro', data),
    getAnalytics: () => api.get('/settings/analytics'),
    updateAnalytics: (data) => api.put('/settings/analytics', data),
};
