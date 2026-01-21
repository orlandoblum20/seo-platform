import { defineStore } from 'pinia';
import { authApi } from '@/services/api';
import router from '@/router';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('token') || null,
        tempToken: null, // For 2FA flow
        requires2fa: false,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token && !!state.user,
        has2fa: (state) => state.user?.has_2fa || false,
    },

    actions: {
        async login(email, password) {
            try {
                const response = await authApi.login(email, password);
                const data = response.data;

                if (data.requires_2fa) {
                    this.tempToken = data.temp_token;
                    this.requires2fa = true;
                    localStorage.setItem('temp_token', data.temp_token);
                    return { requires2fa: true };
                }

                this.setAuth(data.token, data.user);
                return { success: true };
            } catch (error) {
                throw error.response?.data?.message || 'Ошибка авторизации';
            }
        },

        async verify2fa(code) {
            try {
                // Use temp token for this request
                const token = this.tempToken || localStorage.getItem('temp_token');
                if (!token) throw new Error('Сессия истекла');

                const response = await authApi.verify2fa(code);
                const data = response.data;

                this.setAuth(data.token, data.user);
                this.tempToken = null;
                this.requires2fa = false;
                localStorage.removeItem('temp_token');

                return { success: true };
            } catch (error) {
                throw error.response?.data?.message || 'Неверный код';
            }
        },

        setAuth(token, user) {
            this.token = token;
            this.user = user;
            localStorage.setItem('token', token);
        },

        async fetchUser() {
            try {
                const response = await authApi.me();
                this.user = response.data.user;
            } catch (error) {
                this.logout();
                throw error;
            }
        },

        async logout() {
            try {
                if (this.token) {
                    await authApi.logout();
                }
            } catch (e) {
                // Ignore errors during logout
            }

            this.user = null;
            this.token = null;
            this.tempToken = null;
            this.requires2fa = false;
            localStorage.removeItem('token');
            localStorage.removeItem('temp_token');

            router.push({ name: 'login' });
        },

        async setup2fa() {
            const response = await authApi.setup2fa();
            return response.data;
        },

        async enable2fa(code) {
            const response = await authApi.enable2fa(code);
            this.user.has_2fa = true;
            return response.data;
        },

        async disable2fa(code, password) {
            const response = await authApi.disable2fa(code, password);
            this.user.has_2fa = false;
            return response.data;
        },

        async changePassword(currentPassword, newPassword, newPasswordConfirmation) {
            const response = await authApi.changePassword({
                current_password: currentPassword,
                new_password: newPassword,
                new_password_confirmation: newPasswordConfirmation,
            });
            return response.data;
        },
    },
});
