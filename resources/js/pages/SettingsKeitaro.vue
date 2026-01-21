<template>
    <div class="space-y-6">
        <div class="flex items-center gap-4"><router-link to="/settings" class="text-dark-500 hover:text-dark-700"><ArrowLeftIcon class="w-5 h-5" /></router-link><div><h1 class="text-2xl font-bold text-dark-900">Keitaro TDS</h1><p class="text-dark-500">Настройки интеграции с Keitaro</p></div></div>
        <div class="card card-body space-y-4 max-w-2xl">
            <div class="flex items-center gap-2"><input type="checkbox" v-model="form.enabled" id="enabled" class="w-4 h-4 rounded" /><label for="enabled" class="font-medium">Включить Keitaro</label></div>
            <div><label class="label">URL Keitaro</label><input v-model="form.url" type="url" class="input" placeholder="https://your-keitaro.com" /></div>
            <div><label class="label">Campaign ID</label><input v-model="form.campaign_id" type="text" class="input" placeholder="123" /></div>
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
const form = reactive({ enabled: false, url: '', campaign_id: '' });
const save = async () => { loading.value = true; try { await settingsApi.updateKeitaro(form); toast.success('Сохранено'); } catch(e) { toast.error('Ошибка'); } finally { loading.value = false; } };
onMounted(async () => { const r = await settingsApi.getKeitaro(); Object.assign(form, r.data.data); });
</script>
