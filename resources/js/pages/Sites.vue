<template>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-dark-900">Сайты</h1>
                <p class="text-dark-500 mt-1">Управление сгенерированными сайтами</p>
            </div>
            <router-link to="/sites/create" class="btn-primary">
                <PlusIcon class="w-4 h-4 mr-2" />
                Создать сайт
            </router-link>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="card card-body text-center">
                <p class="text-2xl font-semibold text-dark-900">{{ stats?.total || 0 }}</p>
                <p class="text-sm text-dark-500">Всего</p>
            </div>
            <div class="card card-body text-center">
                <p class="text-2xl font-semibold text-green-600">{{ stats?.published || 0 }}</p>
                <p class="text-sm text-dark-500">Опубликовано</p>
            </div>
            <div class="card card-body text-center">
                <p class="text-2xl font-semibold text-blue-600">{{ stats?.by_status?.generating || 0 }}</p>
                <p class="text-sm text-dark-500">Генерируется</p>
            </div>
            <div class="card card-body text-center">
                <p class="text-2xl font-semibold text-purple-600">{{ stats?.with_autopost || 0 }}</p>
                <p class="text-sm text-dark-500">Автопостинг</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="card card-body">
            <div class="flex flex-col md:flex-row gap-4">
                <input v-model="filters.search" type="text" placeholder="Поиск..." class="input flex-1" @input="debouncedFetch" />
                <select v-model="filters.status" class="input w-full md:w-48" @change="fetchSites">
                    <option value="">Все статусы</option>
                    <option value="published">Опубликованные</option>
                    <option value="generated">Сгенерированные</option>
                    <option value="draft">Черновики</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="w-4 h-4 rounded" /></th>
                            <th>Сайт</th>
                            <th>Домен</th>
                            <th>Шаблон</th>
                            <th>Статус</th>
                            <th>Посты</th>
                            <th>Дата</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading"><td colspan="8" class="text-center py-8"><LoadingSpinner /></td></tr>
                        <tr v-else-if="!sites.length"><td colspan="8" class="text-center py-8 text-dark-500">Нет сайтов</td></tr>
                        <tr v-for="site in sites" :key="site.id">
                            <td><input type="checkbox" v-model="selected" :value="site.id" class="w-4 h-4 rounded" /></td>
                            <td>
                                <div class="font-medium text-dark-900">{{ site.title }}</div>
                                <div class="text-xs text-dark-500">{{ site.niche }}</div>
                            </td>
                            <td><span class="text-sm">{{ site.domain?.domain }}</span></td>
                            <td><span class="text-sm">{{ site.template?.name }}</span></td>
                            <td><StatusBadge :status="site.status" /></td>
                            <td><span class="text-sm">{{ site.posts_count || 0 }}</span></td>
                            <td><span class="text-sm text-dark-500">{{ formatDate(site.created_at) }}</span></td>
                            <td>
                                <div class="flex items-center gap-1">
                                    <router-link :to="`/sites/${site.id}/edit`" class="p-1 text-dark-400 hover:text-primary-600 rounded">
                                        <PencilIcon class="w-4 h-4" />
                                    </router-link>
                                    <button v-if="site.status === 'generated'" @click="publishSite(site)" class="p-1 text-dark-400 hover:text-green-600 rounded">
                                        <ArrowUpTrayIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div v-if="selected.length" class="fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-dark-900 text-white rounded-lg shadow-lg px-4 py-3 flex items-center gap-4">
            <span class="text-sm">Выбрано: {{ selected.length }}</span>
            <button @click="bulkPublish" class="btn-sm bg-green-600 hover:bg-green-700 text-white rounded">Опубликовать</button>
            <button @click="selected = []" class="text-dark-400 hover:text-white"><XMarkIcon class="w-5 h-5" /></button>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { sitesApi } from '@/services/api';
import { useToast } from 'vue-toastification';
import { useDebounceFn } from '@vueuse/core';
import StatusBadge from '@/components/common/StatusBadge.vue';
import LoadingSpinner from '@/components/common/LoadingSpinner.vue';
import { PlusIcon, PencilIcon, ArrowUpTrayIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';

const toast = useToast();
const loading = ref(false);
const sites = ref([]);
const stats = ref(null);
const selected = ref([]);
const selectAll = ref(false);
const filters = reactive({ search: '', status: '', page: 1 });

const fetchSites = async () => {
    loading.value = true;
    try {
        const response = await sitesApi.list(filters);
        sites.value = response.data.data;
    } catch (error) {
        toast.error('Ошибка загрузки');
    } finally {
        loading.value = false;
    }
};

const fetchStats = async () => {
    try {
        const response = await sitesApi.stats();
        stats.value = response.data.data;
    } catch (e) {}
};

const debouncedFetch = useDebounceFn(fetchSites, 300);
const toggleSelectAll = () => { selected.value = selectAll.value ? sites.value.map(s => s.id) : []; };
const formatDate = (date) => dayjs(date).format('DD.MM.YYYY');

const publishSite = async (site) => {
    try {
        await sitesApi.publish(site.id);
        toast.success('Публикация запущена');
        fetchSites();
    } catch (e) {
        toast.error('Ошибка публикации');
    }
};

const bulkPublish = async () => {
    try {
        await sitesApi.bulkPublish(selected.value);
        toast.success('Публикация запущена');
        selected.value = [];
        fetchSites();
    } catch (e) {
        toast.error('Ошибка');
    }
};

onMounted(() => { fetchSites(); fetchStats(); });
</script>
