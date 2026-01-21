<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div><h1 class="text-2xl font-bold text-dark-900">Серверы</h1><p class="text-dark-500">Управление серверами для публикации сайтов</p></div>
            <button @click="openCreateModal" class="btn-primary"><PlusIcon class="w-4 h-4 mr-2" />Добавить сервер</button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div v-for="server in servers" :key="server.id" class="card card-body">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-dark-100 flex items-center justify-center">
                        <ServerIcon class="w-5 h-5 text-dark-600" />
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold">{{ server.name }}</p>
                        <p class="text-sm text-dark-500">{{ server.ip_address }}</p>
                    </div>
                    <span v-if="server.is_primary" class="badge-info">Основной</span>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                    <div><span class="text-dark-500">Доменов:</span> {{ server.domains_count || 0 }}</div>
                    <div><span class="text-dark-500">Лимит:</span> {{ server.max_domains || '∞' }}</div>
                </div>
                <div class="flex items-center justify-between">
                    <StatusBadge :status="server.health_status || 'unknown'" type="health" />
                    <div class="flex gap-2">
                        <button @click="healthCheck(server)" class="btn-secondary btn-sm" :disabled="checking === server.id">
                            {{ checking === server.id ? '...' : 'Проверить' }}
                        </button>
                        <button v-if="!server.is_primary" @click="setPrimary(server)" class="btn-secondary btn-sm">Основной</button>
                        <button @click="editServer(server)" class="btn-secondary btn-sm">
                            <PencilIcon class="w-4 h-4" />
                        </button>
                        <button v-if="!server.is_primary" @click="deleteServer(server)" class="btn-sm text-red-600 hover:text-red-800">
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="!loading && servers.length === 0" class="card card-body text-center py-12">
            <ServerIcon class="w-12 h-12 mx-auto text-dark-300 mb-4" />
            <p class="text-dark-500">Нет серверов</p>
            <button @click="openCreateModal" class="btn-primary mt-4">Добавить первый сервер</button>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 mx-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold mb-4">{{ editingServer ? 'Редактировать сервер' : 'Добавить сервер' }}</h3>
                <form @submit.prevent="saveServer" class="space-y-4">
                    <div>
                        <label class="label">Название</label>
                        <input v-model="form.name" type="text" class="input w-full" required placeholder="Основной сервер">
                    </div>
                    <div>
                        <label class="label">IP адрес</label>
                        <input v-model="form.ip_address" type="text" class="input w-full" required placeholder="192.168.1.1">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">SSH порт</label>
                            <input v-model="form.ssh_port" type="number" class="input w-full" placeholder="22">
                        </div>
                        <div>
                            <label class="label">Макс. доменов</label>
                            <input v-model="form.max_domains" type="number" class="input w-full" placeholder="100">
                        </div>
                    </div>
                    <div>
                        <label class="label">SSH пользователь</label>
                        <input v-model="form.ssh_user" type="text" class="input w-full" placeholder="root">
                    </div>
                    <div>
                        <label class="label">SSH ключ (приватный)</label>
                        <textarea v-model="form.ssh_key" class="input w-full h-24 font-mono text-xs" placeholder="-----BEGIN RSA PRIVATE KEY-----"></textarea>
                        <p class="text-xs text-dark-400 mt-1">Оставьте пустым при редактировании, если не хотите менять</p>
                    </div>
                    <div>
                        <label class="label">Путь к сайтам</label>
                        <input v-model="form.sites_path" type="text" class="input w-full" placeholder="/var/www/sites">
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="form.is_active" type="checkbox" id="server_active" class="rounded">
                        <label for="server_active" class="text-sm">Активен</label>
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
import { serversApi } from '@/services/api';
import { useToast } from 'vue-toastification';
import StatusBadge from '@/components/common/StatusBadge.vue';
import { PlusIcon, ServerIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';

const toast = useToast();
const servers = ref([]);
const loading = ref(true);
const showModal = ref(false);
const saving = ref(false);
const checking = ref(null);
const editingServer = ref(null);

const defaultForm = {
    name: '',
    ip_address: '',
    ssh_port: 22,
    ssh_user: 'root',
    ssh_key: '',
    sites_path: '/var/www/sites',
    max_domains: 100,
    is_active: true
};
const form = reactive({ ...defaultForm });

const fetchServers = async () => {
    loading.value = true;
    try {
        const r = await serversApi.list();
        servers.value = r.data.data;
    } catch (e) {
        toast.error('Ошибка загрузки');
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingServer.value = null;
    Object.assign(form, { ...defaultForm });
    showModal.value = true;
};

const editServer = (server) => {
    editingServer.value = server;
    Object.assign(form, {
        name: server.name,
        ip_address: server.ip_address,
        ssh_port: server.ssh_port || 22,
        ssh_user: server.ssh_user || 'root',
        ssh_key: '',
        sites_path: server.sites_path || '/var/www/sites',
        max_domains: server.max_domains || 100,
        is_active: server.is_active
    });
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingServer.value = null;
};

const saveServer = async () => {
    saving.value = true;
    try {
        const data = { ...form };
        if (editingServer.value && !data.ssh_key) {
            delete data.ssh_key;
        }
        
        if (editingServer.value) {
            await serversApi.update(editingServer.value.id, data);
            toast.success('Сервер обновлён');
        } else {
            await serversApi.create(data);
            toast.success('Сервер добавлен');
        }
        closeModal();
        fetchServers();
    } catch (e) {
        toast.error(e.response?.data?.message || 'Ошибка сохранения');
    } finally {
        saving.value = false;
    }
};

const deleteServer = async (server) => {
    if (!confirm(`Удалить сервер "${server.name}"?`)) return;
    try {
        await serversApi.delete(server.id);
        toast.success('Сервер удалён');
        fetchServers();
    } catch (e) {
        toast.error('Ошибка удаления');
    }
};

const healthCheck = async (server) => {
    checking.value = server.id;
    try {
        const r = await serversApi.healthCheck(server.id);
        toast.success(`Статус: ${r.data.data.status || 'OK'}`);
        fetchServers();
    } catch (e) {
        toast.error('Сервер недоступен');
    } finally {
        checking.value = null;
    }
};

const setPrimary = async (server) => {
    try {
        await serversApi.setPrimary(server.id);
        toast.success('Сервер назначен основным');
        fetchServers();
    } catch (e) {
        toast.error('Ошибка');
    }
};

onMounted(fetchServers);
</script>
