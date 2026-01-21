<template>
    <div class="space-y-6">
        <div><h1 class="text-2xl font-bold text-dark-900">Профиль</h1><p class="text-dark-500">Настройки аккаунта и безопасность</p></div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="card card-body space-y-4">
                <h3 class="font-semibold">Информация</h3>
                <div><label class="label">Имя</label><p class="text-dark-900">{{ authStore.user?.name }}</p></div>
                <div><label class="label">Email</label><p class="text-dark-900">{{ authStore.user?.email }}</p></div>
            </div>
            <div class="card card-body space-y-4">
                <h3 class="font-semibold">Двухфакторная аутентификация</h3>
                <div class="flex items-center gap-3"><span :class="authStore.user?.has_2fa ? 'badge-success' : 'badge-warning'">{{ authStore.user?.has_2fa ? '2FA включена' : '2FA выключена' }}</span></div>
                <button v-if="!authStore.user?.has_2fa" @click="setup2fa" class="btn-primary">Включить 2FA</button>
            </div>
            <div class="card card-body space-y-4">
                <h3 class="font-semibold">Смена пароля</h3>
                <div><label class="label">Текущий пароль</label><input v-model="passwordForm.current" type="password" class="input" /></div>
                <div><label class="label">Новый пароль</label><input v-model="passwordForm.new" type="password" class="input" /></div>
                <div><label class="label">Подтверждение</label><input v-model="passwordForm.confirm" type="password" class="input" /></div>
                <button @click="changePassword" :disabled="!canChangePassword" class="btn-primary">Изменить пароль</button>
            </div>
        </div>
    </div>
</template>
<script setup>
import { reactive, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToast } from 'vue-toastification';
const authStore = useAuthStore();
const toast = useToast();
const passwordForm = reactive({ current: '', new: '', confirm: '' });
const canChangePassword = computed(() => passwordForm.current && passwordForm.new && passwordForm.new === passwordForm.confirm && passwordForm.new.length >= 8);
const changePassword = async () => { try { await authStore.changePassword(passwordForm.current, passwordForm.new, passwordForm.confirm); toast.success('Пароль изменён'); passwordForm.current = ''; passwordForm.new = ''; passwordForm.confirm = ''; } catch(e) { toast.error('Ошибка'); } };
const setup2fa = async () => { toast.info('Функция в разработке'); };
</script>
