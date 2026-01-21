<template>
    <div class="space-y-6">
        <div><h1 class="text-2xl font-bold text-dark-900">Настройки</h1><p class="text-dark-500">Общие настройки системы</p></div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <router-link to="/settings/ai" class="card card-body hover:border-primary-500 transition-colors">
                <div class="flex items-center gap-4"><div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center"><CpuChipIcon class="w-6 h-6 text-purple-600" /></div><div><p class="font-semibold">AI провайдеры</p><p class="text-sm text-dark-500">Claude, GPT-4</p></div></div>
            </router-link>
            <router-link to="/settings/keitaro" class="card card-body hover:border-primary-500 transition-colors">
                <div class="flex items-center gap-4"><div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center"><ChartBarIcon class="w-6 h-6 text-green-600" /></div><div><p class="font-semibold">Keitaro TDS</p><p class="text-sm text-dark-500">Трафик и фильтрация</p></div></div>
            </router-link>
            <router-link to="/settings/analytics" class="card card-body hover:border-primary-500 transition-colors">
                <div class="flex items-center gap-4"><div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center"><CodeBracketIcon class="w-6 h-6 text-blue-600" /></div><div><p class="font-semibold">Аналитика</p><p class="text-sm text-dark-500">Yandex, Google</p></div></div>
            </router-link>
        </div>
    </div>
</template>
<script setup>
import { CpuChipIcon, ChartBarIcon, CodeBracketIcon } from '@heroicons/vue/24/outline';
</script>
