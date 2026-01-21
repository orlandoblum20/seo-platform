<template>
    <div class="flex h-screen bg-dark-50">
        <!-- Sidebar -->
        <aside class="hidden lg:flex lg:flex-shrink-0">
            <div class="flex flex-col w-64 border-r border-dark-200 bg-white">
                <!-- Logo -->
                <div class="flex items-center h-16 px-6 border-b border-dark-200">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                        </div>
                        <span class="font-semibold text-dark-900">SEO Platform</span>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto scrollbar-thin">
                    <router-link v-for="item in navigation" :key="item.name" :to="item.to"
                        :class="[isActive(item.to) ? 'sidebar-link-active' : 'sidebar-link-inactive', 'sidebar-link']">
                        <component :is="item.icon" class="w-5 h-5" />
                        {{ item.name }}
                    </router-link>

                    <!-- Divider -->
                    <div class="py-2">
                        <div class="border-t border-dark-200"></div>
                    </div>

                    <!-- Settings Section -->
                    <div class="pt-2">
                        <p class="px-3 text-xs font-semibold text-dark-400 uppercase tracking-wider mb-2">Настройки</p>
                        <router-link v-for="item in settingsNavigation" :key="item.name" :to="item.to"
                            :class="[isActive(item.to) ? 'sidebar-link-active' : 'sidebar-link-inactive', 'sidebar-link']">
                            <component :is="item.icon" class="w-5 h-5" />
                            {{ item.name }}
                        </router-link>
                    </div>
                </nav>

                <!-- User -->
                <div class="p-3 border-t border-dark-200">
                    <div class="flex items-center gap-3 px-3 py-2">
                        <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
                            <span class="text-sm font-medium text-primary-700">
                                {{ userInitials }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-dark-900 truncate">{{ authStore.user?.name }}</p>
                            <p class="text-xs text-dark-500 truncate">{{ authStore.user?.email }}</p>
                        </div>
                        <button @click="logout" class="p-1.5 text-dark-400 hover:text-dark-600 rounded-lg hover:bg-dark-100">
                            <ArrowRightOnRectangleIcon class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Mobile sidebar button -->
        <div class="lg:hidden fixed top-0 left-0 right-0 z-40 flex items-center h-16 px-4 bg-white border-b border-dark-200">
            <button @click="sidebarOpen = true" class="p-2 text-dark-600 hover:bg-dark-100 rounded-lg">
                <Bars3Icon class="w-6 h-6" />
            </button>
            <span class="ml-3 font-semibold text-dark-900">SEO Platform</span>
        </div>

        <!-- Mobile sidebar -->
        <TransitionRoot :show="sidebarOpen" as="template">
            <Dialog as="div" class="relative z-50 lg:hidden" @close="sidebarOpen = false">
                <TransitionChild as="template" enter="transition-opacity ease-linear duration-300" enter-from="opacity-0"
                    enter-to="opacity-100" leave="transition-opacity ease-linear duration-300" leave-from="opacity-100"
                    leave-to="opacity-0">
                    <div class="fixed inset-0 bg-dark-900/80" />
                </TransitionChild>

                <div class="fixed inset-0 flex">
                    <TransitionChild as="template" enter="transition ease-in-out duration-300 transform"
                        enter-from="-translate-x-full" enter-to="translate-x-0"
                        leave="transition ease-in-out duration-300 transform" leave-from="translate-x-0"
                        leave-to="-translate-x-full">
                        <DialogPanel class="relative mr-16 flex w-full max-w-xs flex-1">
                            <div class="flex grow flex-col bg-white">
                                <!-- Mobile nav content - same as desktop -->
                                <div class="flex items-center h-16 px-6 border-b border-dark-200">
                                    <span class="font-semibold text-dark-900">SEO Platform</span>
                                </div>
                                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                                    <router-link v-for="item in [...navigation, ...settingsNavigation]" :key="item.name"
                                        :to="item.to" @click="sidebarOpen = false"
                                        :class="[isActive(item.to) ? 'sidebar-link-active' : 'sidebar-link-inactive', 'sidebar-link']">
                                        <component :is="item.icon" class="w-5 h-5" />
                                        {{ item.name }}
                                    </router-link>
                                </nav>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Main content -->
        <main class="flex-1 overflow-y-auto">
            <div class="lg:py-6 lg:px-8 pt-20 lg:pt-6 px-4 pb-6">
                <router-view />
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue';
import {
    HomeIcon,
    GlobeAltIcon,
    DocumentDuplicateIcon,
    RectangleStackIcon,
    LinkIcon,
    DocumentTextIcon,
    ServerIcon,
    CloudIcon,
    Cog6ToothIcon,
    CpuChipIcon,
    ChartBarIcon,
    CodeBracketIcon,
    Bars3Icon,
    ArrowRightOnRectangleIcon,
} from '@heroicons/vue/24/outline';

const route = useRoute();
const authStore = useAuthStore();
const sidebarOpen = ref(false);

const navigation = [
    { name: 'Дашборд', to: '/', icon: HomeIcon },
    { name: 'Домены', to: '/domains', icon: GlobeAltIcon },
    { name: 'Сайты', to: '/sites', icon: DocumentDuplicateIcon },
    { name: 'Шаблоны', to: '/templates', icon: RectangleStackIcon },
    { name: 'Бэклинки', to: '/backlinks', icon: LinkIcon },
    { name: 'Посты', to: '/posts', icon: DocumentTextIcon },
];

const settingsNavigation = [
    { name: 'DNS аккаунты', to: '/dns-accounts', icon: CloudIcon },
    { name: 'Серверы', to: '/servers', icon: ServerIcon },
    { name: 'AI провайдеры', to: '/settings/ai', icon: CpuChipIcon },
    { name: 'Keitaro', to: '/settings/keitaro', icon: ChartBarIcon },
    { name: 'Аналитика', to: '/settings/analytics', icon: CodeBracketIcon },
    { name: 'Настройки', to: '/settings', icon: Cog6ToothIcon },
];

const userInitials = computed(() => {
    const name = authStore.user?.name || '';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

const isActive = (to) => {
    if (to === '/') return route.path === '/';
    return route.path.startsWith(to);
};

const logout = () => {
    authStore.logout();
};
</script>
