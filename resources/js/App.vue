<template>
    <div class="min-h-screen">
        <router-view />
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

onMounted(async () => {
    // Check if user is authenticated on app load
    if (authStore.token) {
        try {
            await authStore.fetchUser();
        } catch (error) {
            authStore.logout();
        }
    }
});
</script>
