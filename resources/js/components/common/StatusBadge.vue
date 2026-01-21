<template>
    <span :class="badgeClass">{{ label }}</span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
    type: { type: String, default: 'site' }, // site, domain, post, ssl
});

const statusConfig = {
    site: {
        draft: { class: 'badge-gray', label: 'Черновик' },
        generating: { class: 'badge-info', label: 'Генерация...' },
        generated: { class: 'badge-warning', label: 'Сгенерирован' },
        publishing: { class: 'badge-info', label: 'Публикация...' },
        published: { class: 'badge-success', label: 'Опубликован' },
        unpublished: { class: 'badge-gray', label: 'Снят' },
        error: { class: 'badge-danger', label: 'Ошибка' },
    },
    domain: {
        pending: { class: 'badge-warning', label: 'Ожидание' },
        dns_configuring: { class: 'badge-info', label: 'DNS...' },
        ssl_pending: { class: 'badge-info', label: 'SSL...' },
        active: { class: 'badge-success', label: 'Активен' },
        error: { class: 'badge-danger', label: 'Ошибка' },
        suspended: { class: 'badge-danger', label: 'Приостановлен' },
    },
    post: {
        draft: { class: 'badge-gray', label: 'Черновик' },
        generating: { class: 'badge-info', label: 'Генерация...' },
        scheduled: { class: 'badge-warning', label: 'Запланирован' },
        published: { class: 'badge-success', label: 'Опубликован' },
        error: { class: 'badge-danger', label: 'Ошибка' },
    },
    ssl: {
        none: { class: 'badge-gray', label: 'Нет' },
        pending: { class: 'badge-warning', label: 'Ожидание' },
        active: { class: 'badge-success', label: 'Активен' },
        error: { class: 'badge-danger', label: 'Ошибка' },
    },
    health: {
        ok: { class: 'badge-success', label: 'OK' },
        warning: { class: 'badge-warning', label: 'Предупреждение' },
        error: { class: 'badge-danger', label: 'Ошибка' },
        unknown: { class: 'badge-gray', label: 'Неизвестно' },
    },
};

const config = computed(() => {
    const typeConfig = statusConfig[props.type] || statusConfig.site;
    return typeConfig[props.status] || { class: 'badge-gray', label: props.status };
});

const badgeClass = computed(() => config.value.class);
const label = computed(() => config.value.label);
</script>
