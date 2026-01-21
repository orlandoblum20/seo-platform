<template>
    <div class="flex items-center justify-center" :class="containerClass">
        <svg class="animate-spin text-primary-600" :class="sizeClass" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span v-if="text" class="ml-2 text-dark-600">{{ text }}</span>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    size: { type: String, default: 'md' }, // sm, md, lg
    text: { type: String, default: '' },
    fullScreen: { type: Boolean, default: false },
});

const sizeClass = computed(() => {
    const sizes = {
        sm: 'w-4 h-4',
        md: 'w-6 h-6',
        lg: 'w-10 h-10',
    };
    return sizes[props.size] || sizes.md;
});

const containerClass = computed(() => {
    return props.fullScreen ? 'fixed inset-0 bg-white/80 z-50' : '';
});
</script>
