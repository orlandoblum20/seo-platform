<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div><h1 class="text-2xl font-bold text-dark-900">Посты</h1><p class="text-dark-500">Автопостинг и генерация контента</p></div>
            <button @click="openGenerateModal" class="btn-primary"><PlusIcon class="w-4 h-4 mr-2" />Генерировать посты</button>
        </div>
        
        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="card card-body text-center"><p class="text-2xl font-semibold">{{ stats.total || 0 }}</p><p class="text-sm text-dark-500">Всего</p></div>
            <div class="card card-body text-center"><p class="text-2xl font-semibold text-green-600">{{ stats.by_status?.published || 0 }}</p><p class="text-sm text-dark-500">Опубликовано</p></div>
            <div class="card card-body text-center"><p class="text-2xl font-semibold text-yellow-600">{{ stats.scheduled || 0 }}</p><p class="text-sm text-dark-500">Запланировано</p></div>
            <div class="card card-body text-center"><p class="text-2xl font-semibold text-blue-600">{{ stats.published_today || 0 }}</p><p class="text-sm text-dark-500">Сегодня</p></div>
        </div>

        <!-- Filters -->
        <div class="card card-body">
            <div class="flex flex-wrap gap-4">
                <select v-model="filters.status" @change="fetchPosts" class="input w-40">
                    <option value="">Все статусы</option>
                    <option value="draft">Черновик</option>
                    <option value="scheduled">Запланирован</option>
                    <option value="published">Опубликован</option>
                </select>
                <select v-model="filters.site_id" @change="fetchPosts" class="input w-48">
                    <option value="">Все сайты</option>
                    <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.domain?.domain }}</option>
                </select>
            </div>
        </div>

        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead><tr><th>Заголовок</th><th>Сайт</th><th>Тип</th><th>Статус</th><th>Дата</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="post in posts" :key="post.id">
                            <td class="font-medium max-w-xs truncate">{{ post.title }}</td>
                            <td class="text-sm">{{ post.site?.domain?.domain || '—' }}</td>
                            <td><span class="badge-gray">{{ post.type }}</span></td>
                            <td><StatusBadge :status="post.status" type="post" /></td>
                            <td class="text-sm text-dark-500">{{ formatDate(post.created_at) }}</td>
                            <td class="text-right">
                                <button v-if="post.status === 'draft'" @click="publishPost(post)" class="p-1 text-green-500 hover:text-green-700" title="Опубликовать">
                                    <ArrowUpTrayIcon class="w-4 h-4" />
                                </button>
                                <button @click="editPost(post)" class="p-1 text-dark-400 hover:text-dark-600"><PencilIcon class="w-4 h-4" /></button>
                                <button @click="deletePost(post)" class="p-1 text-red-400 hover:text-red-600"><TrashIcon class="w-4 h-4" /></button>
                            </td>
                        </tr>
                        <tr v-if="posts.length === 0">
                            <td colspan="6" class="text-center py-8 text-dark-500">Нет постов</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Generate Modal -->
        <div v-if="showGenerateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeGenerateModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 mx-4">
                <h3 class="text-lg font-semibold mb-4">Генерация постов</h3>
                <form @submit.prevent="generatePosts" class="space-y-4">
                    <div>
                        <label class="label">Сайты</label>
                        <select v-model="generateForm.site_ids" multiple class="input w-full h-32">
                            <option v-for="site in sites.filter(s => s.status === 'published')" :key="site.id" :value="site.id">
                                {{ site.domain?.domain }}
                            </option>
                        </select>
                        <p class="text-xs text-dark-400 mt-1">Удерживайте Ctrl для выбора нескольких</p>
                    </div>
                    <div>
                        <label class="label">Тип контента</label>
                        <select v-model="generateForm.type" class="input w-full">
                            <option value="article">Статья</option>
                            <option value="news">Новость</option>
                            <option value="review">Обзор</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="generateForm.schedule" type="checkbox" id="schedule_posts" class="rounded">
                        <label for="schedule_posts" class="text-sm">Запланировать публикацию</label>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="closeGenerateModal" class="btn-secondary flex-1">Отмена</button>
                        <button type="submit" :disabled="generating || !generateForm.site_ids.length" class="btn-primary flex-1">
                            {{ generating ? 'Генерация...' : 'Генерировать' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeEditModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl p-6 mx-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold mb-4">Редактировать пост</h3>
                <form @submit.prevent="savePost" class="space-y-4">
                    <div>
                        <label class="label">Заголовок</label>
                        <input v-model="editForm.title" type="text" class="input w-full" required>
                    </div>
                    <div>
                        <label class="label">Контент</label>
                        <textarea v-model="editForm.content" class="input w-full h-64 font-mono text-sm"></textarea>
                    </div>
                    <div>
                        <label class="label">Meta Description</label>
                        <textarea v-model="editForm.meta_description" class="input w-full h-20"></textarea>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="closeEditModal" class="btn-secondary flex-1">Отмена</button>
                        <button type="submit" :disabled="saving" class="btn-primary flex-1">{{ saving ? 'Сохранение...' : 'Сохранить' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { postsApi, sitesApi } from '@/services/api';
import { useToast } from 'vue-toastification';
import StatusBadge from '@/components/common/StatusBadge.vue';
import { PlusIcon, PencilIcon, TrashIcon, ArrowUpTrayIcon } from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';

const toast = useToast();
const posts = ref([]);
const sites = ref([]);
const stats = ref({});
const loading = ref(true);
const filters = reactive({ status: '', site_id: '' });

const showGenerateModal = ref(false);
const showEditModal = ref(false);
const generating = ref(false);
const saving = ref(false);
const editingPost = ref(null);

const generateForm = reactive({ site_ids: [], type: 'article', schedule: true });
const editForm = reactive({ title: '', content: '', meta_description: '' });

const formatDate = (d) => dayjs(d).format('DD.MM.YYYY HH:mm');

const fetchPosts = async () => {
    loading.value = true;
    try {
        const params = {};
        if (filters.status) params.status = filters.status;
        if (filters.site_id) params.site_id = filters.site_id;
        const r = await postsApi.list(params);
        posts.value = r.data.data;
    } catch (e) {
        toast.error('Ошибка загрузки');
    } finally {
        loading.value = false;
    }
};

const fetchStats = async () => {
    try {
        const r = await postsApi.stats();
        stats.value = r.data.data || {};
    } catch (e) {}
};

const fetchSites = async () => {
    try {
        const r = await sitesApi.list({ per_page: 100 });
        sites.value = r.data.data;
    } catch (e) {}
};

const openGenerateModal = () => {
    generateForm.site_ids = [];
    generateForm.type = 'article';
    generateForm.schedule = true;
    showGenerateModal.value = true;
};

const closeGenerateModal = () => { showGenerateModal.value = false; };

const generatePosts = async () => {
    generating.value = true;
    try {
        await postsApi.bulkGenerate(generateForm.site_ids, generateForm.type, generateForm.schedule);
        toast.success('Генерация постов запущена');
        closeGenerateModal();
        fetchPosts();
        fetchStats();
    } catch (e) {
        toast.error(e.response?.data?.message || 'Ошибка генерации');
    } finally {
        generating.value = false;
    }
};

const editPost = (post) => {
    editingPost.value = post;
    editForm.title = post.title;
    editForm.content = post.content || '';
    editForm.meta_description = post.meta_description || '';
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingPost.value = null;
};

const savePost = async () => {
    saving.value = true;
    try {
        await postsApi.update(editingPost.value.id, editForm);
        toast.success('Пост обновлён');
        closeEditModal();
        fetchPosts();
    } catch (e) {
        toast.error('Ошибка сохранения');
    } finally {
        saving.value = false;
    }
};

const publishPost = async (post) => {
    try {
        await postsApi.publish(post.id);
        toast.success('Пост опубликован');
        fetchPosts();
        fetchStats();
    } catch (e) {
        toast.error('Ошибка публикации');
    }
};

const deletePost = async (post) => {
    if (!confirm(`Удалить пост "${post.title}"?`)) return;
    try {
        await postsApi.delete(post.id);
        toast.success('Пост удалён');
        fetchPosts();
        fetchStats();
    } catch (e) {
        toast.error('Ошибка удаления');
    }
};

onMounted(() => {
    fetchPosts();
    fetchStats();
    fetchSites();
});
</script>
