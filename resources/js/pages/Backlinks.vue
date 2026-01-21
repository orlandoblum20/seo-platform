<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div><h1 class="text-2xl font-bold text-dark-900">Бэклинки</h1><p class="text-dark-500">Управление PBN ссылками</p></div>
            <button @click="openCreateModal" class="btn-primary"><PlusIcon class="w-4 h-4 mr-2" />Добавить</button>
        </div>
        
        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="card card-body text-center"><p class="text-2xl font-semibold">{{ backlinks.length }}</p><p class="text-sm text-dark-500">Всего</p></div>
            <div class="card card-body text-center"><p class="text-2xl font-semibold text-green-600">{{ backlinks.filter(b => b.is_active).length }}</p><p class="text-sm text-dark-500">Активных</p></div>
            <div class="card card-body text-center"><p class="text-2xl font-semibold text-blue-600">{{ totalAnchors }}</p><p class="text-sm text-dark-500">Анкоров</p></div>
            <div class="card card-body text-center"><p class="text-2xl font-semibold text-purple-600">{{ totalSites }}</p><p class="text-sm text-dark-500">Назначено сайтам</p></div>
        </div>

        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead><tr><th>Название</th><th>URL</th><th>Анкоры</th><th>Группа</th><th>Сайтов</th><th>Статус</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="link in backlinks" :key="link.id">
                            <td class="font-medium">{{ link.name }}</td>
                            <td class="text-sm text-dark-500 max-w-xs truncate">
                                <a :href="link.url" target="_blank" class="hover:text-primary-600">{{ link.url }}</a>
                            </td>
                            <td><span class="badge-gray">{{ (link.anchors || []).length }}</span></td>
                            <td class="text-sm">{{ link.group || '—' }}</td>
                            <td>{{ link.sites_count || 0 }}</td>
                            <td><span :class="link.is_active ? 'badge-success' : 'badge-gray'">{{ link.is_active ? 'Активен' : 'Неактивен' }}</span></td>
                            <td class="text-right">
                                <button @click="editBacklink(link)" class="p-1 text-dark-400 hover:text-dark-600"><PencilIcon class="w-4 h-4" /></button>
                                <button @click="deleteBacklink(link)" class="p-1 text-red-400 hover:text-red-600"><TrashIcon class="w-4 h-4" /></button>
                            </td>
                        </tr>
                        <tr v-if="backlinks.length === 0">
                            <td colspan="7" class="text-center py-8 text-dark-500">Нет бэклинков</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 mx-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold mb-4">{{ editingBacklink ? 'Редактировать бэклинк' : 'Добавить бэклинк' }}</h3>
                <form @submit.prevent="saveBacklink" class="space-y-4">
                    <div>
                        <label class="label">Название</label>
                        <input v-model="form.name" type="text" class="input w-full" required placeholder="PBN Site #1">
                    </div>
                    <div>
                        <label class="label">URL</label>
                        <input v-model="form.url" type="url" class="input w-full" required placeholder="https://pbn-site.com">
                    </div>
                    <div>
                        <label class="label">Группа</label>
                        <input v-model="form.group" type="text" class="input w-full" placeholder="Группа 1">
                    </div>
                    <div>
                        <label class="label">Анкоры <span class="text-dark-400 font-normal">(по одному на строку)</span></label>
                        <textarea v-model="anchorsText" class="input w-full h-32" placeholder="купить недорого
заказать онлайн
лучшая цена
подробнее"></textarea>
                    </div>
                    <div>
                        <label class="label">Приоритет <span class="text-dark-400 font-normal">(1-100)</span></label>
                        <input v-model="form.priority" type="number" class="input w-full" min="1" max="100" placeholder="50">
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="form.is_active" type="checkbox" id="backlink_active" class="rounded">
                        <label for="backlink_active" class="text-sm">Активен</label>
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
import { ref, onMounted, reactive, computed } from 'vue';
import { backlinksApi } from '@/services/api';
import { useToast } from 'vue-toastification';
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';

const toast = useToast();
const backlinks = ref([]);
const loading = ref(true);
const showModal = ref(false);
const saving = ref(false);
const editingBacklink = ref(null);
const anchorsText = ref('');

const totalAnchors = computed(() => backlinks.value.reduce((sum, b) => sum + (b.anchors?.length || 0), 0));
const totalSites = computed(() => backlinks.value.reduce((sum, b) => sum + (b.sites_count || 0), 0));

const defaultForm = { name: '', url: '', group: '', anchors: [], priority: 50, is_active: true };
const form = reactive({ ...defaultForm });

const fetchBacklinks = async () => {
    loading.value = true;
    try {
        const r = await backlinksApi.list();
        backlinks.value = r.data.data;
    } catch (e) {
        toast.error('Ошибка загрузки');
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingBacklink.value = null;
    Object.assign(form, { ...defaultForm });
    anchorsText.value = '';
    showModal.value = true;
};

const editBacklink = (link) => {
    editingBacklink.value = link;
    Object.assign(form, {
        name: link.name,
        url: link.url,
        group: link.group || '',
        anchors: link.anchors || [],
        priority: link.priority || 50,
        is_active: link.is_active
    });
    anchorsText.value = (link.anchors || []).join('\n');
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingBacklink.value = null;
};

const saveBacklink = async () => {
    saving.value = true;
    try {
        const data = {
            ...form,
            anchors: anchorsText.value.split('\n').map(a => a.trim()).filter(Boolean)
        };
        
        if (editingBacklink.value) {
            await backlinksApi.update(editingBacklink.value.id, data);
            toast.success('Бэклинк обновлён');
        } else {
            await backlinksApi.create(data);
            toast.success('Бэклинк добавлен');
        }
        closeModal();
        fetchBacklinks();
    } catch (e) {
        toast.error(e.response?.data?.message || 'Ошибка сохранения');
    } finally {
        saving.value = false;
    }
};

const deleteBacklink = async (link) => {
    if (!confirm(`Удалить бэклинк "${link.name}"?`)) return;
    try {
        await backlinksApi.delete(link.id);
        toast.success('Бэклинк удалён');
        fetchBacklinks();
    } catch (e) {
        toast.error('Ошибка удаления');
    }
};

onMounted(fetchBacklinks);
</script>
