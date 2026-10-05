<script setup>
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirmation',
    },
    message: {
        type: String,
        default: 'Are you sure you want to proceed?',
    },
    confirmLabel: {
        type: String,
        default: 'Confirm',
    },
    cancelLabel: {
        type: String,
        default: 'Cancel',
    },
    severity: {
        type: String,
        default: 'danger', // danger, warn, info, primary
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:visible', 'confirm', 'cancel']);

const handleConfirm = () => {
    emit('confirm');
};

const handleCancel = () => {
    emit('update:visible', false);
    emit('cancel');
};
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="emit('update:visible', $event)"
        :modal="true"
        :closable="!loading"
        class="w-full max-w-md"
        :header="title"
    >
        <div class="flex items-start gap-4 py-2">
            <div
                :class="[
                    'w-10 h-10 rounded-full flex items-center justify-center shrink-0',
                    severity === 'danger' ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600',
                ]"
            >
                <i :class="['pi text-lg', severity === 'danger' ? 'pi-exclamation-circle' : 'pi-question-circle']"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm text-slate-600 leading-relaxed">
                    {{ message }}
                </p>
                <slot />
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2 pt-2">
                <Button
                    type="button"
                    :label="cancelLabel"
                    severity="secondary"
                    outlined
                    size="small"
                    :disabled="loading"
                    @click="handleCancel"
                    class="!rounded-xl text-xs"
                />
                <Button
                    type="button"
                    :label="confirmLabel"
                    :severity="severity"
                    size="small"
                    :loading="loading"
                    @click="handleConfirm"
                    class="!rounded-xl text-xs"
                />
            </div>
        </template>
    </Dialog>
</template>
