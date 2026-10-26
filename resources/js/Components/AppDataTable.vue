<script setup>
import { ref, computed } from 'vue';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Button from 'primevue/button';

const props = defineProps({
    value: {
        type: Array,
        default: () => [],
    },
    items: {
        type: Array,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    rows: {
        type: Number,
        default: 10,
    },
    rowsPerPageOptions: {
        type: Array,
        default: () => [10, 20, 50, 100],
    },
    title: {
        type: String,
        default: '',
    },
    tableTitle: {
        type: String,
        default: '',
    },
    searchPlaceholder: {
        type: String,
        default: 'Search records...',
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const tableData = computed(() => {
    if (props.items !== null && props.items !== undefined) {
        return props.items;
    }
    return props.value || [];
});

const displayTitle = computed(() => props.tableTitle || props.title || '');

const emit = defineEmits(['export']);
const dt = ref();

const exportCSV = () => {
    if (dt.value) {
        dt.value.exportCSV();
    }
};
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
        <DataTable
            ref="dt"
            :value="tableData"
            :loading="loading"
            :paginator="true"
            :rows="rows"
            :rowsPerPageOptions="rowsPerPageOptions"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} records"
            responsiveLayout="scroll"
            stripedRows
            v-bind="$attrs"
            class="p-datatable-sm"
        >
            <template #header>
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-2">
                    <div class="flex items-center gap-2">
                        <slot name="header-left">
                            <h3 v-if="displayTitle" class="font-bold text-slate-800 text-base">
                                {{ displayTitle }}
                            </h3>
                        </slot>
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <slot name="header-actions" />

                        <Button
                            type="button"
                            icon="pi pi-download"
                            label="Export"
                            severity="secondary"
                            outlined
                            size="small"
                            @click="exportCSV"
                            class="!rounded-xl text-xs"
                        />
                    </div>
                </div>
            </template>

            <template #empty>
                <div class="py-12 text-center text-slate-400">
                    <i class="pi pi-inbox text-3xl mb-2 block"></i>
                    <p class="text-sm font-medium">No records found matching criteria</p>
                </div>
            </template>

            <template #loading>
                <div class="py-8 text-center text-slate-500">
                    <i class="pi pi-spin pi-spinner text-2xl mb-2 block text-amber-500"></i>
                    <p class="text-sm">Loading data from server...</p>
                </div>
            </template>

            <slot />
        </DataTable>
    </div>
</template>
