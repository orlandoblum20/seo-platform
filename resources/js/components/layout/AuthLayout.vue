<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-600 to-primary-800 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-xl bg-white/10 backdrop-blur-sm mb-4">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white">SEO Platform</h1>
                <p class="text-primary-200 mt-1">Управление SEO-сайтами</p>
            </div>

            <!-- Content -->
            <div class="card">
                <div class="card-body">
                    <router-view />
                </div>
            </div>

            <!-- Footer -->
            <p class="text-center text-primary-200 text-sm mt-6">
                &copy; {{ new Date().getFullYear() }} SEO Landing Platform
            </p>
        </div>
    </div>
</template>

<script setup>
</script>
