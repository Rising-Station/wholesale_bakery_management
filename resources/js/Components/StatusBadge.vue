<script setup>
import { computed } from 'vue';
import Tag from 'primevue/tag';

const props = defineProps({
    value: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: '',
    },
});

const severity = computed(() => {
    const val = (props.value || '').toUpperCase();
    switch (val) {
        case 'PAID':
        case 'RECEIVED':
        case 'DELIVERED':
        case 'COMPLETED':
        case 'APPROVED':
            return 'success';
        case 'PARTIAL':
        case 'DISPATCHED':
        case 'DAMAGED':
            return 'warn';
        case 'DUE':
        case 'EXPIRED':
        case 'REDUCE':
            return 'danger';
        case 'ORDERED':
        case 'LOADED':
        case 'PENDING':
        case 'ADD':
            return 'info';
        case 'DRAFT':
        case 'CANCELLED':
        case 'RETURNED':
        default:
            return 'secondary';
    }
});

const displayLabel = computed(() => {
    if (props.label) return props.label;
    return (props.value || '').replace(/_/g, ' ');
});
</script>

<template>
    <Tag
        :value="displayLabel"
        :severity="severity"
        class="text-[11px] font-semibold uppercase tracking-wider px-2 py-0.5"
    />
</template>
