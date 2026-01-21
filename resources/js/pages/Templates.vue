<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div><h1 class="text-2xl font-bold text-dark-900">Шаблоны</h1><p class="text-dark-500">Управление шаблонами сайтов</p></div>
            <button @click="openCreateModal" class="btn-primary"><PlusIcon class="w-4 h-4 mr-2" />Создать шаблон</button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="template in templates" :key="template.id" class="card overflow-hidden group">
                <div class="h-40 bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                    <DocumentTextIcon class="w-16 h-16 text-white/30" />
                </div>
                <div class="p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-semibold text-dark-900">{{ template.name }}</h3>
                            <p class="text-sm text-dark-500 mt-1">{{ template.description || getTypeName(template.type) }}</p>
                        </div>
                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="editTemplate(template)" class="p-1 text-dark-400 hover:text-dark-600"><PencilIcon class="w-4 h-4" /></button>
                            <button @click="duplicateTemplate(template)" class="p-1 text-dark-400 hover:text-primary-600"><DocumentDuplicateIcon class="w-4 h-4" /></button>
                            <button @click="deleteTemplate(template)" class="p-1 text-red-400 hover:text-red-600"><TrashIcon class="w-4 h-4" /></button>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between">
                        <span class="badge-gray">{{ template.sites_count || 0 }} сайтов</span>
                        <span :class="template.is_active ? 'badge-success' : 'badge-gray'">{{ template.is_active ? 'Активен' : 'Неактивен' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="!loading && templates.length === 0" class="card card-body text-center py-12">
            <DocumentTextIcon class="w-12 h-12 mx-auto text-dark-300 mb-4" />
            <p class="text-dark-500">Нет шаблонов</p>
            <button @click="openCreateModal" class="btn-primary mt-4">Создать первый шаблон</button>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 mx-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold mb-4">{{ editingTemplate ? 'Редактировать шаблон' : 'Создать шаблон' }}</h3>
                <form @submit.prevent="saveTemplate" class="space-y-4">
                    <div>
                        <label class="label">Название</label>
                        <input v-model="form.name" type="text" class="input w-full" required placeholder="Бизнес шаблон">
                    </div>
                    <div>
                        <label class="label">Тип</label>
                        <select v-model="form.type" class="input w-full" required>
                            <option value="business">Бизнес</option>
                            <option value="service">Услуги</option>
                            <option value="landing">Лендинг</option>
                            <option value="corporate">Корпоративный</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Описание</label>
                        <textarea v-model="form.description" class="input w-full h-20" placeholder="Описание шаблона..."></textarea>
                    </div>
                    <div>
                        <label class="label">Структура страниц</label>
                        <div class="space-y-2">
                            <label v-for="page in availablePages" :key="page" class="flex items-center gap-2">
                                <input type="checkbox" v-model="form.pages" :value="page" class="rounded">
                                <span class="text-sm">{{ pageNames[page] || page }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="form.is_active" type="checkbox" id="template_active" class="rounded">
                        <label for="template_active" class="text-sm">Активен</label>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="closeModal" class="btn-secondary flex-1">Отмена</button>
                        <button type="submit" :disabled="saving" class="btn-primary flex-1">{{ saving ? 'Сохранение...' : 'Сохранить' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { templatesApi } from '@/services/api';
import { useToast } from 'vue-toastification';
import { PlusIcon, DocumentTextIcon, PencilIcon, TrashIcon, DocumentDuplicateIcon } from '@heroicons/vue/24/outline';

const toast = useToast();
const templates = ref([]);
const loading = ref(true);
const showModal = ref(false);
const saving = ref(false);
const editingTemplate = ref(null);

const availablePages = ['home', 'about', 'services', 'contacts', 'blog'];
const pageNames = { home: 'Главная', about: 'О компании', services: 'Услуги', contacts: 'Контакты', blog: 'Блог' };
const typeNames = { business: 'Бизнес', service: 'Услуги', landing: 'Лендинг', corporate: 'Корпоративный' };

const defaultForm = { name: '', type: 'business', description: '', pages: ['home', 'about', 'services', 'contacts'], is_active: true };
const form = reactive({ ...defaultForm });

const getTypeName = (type) => typeNames[type] || type;

const fetchTemplates = async () => {
    loading.value = true;
    try {
        const r = await templatesApi.list();
        templates.value = r.data.data;
    } catch (e) {
        toast.error('Ошибка загрузки');
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingTemplate.value = null;
    Object.assign(form, { ...defaultForm });
    showModal.value = true;
};

const editTemplate = (template) => {
    editingTemplate.value = template;
    Object.assign(form, {
        name: template.name,
        type: template.type,
        description: template.description || '',
        pages: template.structure?.pages || ['home'],
        is_active: template.is_active
    });
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingTemplate.value = null;
};

const saveTemplate = async () => {
    saving.value = true;
    try {
        const data = {
            ...form,
            structure: { pages: form.pages }
        };
        delete data.pages;
        
        if (editingTemplate.value) {
            await templatesApi.update(editingTemplate.value.id, data);
            toast.success('Шаблон обновлён');
        } else {
            await templatesApi.create(data);
            toast.success('Шаблон создан');
        }
        closeModal();
        fetchTemplates();
    } catch (e) {
        toast.error(e.response?.data?.message || 'Ошибка сохранения');
    } finally {
        saving.value = false;
    }
};

const duplicateTemplate = async (template) => {
    try {
        await templatesApi.duplicate(template.id);
        toast.success('Шаблон скопирован');
        fetchTemplates();
    } catch (e) {
        toast.error('Ошибка копирования');
    }
};

const deleteTemplate = async (template) => {
    if (!confirm(`Удалить шаблон "${template.name}"?`)) return;
    try {
        await templatesApi.delete(template.id);
        toast.success('Шаблон удалён');
        fetchTemplates();
    } catch (e) {
        toast.error('Ошибка удаления');
    }
};

onMounted(fetchTemplates);
</script>
