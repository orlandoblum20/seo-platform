<template>
    <div>
        <h2 class="text-xl font-semibold text-dark-900 text-center mb-6">Вход в систему</h2>

        <form @submit.prevent="handleLogin" class="space-y-4">
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" v-model="form.email" type="email" required autocomplete="email"
                    class="input" :class="{ 'input-error': errors.email }" placeholder="admin@example.com" />
                <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
            </div>

            <div>
                <label for="password" class="label">Пароль</label>
                <input id="password" v-model="form.password" type="password" required autocomplete="current-password"
                    class="input" :class="{ 'input-error': errors.password }" placeholder="••••••••" />
                <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
            </div>

            <div v-if="errorMessage" class="p-3 rounded-lg bg-red-50 border border-red-200">
                <p class="text-sm text-red-700">{{ errorMessage }}</p>
            </div>

            <button type="submit" :disabled="loading" class="btn-primary w-full">
                <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ loading ? 'Вход...' : 'Войти' }}
            </button>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
    email: '',
    password: '',
});

const errors = reactive({
    email: '',
    password: '',
});

const loading = ref(false);
const errorMessage = ref('');

const handleLogin = async () => {
    // Reset errors
    errors.email = '';
    errors.password = '';
    errorMessage.value = '';

    // Validate
    if (!form.email) {
        errors.email = 'Введите email';
        return;
    }
    if (!form.password) {
        errors.password = 'Введите пароль';
        return;
    }

    loading.value = true;

    try {
        const result = await authStore.login(form.email, form.password);

        if (result.requires2fa) {
            router.push({ name: '2fa' });
        } else {
            router.push({ name: 'dashboard' });
        }
    } catch (error) {
        errorMessage.value = typeof error === 'string' ? error : 'Ошибка авторизации';
    } finally {
        loading.value = false;
    }
};
</script>
