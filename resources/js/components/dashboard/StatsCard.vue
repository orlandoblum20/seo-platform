<template>
    <div class="card">
        <div class="card-body flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg flex items-center justify-center" :class="bgColorClass">
                <component :is="iconComponent" class="w-6 h-6" :class="iconColorClass" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-dark-500">{{ title }}</p>
                <p class="text-2xl font-semibold text-dark-900">{{ formattedValue }}</p>
                <p v-if="subtitle" class="text-sm text-dark-400 mt-0.5">{{ subtitle }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import {
    GlobeAltIcon,
    DocumentDuplicateIcon,
    DocumentTextIcon,
    ClockIcon,
    UserGroupIcon,
    CurrencyDollarIcon,
    ChartBarIcon,
    LinkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    title: { type: String, required: true },
    value: { type: [Number, String], required: true },
    subtitle: { type: String, default: '' },
    icon: { type: String, default: 'ChartBarIcon' },
    color: { type: String, default: 'blue' },
});

const icons = {
    GlobeAltIcon,
    DocumentDuplicateIcon,
    DocumentTextIcon,
    ClockIcon,
    UserGroupIcon,
    CurrencyDollarIcon,
    ChartBarIcon,
    LinkIcon,
};

const iconComponent = computed(() => icons[props.icon] || ChartBarIcon);

const colorClasses = {
    blue: { bg: 'bg-blue-100', icon: 'text-blue-600' },
    green: { bg: 'bg-green-100', icon: 'text-green-600' },
    purple: { bg: 'bg-purple-100', icon: 'text-purple-600' },
    orange: { bg: 'bg-orange-100', icon: 'text-orange-600' },
    red: { bg: 'bg-red-100', icon: 'text-red-600' },
    gray: { bg: 'bg-dark-100', icon: 'text-dark-600' },
};

const bgColorClass = computed(() => colorClasses[props.color]?.bg || colorClasses.blue.bg);
const iconColorClass = computed(() => colorClasses[props.color]?.icon || colorClasses.blue.icon);

const formattedValue = computed(() => {
    if (typeof props.value === 'number') {
        return props.value.toLocaleString('ru-RU');
    }
    return props.value;
});
</script>
