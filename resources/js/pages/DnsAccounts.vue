<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div><h1 class="text-2xl font-bold text-dark-900">DNS аккаунты</h1><p class="text-dark-500">Cloudflare и DNSPOD интеграции</p></div>
            <button @click="openCreateModal" class="btn-primary"><PlusIcon class="w-4 h-4 mr-2" />Добавить аккаунт</button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="account in accounts" :key="account.id" class="card card-body">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="account.provider === 'cloudflare' ? 'bg-orange-100' : 'bg-blue-100'">
                        <CloudIcon class="w-5 h-5" :class="account.provider === 'cloudflare' ? 'text-orange-600' : 'text-blue-600'" />
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold">{{ account.name }}</p>
                        <p class="text-sm text-dark-500">{{ account.provider }}<span v-if="account.email" class="ml-1">(Global)</span><span v-else-if="account.provider === 'cloudflare'" class="ml-1">(Token)</span></p>
                    </div>
                    <button @click="editAccount(account)" class="p-1 text-dark-400 hover:text-dark-600"><PencilIcon class="w-4 h-4" /></button>
                    <button @click="deleteAccount(account)" class="p-1 text-red-400 hover:text-red-600"><TrashIcon class="w-4 h-4" /></button>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-dark-500">{{ account.domains_count || 0 }} доменов</span>
                    <span :class="account.is_active ? 'badge-success' : 'badge-gray'">{{ account.is_active ? 'Активен' : 'Неактивен' }}</span>
                </div>
                <div class="mt-4 flex gap-2">
                    <button @click="verifyAccount(account)" class="btn-secondary btn-sm flex-1">Проверить</button>
                    <button @click="syncAccount(account)" class="btn-secondary btn-sm flex-1">Синхр.</button>
                </div>
            </div>
        </div>

        <div v-if="!loading && accounts.length === 0" class="card card-body text-center py-12">
            <CloudIcon class="w-12 h-12 mx-auto text-dark-300 mb-4" />
            <p class="text-dark-500">Нет DNS аккаунтов</p>
            <button @click="openCreateModal" class="btn-primary mt-4">Добавить первый аккаунт</button>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 mx-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold mb-4">{{ editingAccount ? 'Редактировать аккаунт' : 'Добавить DNS аккаунт' }}</h3>
                <form @submit.prevent="saveAccount" class="space-y-4">
                    <div>
                        <label class="label">Название</label>
                        <input v-model="form.name" type="text" class="input w-full" required placeholder="Мой Cloudflare">
                    </div>
                    <div>
                        <label class="label">Провайдер</label>
                        <select v-model="form.provider" class="input w-full" required :disabled="editingAccount">
                            <option value="cloudflare">Cloudflare</option>
                            <option value="dnspod">DNSPOD International</option>
                        </select>
                    </div>
                    
                    <!-- Cloudflare -->
                    <template v-if="form.provider === 'cloudflare'">
                        <div>
                            <label class="label">Тип авторизации</label>
                            <select v-model="form.auth_type" class="input w-full">
                                <option value="global">Global API Key (Email + Key)</option>
                                <option value="token">API Token</option>
                            </select>
                        </div>
                        
                        <template v-if="form.auth_type === 'global'">
                            <div>
                                <label class="label">Email</label>
                                <input v-model="form.email" type="email" class="input w-full" required placeholder="your@email.com">
                            </div>
                            <div>
                                <label class="label">Global API Key</label>
                                <input v-model="form.api_key" type="password" class="input w-full" :required="!editingAccount" placeholder="Global API Key">
                            </div>
                            <p class="text-xs text-dark-400">My Profile → API Tokens → Global API Key → View</p>
                        </template>
                        
                        <template v-if="form.auth_type === 'token'">
                            <div>
                                <label class="label">API Token</label>
                                <input v-model="form.api_key" type="password" class="input w-full" :required="!editingAccount" placeholder="API Token">
                            </div>
                            <div>
                                <label class="label">Account ID</label>
                                <input v-model="form.account_id" type="text" class="input w-full" required placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                            </div>
                            <p class="text-xs text-dark-400">
                                Token: My Profile → API Tokens → Create Token<br>
                                Account ID: любой домен → Overview → справа внизу
                            </p>
                        </template>
                    </template>
                    
                    <!-- DNSPOD -->
                    <template v-if="form.provider === 'dnspod'">
                        <div>
                            <label class="label">API ID</label>
                            <input v-model="form.api_secret" type="text" class="input w-full" :required="!editingAccount" placeholder="123456">
                        </div>
                        <div>
                            <label class="label">API Token</label>
                            <input v-model="form.api_key" type="password" class="input w-full" :required="!editingAccount" placeholder="token">
                        </div>
                        <p class="text-xs text-dark-400">DNSPOD Console → Account → API Key</p>
                    </template>
                    
                    <div class="flex items-center gap-2">
                        <input v-model="form.is_active" type="checkbox" id="is_active" class="rounded">
                        <label for="is_active" class="text-sm">Активен</label>
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
import { ref, onMounted, reactive, watch } from 'vue';
import { dnsAccountsApi } from '@/services/api';
import { useToast } from 'vue-toastification';
import { PlusIcon, CloudIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';

const toast = useToast();
const accounts = ref([]);
const loading = ref(true);
const showModal = ref(false);
const saving = ref(false);
const editingAccount = ref(null);

const defaultForm = { name: '', provider: 'cloudflare', auth_type: 'global', api_key: '', api_secret: '', email: '', account_id: '', is_active: true };
const form = reactive({ ...defaultForm });

watch(() => form.auth_type, (newType) => {
    if (newType === 'global') { form.account_id = ''; } 
    else { form.email = ''; }
});

const fetchAccounts = async () => {
    loading.value = true;
    try { const r = await dnsAccountsApi.list(); accounts.value = r.data.data; }
    catch (e) { toast.error('Ошибка загрузки'); }
    finally { loading.value = false; }
};

const openCreateModal = () => {
    editingAccount.value = null;
    Object.assign(form, { ...defaultForm });
    showModal.value = true;
};

const editAccount = (account) => {
    editingAccount.value = account;
    const authType = account.email ? 'global' : 'token';
    Object.assign(form, { 
        name: account.name, 
        provider: account.provider, 
        auth_type: authType,
        api_key: '', 
        api_secret: '', 
        email: account.email || '', 
        account_id: account.account_id || '',
        is_active: account.is_active 
    });
    showModal.value = true;
};

const closeModal = () => { showModal.value = false; editingAccount.value = null; };

const saveAccount = async () => {
    saving.value = true;
    try {
        const data = { name: form.name, provider: form.provider, is_active: form.is_active };
        
        if (form.provider === 'cloudflare') {
            if (form.api_key) data.api_key = form.api_key;
            if (form.auth_type === 'global') { data.email = form.email; } 
            else { data.account_id = form.account_id; }
        }
        
        if (form.provider === 'dnspod') {
            if (form.api_key) data.api_key = form.api_key;
            if (form.api_secret) data.api_secret = form.api_secret;
        }
        
        if (editingAccount.value) { await dnsAccountsApi.update(editingAccount.value.id, data); toast.success('Аккаунт обновлён'); } 
        else { await dnsAccountsApi.create(data); toast.success('Аккаунт добавлен'); }
        closeModal(); fetchAccounts();
    } catch (e) { toast.error(e.response?.data?.message || 'Ошибка сохранения'); } 
    finally { saving.value = false; }
};

const deleteAccount = async (account) => {
    if (!confirm('Удалить "' + account.name + '"?')) return;
    try { await dnsAccountsApi.delete(account.id); toast.success('Удалён'); fetchAccounts(); } 
    catch (e) { toast.error('Ошибка'); }
};

const verifyAccount = async (a) => {
    try { await dnsAccountsApi.verify(a.id); toast.success('Соединение OK'); } 
    catch (e) { toast.error('Ошибка соединения'); }
};

const syncAccount = async (a) => {
    try { const r = await dnsAccountsApi.sync(a.id); toast.success('Синхр.: ' + r.data.data.synced + ' доменов'); fetchAccounts(); } 
    catch (e) { toast.error('Ошибка'); }
};

onMounted(fetchAccounts);
</script>
