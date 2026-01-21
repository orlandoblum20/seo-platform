<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <router-link to="/settings" class="text-dark-500 hover:text-dark-700">
                    <ArrowLeftIcon class="w-5 h-5" />
                </router-link>
                <div>
                    <h1 class="text-2xl font-bold text-dark-900">AI провайдеры</h1>
                    <p class="text-dark-500">Настройки OpenAI и Anthropic (Claude)</p>
                </div>
            </div>
            <button @click="openCreateModal" class="btn-primary">
                <PlusIcon class="w-4 h-4 mr-2" />
                Добавить провайдер
            </button>
        </div>

        <!-- Providers List -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div v-for="provider in providers" :key="provider.id" class="card card-body">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center" 
                         :class="provider.provider === 'anthropic' ? 'bg-orange-100' : provider.provider === 'deepseek' ? 'bg-blue-100' : 'bg-green-100'">
                        <span class="text-xl">{{ provider.provider === 'anthropic' ? '🧠' : provider.provider === 'deepseek' ? '🐋' : '🤖' }}</span>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-dark-900">{{ provider.name }}</p>
                        <p class="text-sm text-dark-500">{{ provider.model }}</p>
                    </div>
                    <span v-if="provider.is_default" class="badge-success">По умолчанию</span>
                    <span v-if="!provider.is_active" class="badge-gray">Неактивен</span>
                </div>
                
                <div class="grid grid-cols-2 gap-4 text-sm mb-4 p-3 bg-dark-50 rounded-lg">
                    <div>
                        <span class="text-dark-500">Запросов сегодня:</span>
                        <span class="font-medium ml-1">{{ provider.requests_today || 0 }}</span>
                    </div>
                    <div>
                        <span class="text-dark-500">Лимит:</span>
                        <span class="font-medium ml-1">{{ provider.daily_limit || '∞' }}</span>
                    </div>
                    <div>
                        <span class="text-dark-500">Max tokens:</span>
                        <span class="font-medium ml-1">{{ provider.max_tokens || 4096 }}</span>
                    </div>
                    <div>
                        <span class="text-dark-500">Temperature:</span>
                        <span class="font-medium ml-1">{{ provider.temperature || 0.7 }}</span>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <button @click="testProvider(provider)" :disabled="testing === provider.id" 
                            class="btn-secondary btn-sm flex-1">
                        {{ testing === provider.id ? 'Тестирование...' : '🧪 Тест' }}
                    </button>
                    <button @click="editProvider(provider)" class="btn-secondary btn-sm flex-1">
                        ✏️ Изменить
                    </button>
                    <button v-if="!provider.is_default" @click="setDefault(provider)" class="btn-secondary btn-sm flex-1">
                        ⭐ По умолч.
                    </button>
                    <button @click="deleteProvider(provider)" class="btn-secondary btn-sm text-red-600 hover:bg-red-50">
                        🗑️
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && providers.length === 0" class="card card-body text-center py-12">
            <div class="text-4xl mb-4">🤖</div>
            <p class="text-dark-500 mb-4">Нет настроенных AI провайдеров</p>
            <p class="text-sm text-dark-400 mb-6">Добавьте OpenAI или Anthropic для генерации контента</p>
            <button @click="openCreateModal" class="btn-primary mx-auto">Добавить провайдер</button>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 mx-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold mb-4">
                    {{ editingProvider ? 'Редактировать провайдер' : 'Добавить AI провайдер' }}
                </h3>
                
                <form @submit.prevent="saveProvider" class="space-y-4">
                    <div>
                        <label class="label">Провайдер *</label>
                        <select v-model="form.provider" class="input" required :disabled="editingProvider">
                            <option value="">Выберите провайдер</option>
                            <option value="openai">OpenAI (GPT-4o, o1, o3)</option>
                            <option value="anthropic">Anthropic (Claude 4.5)</option>
                            <option value="deepseek">DeepSeek (R1, V3)</option>
                        </select>
                    </div>

                    <div>
                        <label class="label">Название *</label>
                        <input v-model="form.name" type="text" class="input" required 
                               placeholder="Мой OpenAI ключ">
                    </div>

                    <div>
                        <label class="label">API ключ *</label>
                        <input v-model="form.api_key" type="password" class="input" required 
                               :placeholder="editingProvider ? '••••••••' : 'sk-...'">
                        <p class="text-xs text-dark-400 mt-1">
                            {{ form.provider === 'anthropic' ? 'Получить: console.anthropic.com' : 
                               form.provider === 'deepseek' ? 'Получить: platform.deepseek.com' :
                               'Получить: platform.openai.com/api-keys' }}
                        </p>
                    </div>

                    <div>
                        <label class="label">Модель *</label>
                        <select v-model="form.model" class="input" required>
                            <option value="">Выберите модель</option>
                            <template v-if="form.provider === 'openai'">
                                <option value="o3-mini">o3-mini (новейший)</option>
                                <option value="o1">o1 (reasoning)</option>
                                <option value="o1-mini">o1-mini</option>
                                <option value="gpt-4o">GPT-4o (рекомендуется)</option>
                                <option value="gpt-4o-mini">GPT-4o Mini (дешевле)</option>
                            </template>
                            <template v-if="form.provider === 'anthropic'">
                                <option value="claude-opus-4-5-20250514">Claude Opus 4.5 (самый умный)</option>
                                <option value="claude-sonnet-4-5-20250514">Claude Sonnet 4.5 (рекомендуется)</option>
                                <option value="claude-sonnet-4-20250514">Claude Sonnet 4</option>
                                <option value="claude-3-5-haiku-20241022">Claude 3.5 Haiku (быстрый)</option>
                            </template>
                            <template v-if="form.provider === 'deepseek'">
                                <option value="deepseek-reasoner">DeepSeek R1 (reasoning)</option>
                                <option value="deepseek-chat">DeepSeek Chat V3</option>
                            </template>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Max Tokens</label>
                            <input v-model.number="form.max_tokens" type="number" class="input" 
                                   min="100" max="100000" placeholder="4096">
                        </div>
                        <div>
                            <label class="label">Temperature</label>
                            <input v-model.number="form.temperature" type="number" class="input" 
                                   min="0" max="2" step="0.1" placeholder="0.7">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Дневной лимит запросов</label>
                            <input v-model.number="form.daily_limit" type="number" class="input" 
                                   min="1" placeholder="Без лимита">
                        </div>
                        <div>
                            <label class="label">Rate limit (запросов/мин)</label>
                            <input v-model.number="form.rate_limit" type="number" class="input" 
                                   min="1" placeholder="60">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2">
                            <input v-model="form.is_active" type="checkbox" class="rounded">
                            <span class="text-sm">Активен</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input v-model="form.is_default" type="checkbox" class="rounded">
                            <span class="text-sm">По умолчанию</span>
                        </label>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="closeModal" class="btn-secondary flex-1">Отмена</button>
                        <button type="submit" :disabled="saving" class="btn-primary flex-1">
                            {{ saving ? 'Сохранение...' : 'Сохранить' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { settingsApi } from '@/services/api';
import { useToast } from 'vue-toastification';
import { ArrowLeftIcon, PlusIcon } from '@heroicons/vue/24/outline';

const toast = useToast();
const loading = ref(true);
const saving = ref(false);
const testing = ref(null);
const providers = ref([]);
const showModal = ref(false);
const editingProvider = ref(null);

const defaultForm = {
    provider: '',
    name: '',
    api_key: '',
    model: '',
    max_tokens: 4096,
    temperature: 0.7,
    daily_limit: null,
    rate_limit: 60,
    is_active: true,
    is_default: false,
};

const form = reactive({ ...defaultForm });

const fetchProviders = async () => {
    loading.value = true;
    try {
        const response = await settingsApi.getAi();
        providers.value = response.data.data.providers || [];
    } catch (error) {
        toast.error('Ошибка загрузки');
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingProvider.value = null;
    Object.assign(form, { ...defaultForm });
    showModal.value = true;
};

const editProvider = (provider) => {
    editingProvider.value = provider;
    Object.assign(form, {
        provider: provider.provider,
        name: provider.name,
        api_key: '',
        model: provider.model,
        max_tokens: provider.max_tokens || 4096,
        temperature: provider.temperature || 0.7,
        daily_limit: provider.daily_limit,
        rate_limit: provider.rate_limit || 60,
        is_active: provider.is_active,
        is_default: provider.is_default,
    });
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingProvider.value = null;
};

const saveProvider = async () => {
    saving.value = true;
    try {
        const data = { ...form };
        if (editingProvider.value && !data.api_key) {
            delete data.api_key;
        }
        
        if (editingProvider.value) {
            await settingsApi.updateAi(editingProvider.value.id, data);
            toast.success('Провайдер обновлён');
        } else {
            await settingsApi.createAi(data);
            toast.success('Провайдер добавлен');
        }
        closeModal();
        fetchProviders();
    } catch (error) {
        toast.error(error.response?.data?.message || 'Ошибка сохранения');
    } finally {
        saving.value = false;
    }
};

const testProvider = async (provider) => {
    testing.value = provider.id;
    try {
        await settingsApi.testAi(provider.id);
        toast.success('✅ API работает!');
    } catch (error) {
        toast.error('❌ Ошибка API: ' + (error.response?.data?.message || 'Недоступен'));
    } finally {
        testing.value = null;
    }
};

const setDefault = async (provider) => {
    try {
        await settingsApi.setDefaultAi(provider.id);
        toast.success('Провайдер назначен по умолчанию');
        fetchProviders();
    } catch (error) {
        toast.error('Ошибка');
    }
};

const deleteProvider = async (provider) => {
    if (!confirm(`Удалить провайдер "${provider.name}"?`)) return;
    try {
        await settingsApi.deleteAi(provider.id);
        toast.success('Провайдер удалён');
        fetchProviders();
    } catch (error) {
        toast.error(error.response?.data?.message || 'Ошибка удаления');
    }
};

onMounted(fetchProviders);
</script>
