<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-dark-900">Дашборд</h1>
                <p class="text-dark-500 mt-1">Обзор системы и статистика</p>
            </div>
            <div class="flex gap-2">
                <button @click="refresh" :disabled="loading" class="btn-secondary">
                    <ArrowPathIcon class="w-4 h-4 mr-2" :class="{ 'animate-spin': loading }" />
                    Обновить
                </button>
            </div>
        </div>

        <!-- Alerts -->
        <div v-if="data?.alerts?.length" class="space-y-2">
            <div v-for="(alert, index) in data.alerts" :key="index"
                :class="[
                    'flex items-center gap-3 px-4 py-3 rounded-lg',
                    alert.type === 'error' ? 'bg-red-50 border border-red-200' : '',
                    alert.type === 'warning' ? 'bg-yellow-50 border border-yellow-200' : '',
                    alert.type === 'info' ? 'bg-blue-50 border border-blue-200' : '',
                ]">
                <ExclamationTriangleIcon v-if="alert.type === 'error'" class="w-5 h-5 text-red-600" />
                <ExclamationTriangleIcon v-else-if="alert.type === 'warning'" class="w-5 h-5 text-yellow-600" />
                <InformationCircleIcon v-else class="w-5 h-5 text-blue-600" />
                <span class="flex-1 text-sm">{{ alert.message }}</span>
                <router-link v-if="alert.action" :to="alert.action" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                    Подробнее →
                </router-link>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <StatsCard title="Домены" :value="data?.stats?.domains?.total || 0"
                :subtitle="`${data?.stats?.domains?.active || 0} активных`" icon="GlobeAltIcon" color="blue" />
            <StatsCard title="Сайты" :value="data?.stats?.sites?.total || 0"
                :subtitle="`${data?.stats?.sites?.published || 0} опубликовано`" icon="DocumentDuplicateIcon" color="green" />
            <StatsCard title="Посты" :value="data?.stats?.posts?.total || 0"
                :subtitle="`${data?.stats?.posts?.today || 0} сегодня`" icon="DocumentTextIcon" color="purple" />
            <StatsCard title="Автопостинг" :value="data?.stats?.autopost?.enabled_sites || 0"
                :subtitle="`${data?.stats?.autopost?.next_24h || 0} в ближ. 24ч`" icon="ClockIcon" color="orange" />
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Sites -->
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h3 class="font-semibold text-dark-900">Последние сайты</h3>
                    <router-link to="/sites" class="text-sm text-primary-600 hover:text-primary-700">
                        Все сайты →
                    </router-link>
                </div>
                <div class="divide-y divide-dark-100">
                    <div v-if="!data?.recent_sites?.length" class="px-6 py-8 text-center text-dark-500">
                        Нет сайтов
                    </div>
                    <div v-for="site in data?.recent_sites" :key="site.id" class="px-6 py-4 flex items-center gap-4 hover:bg-dark-50">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-dark-900 truncate">{{ site.title }}</p>
                            <p class="text-sm text-dark-500 truncate">{{ site.domain?.domain }}</p>
                        </div>
                        <StatusBadge :status="site.status" />
                    </div>
                </div>
            </div>

            <!-- Recent Posts -->
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h3 class="font-semibold text-dark-900">Последние посты</h3>
                    <router-link to="/posts" class="text-sm text-primary-600 hover:text-primary-700">
                        Все посты →
                    </router-link>
                </div>
                <div class="divide-y divide-dark-100">
                    <div v-if="!data?.recent_posts?.length" class="px-6 py-8 text-center text-dark-500">
                        Нет постов
                    </div>
                    <div v-for="post in data?.recent_posts" :key="post.id" class="px-6 py-4 flex items-center gap-4 hover:bg-dark-50">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-dark-900 truncate">{{ post.title }}</p>
                            <p class="text-sm text-dark-500 truncate">{{ post.site?.domain?.domain }}</p>
                        </div>
                        <span class="badge-gray">{{ postTypes[post.type] || post.type }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Infrastructure Status -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-dark-900">Инфраструктура</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                            <CloudIcon class="w-6 h-6 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-sm text-dark-500">DNS аккаунты</p>
                            <p class="text-xl font-semibold text-dark-900">{{ data?.infrastructure?.dns_accounts || 0 }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                            <ServerIcon class="w-6 h-6 text-green-600" />
                        </div>
                        <div>
                            <p class="text-sm text-dark-500">Серверы</p>
                            <p class="text-xl font-semibold text-dark-900">{{ data?.infrastructure?.servers || 0 }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg" :class="primaryServerHealthClass">
                            <CheckCircleIcon v-if="primaryServerHealthy" class="w-6 h-6 text-green-600" />
                            <ExclamationTriangleIcon v-else class="w-6 h-6 text-yellow-600" />
                        </div>
                        <div>
                            <p class="text-sm text-dark-500">Основной сервер</p>
                            <p class="text-xl font-semibold text-dark-900">
                                {{ data?.infrastructure?.primary_server?.name || 'Не настроен' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { dashboardApi } from '@/services/api';
import { useToast } from 'vue-toastification';
import StatsCard from '@/components/dashboard/StatsCard.vue';
import StatusBadge from '@/components/common/StatusBadge.vue';
import {
    ArrowPathIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    CloudIcon,
    ServerIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline';

const toast = useToast();
const loading = ref(false);
const data = ref(null);

const postTypes = {
    article: 'Статья',
    news: 'Новость',
    announcement: 'Анонс',
    faq: 'FAQ',
};

const primaryServerHealthy = computed(() => {
    return data.value?.infrastructure?.primary_server?.health_status === 'ok';
});

const primaryServerHealthClass = computed(() => {
    return primaryServerHealthy.value ? 'bg-green-100' : 'bg-yellow-100';
});

const fetchData = async () => {
    loading.value = true;
    try {
        const response = await dashboardApi.index();
        data.value = response.data.data;
    } catch (error) {
        toast.error('Ошибка загрузки данных');
    } finally {
        loading.value = false;
    }
};

const refresh = () => {
    fetchData();
};

onMounted(() => {
    fetchData();
});
</script>
