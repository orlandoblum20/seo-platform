<template>
    <div class="space-y-6">
        <div class="flex items-center gap-4"><router-link to="/settings" class="text-dark-500 hover:text-dark-700"><ArrowLeftIcon class="w-5 h-5" /></router-link><div><h1 class="text-2xl font-bold text-dark-900">Аналитика</h1><p class="text-dark-500">Глобальные коды аналитики</p></div></div>
        <div class="card card-body space-y-4 max-w-2xl">
            <div><label class="label">Яндекс.Метрика (номер счётчика)</label><input v-model="form.yandex_metrika" type="text" class="input" placeholder="12345678" /></div>
            <div><label class="label">Google Analytics (ID)</label><input v-model="form.google_analytics" type="text" class="input" placeholder="G-XXXXXXXXXX" /></div>
            <div><label class="label">Google Tag Manager (ID)</label><input v-model="form.google_tag_manager" type="text" class="input" placeholder="GTM-XXXXXXX" /></div>
            <div><label class="label">Дополнительные скрипты</label><textarea v-model="form.custom_scripts" rows="4" class="input font-mono text-sm" placeholder="<script>...</script>"></textarea></div>
            <div class="flex justify-end"><button @click="save" :disabled="loading" class="btn-primary">{{ loading ? 'Сохранение...' : 'Сохранить' }}</button></div>
        </div>
    </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue';
import { settingsApi } from '@/services/api';
import { useToast } from 'vue-toastification';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
const toast = useToast();
const loading = ref(false);
const form = reactive({ yandex_metrika: '', google_analytics: '', google_tag_manager: '', custom_scripts: '' });
const save = async () => { loading.value = true; try { await settingsApi.updateAnalytics(form); toast.success('Сохранено'); } catch(e) { toast.error('Ошибка'); } finally { loading.value = false; } };
onMounted(async () => { const r = await settingsApi.getAnalytics(); Object.assign(form, r.data.data); });
</script>
