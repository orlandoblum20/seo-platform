<template>
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <router-link to="/domains" class="text-dark-500 hover:text-dark-700">
                <ArrowLeftIcon class="w-5 h-5" />
            </router-link>
            <div>
                <h1 class="text-2xl font-bold text-dark-900">Импорт доменов</h1>
                <p class="text-dark-500 mt-1">Добавьте домены списком</p>
            </div>
        </div>

        <div class="card card-body space-y-4">
            <div>
                <label class="label">DNS аккаунт</label>
                <select v-model="form.dns_account_id" class="input" required>
                    <option value="">Выберите аккаунт</option>
                    <option v-for="account in dnsAccounts" :key="account.id" :value="account.id">
                        {{ account.name }} ({{ account.provider }})
                    </option>
                </select>
            </div>

            <div>
                <label class="label">Домены (по одному на строку)</label>
                <textarea v-model="form.domains" rows="10" class="input font-mono text-sm"
                    placeholder="example.com&#10;another-domain.ru&#10;test-site.net"></textarea>
                <p class="text-sm text-dark-500 mt-1">
                    {{ domainCount }} доменов
                </p>
            </div>

            <div class="flex justify-end gap-3">
                <router-link to="/domains" class="btn-secondary">Отмена</router-link>
                <button @click="importDomains" :disabled="loading || !canSubmit" class="btn-primary">
                    <span v-if="loading">Импорт... ({{ progress }})</span>
                    <span v-else>Импортировать</span>
                </button>
            </div>
        </div>

        <!-- Results -->
        <div v-if="results" class="space-y-4">
            <!-- Summary -->
            <div class="card card-body">
                <h3 class="font-semibold text-dark-900 mb-4">Результаты импорта</h3>
                
                <div class="grid grid-cols-3 gap-4">
                    <div class="p-4 bg-green-50 rounded-lg text-center">
                        <p class="text-2xl font-semibold text-green-600">{{ results.summary?.added || 0 }}</p>
                        <p class="text-sm text-green-700">Добавлено</p>
                    </div>
                    <div class="p-4 bg-red-50 rounded-lg text-center">
                        <p class="text-2xl font-semibold text-red-600">{{ results.summary?.failed || 0 }}</p>
                        <p class="text-sm text-red-700">Ошибки</p>
                    </div>
                    <div class="p-4 bg-yellow-50 rounded-lg text-center">
                        <p class="text-2xl font-semibold text-yellow-600">{{ results.summary?.invalid || 0 }}</p>
                        <p class="text-sm text-yellow-700">Неверный формат</p>
                    </div>
                </div>
            </div>

            <!-- Success grouped by NS servers -->
            <div v-if="groupedByNs && Object.keys(groupedByNs).length" class="space-y-4">
                <div class="card card-body">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-dark-900">Добавленные домены по NS серверам</h3>
                        <button @click="copyAllGrouped" class="btn-secondary btn-sm">
                            <ClipboardIcon class="w-4 h-4 mr-1" />
                            Копировать всё
                        </button>
                    </div>
                    
                    <p class="text-sm text-dark-500 mb-4">
                        ⚠️ Пропишите указанные NS записи у вашего регистратора доменов. 
                        После этого вернитесь в раздел "Домены" и нажмите "Проверить NS" для активации.
                    </p>
                </div>

                <!-- NS Group -->
                <div v-for="(group, nsKey) in groupedByNs" :key="nsKey" class="card card-body">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h4 class="font-medium text-dark-800 mb-1">NS серверы:</h4>
                            <div class="font-mono text-sm text-primary-600 space-y-0.5">
                                <div v-for="ns in group.nameservers" :key="ns">{{ ns }}</div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="copyGroupNs(group)" class="btn-secondary btn-sm" title="Копировать NS">
                                <ClipboardIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div class="border-t border-dark-200 pt-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-dark-600">{{ group.domains.length }} доменов:</span>
                            <button @click="copyGroupDomains(group)" class="text-primary-600 hover:text-primary-700 text-sm flex items-center gap-1">
                                <ClipboardIcon class="w-4 h-4" />
                                Копировать домены
                            </button>
                        </div>
                        <div class="bg-dark-50 rounded p-3 max-h-40 overflow-y-auto">
                            <div class="font-mono text-sm text-dark-700 space-y-0.5">
                                <div v-for="domain in group.domains" :key="domain">{{ domain }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Errors -->
            <div v-if="results.failed?.length" class="card card-body">
                <h3 class="font-semibold text-red-700 mb-4">Ошибки</h3>
                <ul class="text-sm text-red-600 space-y-1">
                    <li v-for="(err, i) in results.failed" :key="i" class="flex gap-2">
                        <span class="font-medium">{{ err.domain }}:</span>
                        <span>{{ err.error }}</span>
                    </li>
                </ul>
            </div>

            <!-- Invalid format -->
            <div v-if="results.invalid_format?.length" class="card card-body">
                <h3 class="font-semibold text-yellow-700 mb-4">Неверный формат</h3>
                <ul class="text-sm text-yellow-600 space-y-1">
                    <li v-for="domain in results.invalid_format" :key="domain">{{ domain }}</li>
                </ul>
            </div>

            <div class="flex justify-end">
                <router-link to="/domains" class="btn-primary">
                    Перейти к доменам
                </router-link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { domainsApi, dnsAccountsApi } from '@/services/api';
import { useToast } from 'vue-toastification';
import { ArrowLeftIcon, ClipboardIcon } from '@heroicons/vue/24/outline';

const toast = useToast();
const loading = ref(false);
const progress = ref('');
const dnsAccounts = ref([]);
const results = ref(null);

const form = reactive({
    dns_account_id: '',
    domains: '',
});

const domainCount = computed(() => {
    return form.domains.split('\n').filter(d => d.trim()).length;
});

const canSubmit = computed(() => {
    return form.dns_account_id && domainCount.value > 0;
});

// Group successful domains by NS servers
const groupedByNs = computed(() => {
    if (!results.value?.success?.length) return null;
    
    const groups = {};
    
    for (const item of results.value.success) {
        const nsKey = (item.nameservers || []).sort().join('|') || 'unknown';
        
        if (!groups[nsKey]) {
            groups[nsKey] = {
                nameservers: item.nameservers || [],
                domains: [],
            };
        }
        groups[nsKey].domains.push(item.domain);
    }
    
    // Sort domains in each group
    for (const key of Object.keys(groups)) {
        groups[key].domains.sort();
    }
    
    return groups;
});

const fetchDnsAccounts = async () => {
    try {
        const response = await dnsAccountsApi.list({ active_only: true });
        dnsAccounts.value = response.data.data;
    } catch (error) {
        toast.error('Ошибка загрузки DNS аккаунтов');
    }
};

const importDomains = async () => {
    loading.value = true;
    results.value = null;
    progress.value = 'подготовка...';

    try {
        // Show estimated time based on domain count
        const count = domainCount.value;
        const estimatedSeconds = Math.ceil(count * 0.5); // 500ms per domain
        progress.value = `~${estimatedSeconds} сек`;

        const response = await domainsApi.import({
            dns_account_id: form.dns_account_id,
            domains: form.domains,
        });
        results.value = response.data.data;
        toast.success('Импорт завершён');
        
        // Clear form on success
        if (results.value.summary?.added > 0) {
            form.domains = '';
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Ошибка импорта');
    } finally {
        loading.value = false;
        progress.value = '';
    }
};

const copyToClipboard = (text) => {
    if (!navigator.clipboard) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        return;
    }
    navigator.clipboard.writeText(text);
};

const copyGroupNs = (group) => {
    if (group.nameservers?.length) {
        copyToClipboard(group.nameservers.join('\n'));
        toast.success('NS серверы скопированы');
    }
};

const copyGroupDomains = (group) => {
    if (group.domains?.length) {
        copyToClipboard(group.domains.join('\n'));
        toast.success(`${group.domains.length} доменов скопировано`);
    }
};

const copyAllGrouped = () => {
    if (!groupedByNs.value) return;
    
    const parts = [];
    for (const group of Object.values(groupedByNs.value)) {
        parts.push(`NS серверы:\n${group.nameservers.join('\n')}\n\nДомены:\n${group.domains.join('\n')}`);
    }
    
    copyToClipboard(parts.join('\n\n---\n\n'));
    toast.success('Все данные скопированы');
};

onMounted(() => {
    fetchDnsAccounts();
});
</script>
