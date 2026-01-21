<template>
    <div>
        <h2 class="text-xl font-semibold text-dark-900 text-center mb-2">Двухфакторная аутентификация</h2>
        <p class="text-dark-500 text-center text-sm mb-6">Введите код из приложения аутентификации</p>

        <form @submit.prevent="handleVerify" class="space-y-4">
            <div>
                <label for="code" class="label">Код подтверждения</label>
                <input id="code" v-model="code" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                    required autofocus class="input text-center text-2xl tracking-widest font-mono"
                    :class="{ 'input-error': error }" placeholder="000000" />
                <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
            </div>

            <button type="submit" :disabled="loading || code.length !== 6" class="btn-primary w-full">
                <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ loading ? 'Проверка...' : 'Подтвердить' }}
            </button>

            <button type="button" @click="backToLogin" class="btn-secondary w-full">
                Назад к входу
            </button>
        </form>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const code = ref('');
const loading = ref(false);
const error = ref('');

const handleVerify = async () => {
    error.value = '';
    loading.value = true;

    try {
        await authStore.verify2fa(code.value);
        router.push({ name: 'dashboard' });
    } catch (e) {
        error.value = typeof e === 'string' ? e : 'Неверный код';
        code.value = '';
    } finally {
        loading.value = false;
    }
};

const backToLogin = () => {
    authStore.tempToken = null;
    authStore.requires2fa = false;
    localStorage.removeItem('temp_token');
    router.push({ name: 'login' });
};
</script>
