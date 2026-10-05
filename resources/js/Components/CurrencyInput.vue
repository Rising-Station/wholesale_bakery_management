<script setup>
import InputNumber from 'primevue/inputnumber';

const props = defineProps({
    modelValue: {
        type: [Number, String, null],
        default: null,
    },
    placeholder: {
        type: String,
        default: '0.00',
    },
    min: {
        type: Number,
        default: 0,
    },
    max: {
        type: Number,
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    fluid: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <InputNumber
        :modelValue="modelValue"
        @update:modelValue="emit('update:modelValue', $event)"
        mode="currency"
        currency="BDT"
        currencyDisplay="code"
        locale="en-US"
        :minFractionDigits="2"
        :maxFractionDigits="2"
        :min="min"
        :max="max"
        :placeholder="placeholder"
        :disabled="disabled"
        :fluid="fluid"
        prefix="৳ "
        class="w-full"
        v-bind="$attrs"
    />
</template>
