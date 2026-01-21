<template>
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <router-link to="/sites" class="text-dark-500 hover:text-dark-700">
                <ArrowLeftIcon class="w-5 h-5" />
            </router-link>
            <div>
                <h1 class="text-2xl font-bold text-dark-900">Создание сайта</h1>
                <p class="text-dark-500 mt-1">Настройте параметры нового сайта</p>
            </div>
        </div>

        <form @submit.prevent="createSite" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-6">
                <div class="card card-body space-y-4">
                    <h3 class="font-semibold text-dark-900">Основные настройки</h3>
                    
                    <div>
                        <label class="label">Домен *</label>
                        <select v-model="form.domain_id" class="input" required>
                            <option value="">Выберите домен</option>
                            <option v-for="domain in availableDomains" :key="domain.id" :value="domain.id">
                                {{ domain.domain }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="label">Шаблон *</label>
                        <select v-model="form.template_id" class="input" required>
                            <option value="">Выберите шаблон</option>
                            <option v-for="template in templates" :key="template.id" :value="template.id">
                                {{ template.name }} ({{ template.type }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="label">Название сайта *</label>
                        <input v-model="form.title" type="text" class="input" required placeholder="Название компании" />
                    </div>

                    <div>
                        <label class="label">Ниша/Тематика *</label>
                        <input v-model="form.niche" type="text" class="input" required placeholder="Строительство, юридические услуги..." />
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <div class="card card-body space-y-4">
                    <h3 class="font-semibold text-dark-900">SEO и ключевые слова</h3>
                    
                    <div>
                        <label class="label">Ключевые слова * (по одному на строку)</label>
                        <textarea v-model="keywordsText" rows="6" class="input font-mono text-sm"
                            placeholder="строительство домов&#10;ремонт квартир&#10;отделочные работы"></textarea>
                        <p class="text-sm text-dark-500 mt-1">{{ keywordsCount }} ключевых слов</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.keitaro_enabled" id="keitaro" class="w-4 h-4 rounded" />
                        <label for="keitaro" class="text-sm text-dark-700">Включить Keitaro TDS</label>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.auto_generate" id="autogen" class="w-4 h-4 rounded" />
                        <label for="autogen" class="text-sm text-dark-700">Автоматически сгенерировать контент</label>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <router-link to="/sites" class="btn-secondary">Отмена</router-link>
                    <button type="submit" :disabled="loading || !canSubmit" class="btn-primary">
                        {{ loading ? 'Создание...' : 'Создать сайт' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { sitesApi, domainsApi, templatesApi } from '@/services/api';
import { useToast } from 'vue-toastification';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const router = useRouter();
const toast = useToast();
const loading = ref(false);
const availableDomains = ref([]);
const templates = ref([]);
const keywordsText = ref('');

const form = reactive({
    domain_id: '',
    template_id: '',
    title: '',
    niche: '',
    keitaro_enabled: true,
    auto_generate: true,
});

const keywordsCount = computed(() => keywordsText.value.split('\n').filter(k => k.trim()).length);
const canSubmit = computed(() => form.domain_id && form.template_id && form.title && form.niche && keywordsCount.value > 0);

const fetchData = async () => {
    try {
        const [domainsRes, templatesRes] = await Promise.all([
            domainsApi.list({ available_only: true, per_page: 100 }),
            templatesApi.list({ active_only: true }),
        ]);
        availableDomains.value = domainsRes.data.data;
        templates.value = templatesRes.data.data;
    } catch (error) {
        toast.error('Ошибка загрузки данных');
    }
};

const createSite = async () => {
    loading.value = true;
    try {
        const keywords = keywordsText.value.split('\n').filter(k => k.trim());
        const response = await sitesApi.create({ ...form, keywords });
        toast.success('Сайт создан');
        router.push(`/sites/${response.data.data.id}/edit`);
    } catch (error) {
        toast.error(error.response?.data?.message || 'Ошибка создания');
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);
</script>
