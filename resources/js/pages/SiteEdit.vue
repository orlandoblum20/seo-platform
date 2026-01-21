<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <router-link to="/sites" class="text-dark-500 hover:text-dark-700">
                    <ArrowLeftIcon class="w-5 h-5" />
                </router-link>
                <div>
                    <h1 class="text-2xl font-bold text-dark-900">{{ site?.title || 'Загрузка...' }}</h1>
                    <p class="text-dark-500">{{ site?.domain?.domain }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button v-if="site?.status === 'generated'" @click="publishSite" class="btn-success">Опубликовать</button>
                <button v-if="site?.status === 'published'" @click="unpublishSite" class="btn-secondary">Снять с публикации</button>
                <button @click="regenerate" :disabled="!canRegenerate" class="btn-secondary">Перегенерировать</button>
            </div>
        </div>
        <div v-if="loading" class="text-center py-12"><LoadingSpinner /></div>
        <div v-else-if="site" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="card card-body"><h3 class="font-semibold mb-4">Контент</h3><pre class="text-sm bg-dark-50 p-4 rounded overflow-auto max-h-96">{{ JSON.stringify(site.content, null, 2) }}</pre></div>
            </div>
            <div class="space-y-6">
                <div class="card card-body"><h3 class="font-semibold mb-4">Статус</h3><StatusBadge :status="site.status" /></div>
                <div class="card card-body"><h3 class="font-semibold mb-4">Ключевые слова</h3><div class="flex flex-wrap gap-2"><span v-for="kw in site.keywords" :key="kw" class="badge-gray">{{ kw }}</span></div></div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { sitesApi } from '@/services/api';
import { useToast } from 'vue-toastification';
import StatusBadge from '@/components/common/StatusBadge.vue';
import LoadingSpinner from '@/components/common/LoadingSpinner.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const route = useRoute();
const toast = useToast();
const loading = ref(true);
const site = ref(null);
const canRegenerate = computed(() => ['draft', 'generated', 'error'].includes(site.value?.status));

const fetchSite = async () => {
    try {
        const response = await sitesApi.get(route.params.id);
        site.value = response.data.data;
    } catch (e) { toast.error('Ошибка загрузки'); } finally { loading.value = false; }
};
const publishSite = async () => { await sitesApi.publish(site.value.id); toast.success('Публикация запущена'); fetchSite(); };
const unpublishSite = async () => { await sitesApi.unpublish(site.value.id); toast.success('Снятие с публикации'); fetchSite(); };
const regenerate = async () => { await sitesApi.generate(site.value.id); toast.success('Генерация запущена'); fetchSite(); };
onMounted(fetchSite);
</script>
