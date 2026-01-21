<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-dark-900">Домены</h1>
                <p class="text-dark-500 mt-1">Управление доменами и DNS</p>
            </div>
            <div class="flex gap-2">
                <router-link to="/domains/import" class="btn-primary">
                    <PlusIcon class="w-4 h-4 mr-2" />
                    Импорт доменов
                </router-link>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="card card-body text-center">
                <p class="text-2xl font-semibold text-dark-900">{{ stats?.total || 0 }}</p>
                <p class="text-sm text-dark-500">Всего</p>
            </div>
            <div class="card card-body text-center">
                <p class="text-2xl font-semibold text-green-600">{{ stats?.by_status?.active || 0 }}</p>
                <p class="text-sm text-dark-500">Активных</p>
            </div>
            <div class="card card-body text-center">
                <p class="text-2xl font-semibold text-yellow-600">{{ stats?.by_status?.dns_configuring || 0 }}</p>
                <p class="text-sm text-dark-500">Ожидают NS</p>
            </div>
            <div class="card card-body text-center">
                <p class="text-2xl font-semibold text-blue-600">{{ stats?.available || 0 }}</p>
                <p class="text-sm text-dark-500">Свободных</p>
            </div>
            <div class="card card-body text-center">
                <p class="text-2xl font-semibold text-red-600">{{ stats?.with_errors || 0 }}</p>
                <p class="text-sm text-dark-500">С ошибками</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="card card-body">
            <div class="flex flex-col gap-4">
                <!-- Row 1: Search and main filters -->
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <input v-model="filters.search" type="text" placeholder="Поиск по домену..."
                            class="input" @input="debouncedFetch" />
                    </div>
                    <select v-model="filters.status" class="input w-full md:w-40" @change="fetchDomains">
                        <option value="">Все статусы</option>
                        <option value="active">Активные</option>
                        <option value="dns_configuring">Ожидают NS</option>
                        <option value="ssl_pending">Ожидают SSL</option>
                        <option value="error">С ошибками</option>
                    </select>
                    <select v-model="filters.dns_account_id" class="input w-full md:w-48" @change="fetchDomains">
                        <option value="">Все DNS аккаунты</option>
                        <option v-for="acc in filterOptions.dns_accounts" :key="acc.id" :value="acc.id">
                            {{ acc.name }} ({{ acc.domains_count }})
                        </option>
                    </select>
                </div>
                
                <!-- Row 2: NS filter and checkboxes -->
                <div class="flex flex-col md:flex-row gap-4 items-center">
                    <select v-model="filters.nameservers" class="input w-full md:w-64" @change="fetchDomains">
                        <option value="">Все NS серверы</option>
                        <option v-for="(ns, i) in filterOptions.ns_servers" :key="i" :value="ns.join(',')">
                            {{ ns[0] }} ({{ ns.length }} NS)
                        </option>
                    </select>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="filters.available_only" @change="fetchDomains"
                            class="w-4 h-4 rounded border-dark-300 text-primary-600 focus:ring-primary-500" />
                        <span class="text-sm text-dark-700">Только свободные</span>
                    </label>
                    <button v-if="hasActiveFilters" @click="clearFilters" class="text-sm text-primary-600 hover:text-primary-700">
                        Сбросить фильтры
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" v-model="selectAll" @change="toggleSelectAll"
                                    class="w-4 h-4 rounded border-dark-300 text-primary-600 focus:ring-primary-500" />
                            </th>
                            <th>Домен</th>
                            <th>Статус</th>
                            <th>NS серверы</th>
                            <th>DNS</th>
                            <th>Сайт</th>
                            <th>DR</th>
                            <th>Дата</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="9" class="text-center py-8">
                                <LoadingSpinner />
                            </td>
                        </tr>
                        <tr v-else-if="!domains.length">
                            <td colspan="9" class="text-center py-8 text-dark-500">
                                Нет доменов
                            </td>
                        </tr>
                        <tr v-for="domain in domains" :key="domain.id">
                            <td>
                                <input type="checkbox" v-model="selected" :value="domain.id"
                                    class="w-4 h-4 rounded border-dark-300 text-primary-600 focus:ring-primary-500" />
                            </td>
                            <td>
                                <div class="font-medium text-dark-900">{{ domain.domain }}</div>
                                <div v-if="domain.error_message" class="text-xs text-red-600 truncate max-w-xs">
                                    {{ domain.error_message }}
                                </div>
                            </td>
                            <td>
                                <span :class="getStatusClass(domain.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    {{ getStatusText(domain.status) }}
                                </span>
                            </td>
                            <td>
                                <div v-if="domain.nameservers?.length" class="text-xs text-dark-600 space-y-0.5">
                                    <div v-for="ns in domain.nameservers" :key="ns" class="font-mono">{{ ns }}</div>
                                </div>
                                <span v-else class="text-dark-400 text-sm">—</span>
                            </td>
                            <td>
                                <span class="text-sm text-dark-600">{{ domain.dns_account?.name || '—' }}</span>
                            </td>
                            <td>
                                <router-link v-if="domain.site" :to="`/sites/${domain.site.id}/edit`"
                                    class="text-primary-600 hover:text-primary-700 text-sm">
                                    {{ domain.site.title }}
                                </router-link>
                                <span v-else class="text-dark-400 text-sm">—</span>
                            </td>
                            <td>
                                <span class="text-sm">{{ domain.dr_rating || '—' }}</span>
                            </td>
                            <td>
                                <span class="text-sm text-dark-500">{{ formatDate(domain.created_at) }}</span>
                            </td>
                            <td>
                                <div class="flex items-center gap-1">
                                    <button @click="recheckSingleDomain(domain)" 
                                        :disabled="recheckingDomains[domain.id]"
                                        class="p-1 text-dark-400 hover:text-primary-600 rounded" 
                                        title="Проверить статус">
                                        <ArrowPathIcon :class="['w-4 h-4', recheckingDomains[domain.id] ? 'animate-spin' : '']" />
                                    </button>
                                    <button 
                                        v-if="domain.status === 'ssl_pending' || (domain.status === 'dns_configuring' && domain.cloudflare_zone_id)"
                                        @click="setupSsl(domain)" 
                                        :disabled="settingSslDomains[domain.id]"
                                        class="p-1 text-dark-400 hover:text-green-600 rounded" 
                                        title="Настроить SSL"
                                    >
                                        <ShieldCheckIcon :class="['w-4 h-4', settingSslDomains[domain.id] ? 'animate-spin' : '']" />
                                    </button>
                                    <button @click="deleteDomain(domain)" class="p-1 text-dark-400 hover:text-red-600 rounded" title="Удалить">
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="meta.total > meta.per_page" class="px-6 py-4 border-t border-dark-100 flex items-center justify-between">
                <p class="text-sm text-dark-500">
                    Показано {{ (meta.current_page - 1) * meta.per_page + 1 }} - 
                    {{ Math.min(meta.current_page * meta.per_page, meta.total) }} из {{ meta.total }}
                </p>
                <div class="flex gap-2">
                    <button @click="prevPage" :disabled="meta.current_page === 1" class="btn-secondary btn-sm">
                        Назад
                    </button>
                    <button @click="nextPage" :disabled="meta.current_page === meta.last_page" class="btn-secondary btn-sm">
                        Вперёд
                    </button>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div v-if="selected.length" class="fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-dark-900 text-white rounded-lg shadow-lg px-4 py-3 flex items-center gap-3">
            <span class="text-sm">Выбрано: {{ selected.length }}</span>
            <div class="w-px h-6 bg-dark-700"></div>
            <button @click="bulkRecheckStatus" :disabled="bulkRechecking" class="btn-sm bg-primary-600 hover:bg-primary-700 text-white rounded flex items-center gap-1">
                <ArrowPathIcon :class="['w-4 h-4', bulkRechecking ? 'animate-spin' : '']" />
                Проверить NS
            </button>
            <button @click="bulkSetupSsl" :disabled="bulkSettingSsl" class="btn-sm bg-green-600 hover:bg-green-700 text-white rounded flex items-center gap-1">
                <ShieldCheckIcon :class="['w-4 h-4', bulkSettingSsl ? 'animate-spin' : '']" />
                Настроить SSL
            </button>
            <button @click="copySelectedDomains" class="btn-sm bg-dark-700 hover:bg-dark-600 text-white rounded flex items-center gap-1">
                <ClipboardIcon class="w-4 h-4" />
                Копировать
            </button>
            <button @click="downloadSelectedDomains" class="btn-sm bg-dark-700 hover:bg-dark-600 text-white rounded flex items-center gap-1">
                <ArrowDownTrayIcon class="w-4 h-4" />
                Скачать TXT
            </button>
            <button @click="bulkDelete" class="btn-sm bg-red-600 hover:bg-red-700 text-white rounded">
                Удалить
            </button>
            <button @click="selected = []" class="text-dark-400 hover:text-white ml-2">
                <XMarkIcon class="w-5 h-5" />
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { domainsApi } from '@/services/api';
import { useToast } from 'vue-toastification';
import { useDebounceFn } from '@vueuse/core';
import LoadingSpinner from '@/components/common/LoadingSpinner.vue';
import { PlusIcon, XMarkIcon, ArrowPathIcon, TrashIcon, ClipboardIcon, ArrowDownTrayIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';

const toast = useToast();

const loading = ref(false);
const domains = ref([]);
const stats = ref(null);
const filterOptions = ref({ ns_servers: [], dns_accounts: [] });
const selected = ref([]);
const selectAll = ref(false);
const bulkRechecking = ref(false);
const bulkSettingSsl = ref(false);
const recheckingDomains = ref({});
const settingSslDomains = ref({});

const filters = reactive({ 
    search: '', 
    status: '', 
    dns_account_id: '',
    nameservers: '',
    available_only: false, 
    page: 1 
});
const meta = reactive({ current_page: 1, last_page: 1, per_page: 25, total: 0 });

const statusMap = {
    'pending': { text: 'Ожидает', class: 'bg-gray-100 text-gray-800' },
    'dns_configuring': { text: 'Ожидает NS', class: 'bg-yellow-100 text-yellow-800' },
    'ssl_pending': { text: 'Ожидает SSL', class: 'bg-blue-100 text-blue-800' },
    'active': { text: 'Активен', class: 'bg-green-100 text-green-800' },
    'error': { text: 'Ошибка', class: 'bg-red-100 text-red-800' },
    'suspended': { text: 'Приостановлен', class: 'bg-gray-100 text-gray-800' },
};

const hasActiveFilters = computed(() => {
    return filters.search || filters.status || filters.dns_account_id || filters.nameservers || filters.available_only;
});

const getStatusText = (status) => statusMap[status]?.text || status;
const getStatusClass = (status) => statusMap[status]?.class || 'bg-gray-100 text-gray-800';

const fetchDomains = async () => {
    loading.value = true;
    try {
        const params = { 
            page: filters.page, 
            search: filters.search || undefined, 
            status: filters.status || undefined,
            dns_account_id: filters.dns_account_id || undefined,
            nameservers: filters.nameservers || undefined,
            available_only: filters.available_only || undefined 
        };
        const response = await domainsApi.list(params);
        domains.value = response.data.data;
        Object.assign(meta, response.data.meta);
    } catch (error) { toast.error('Ошибка загрузки доменов'); }
    finally { loading.value = false; }
};

const fetchStats = async () => {
    try { const response = await domainsApi.stats(); stats.value = response.data.data; }
    catch (error) { console.error('Error fetching stats:', error); }
};

const fetchFilterOptions = async () => {
    try { 
        const response = await domainsApi.filterOptions(); 
        filterOptions.value = response.data.data; 
    }
    catch (error) { console.error('Error fetching filter options:', error); }
};

const clearFilters = () => {
    filters.search = '';
    filters.status = '';
    filters.dns_account_id = '';
    filters.nameservers = '';
    filters.available_only = false;
    filters.page = 1;
    fetchDomains();
};

const debouncedFetch = useDebounceFn(() => { filters.page = 1; fetchDomains(); }, 300);

const toggleSelectAll = () => {
    if (selectAll.value) { selected.value = domains.value.map(d => d.id); }
    else { selected.value = []; }
};

const prevPage = () => { if (filters.page > 1) { filters.page--; fetchDomains(); } };
const nextPage = () => { if (filters.page < meta.last_page) { filters.page++; fetchDomains(); } };

const recheckSingleDomain = async (domain) => {
    recheckingDomains.value[domain.id] = true;
    try {
        const response = await domainsApi.recheckStatus(domain.id);
        const data = response.data.data;
        const index = domains.value.findIndex(d => d.id === domain.id);
        if (index !== -1) { domains.value[index] = data.domain; }
        if (data.provider_status === 'active') { toast.success(`${domain.domain}: Active`); }
        else { toast.info(`${domain.domain}: ${data.provider_status || 'проверен'}`); }
        fetchStats();
    } catch (error) { toast.error('Ошибка проверки статуса'); }
    finally { recheckingDomains.value[domain.id] = false; }
};

const setupSsl = async (domain) => {
    settingSslDomains.value[domain.id] = true;
    try {
        const response = await domainsApi.setupSsl(domain.id);
        const data = response.data.data;
        const index = domains.value.findIndex(d => d.id === domain.id);
        if (index !== -1) {
            domains.value[index].status = data.status;
            domains.value[index].ssl_status = data.ssl_status;
        }
        toast.success(data.message || 'SSL настроен');
        fetchStats();
    } catch (error) { 
        toast.error(error.response?.data?.message || 'Ошибка настройки SSL'); 
    }
    finally { settingSslDomains.value[domain.id] = false; }
};

const bulkRecheckStatus = async () => {
    if (selected.value.length === 0) return;
    bulkRechecking.value = true;
    try {
        const response = await domainsApi.bulkRecheckStatus(selected.value);
        const data = response.data.data;
        toast.success(`Проверено: ${data.summary.checked}, ошибок: ${data.summary.failed}`);
        fetchDomains();
        fetchStats();
    } catch (error) { toast.error('Ошибка проверки статуса'); }
    finally { bulkRechecking.value = false; }
};

const bulkSetupSsl = async () => {
    if (selected.value.length === 0) return;
    bulkSettingSsl.value = true;
    try {
        const response = await domainsApi.bulkSetupSsl(selected.value);
        const data = response.data.data;
        const msg = `SSL настроен: ${data.summary.configured}, ошибок: ${data.summary.failed}`;
        if (data.summary.failed > 0) {
            toast.warning(msg);
        } else {
            toast.success(msg);
        }
        fetchDomains();
        fetchStats();
    } catch (error) { 
        toast.error(error.response?.data?.message || 'Ошибка настройки SSL'); 
    }
    finally { bulkSettingSsl.value = false; }
};

const bulkDelete = async () => {
    if (!confirm(`Удалить ${selected.value.length} доменов?`)) return;
    try {
        await domainsApi.bulkDelete(selected.value);
        toast.success('Домены удалены');
        selected.value = [];
        fetchDomains();
        fetchStats();
        fetchFilterOptions();
    } catch (error) { toast.error('Ошибка удаления'); }
};

const deleteDomain = async (domain) => {
    if (!confirm(`Удалить домен ${domain.domain}?`)) return;
    try {
        await domainsApi.delete(domain.id);
        toast.success('Домен удалён');
        fetchDomains();
        fetchStats();
        fetchFilterOptions();
    } catch (error) { toast.error('Ошибка удаления'); }
};

const copyToClipboard = (text) => {
    // Fallback для HTTP или старых браузеров
    if (!navigator.clipboard) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        return;
    }
    navigator.clipboard.writeText(text);
};

const copySelectedDomains = () => {
    const selectedDomains = domains.value
        .filter(d => selected.value.includes(d.id))
        .map(d => d.domain);
    copyToClipboard(selectedDomains.join('\n'));
    toast.success(`${selectedDomains.length} доменов скопировано`);
};

const downloadSelectedDomains = () => {
    const selectedDomains = domains.value
        .filter(d => selected.value.includes(d.id))
        .map(d => d.domain);
    
    const blob = new Blob([selectedDomains.join('\n')], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `domains-${dayjs().format('YYYY-MM-DD-HHmm')}.txt`;
    a.click();
    URL.revokeObjectURL(url);
    toast.success(`${selectedDomains.length} доменов скачано`);
};

const formatDate = (date) => dayjs(date).format('DD.MM.YYYY');

onMounted(() => { 
    fetchDomains(); 
    fetchStats();
    fetchFilterOptions();
});
</script>
