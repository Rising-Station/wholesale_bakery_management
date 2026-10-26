<script setup>
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppDataTable from '@/Components/AppDataTable.vue';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';

const props = defineProps({
    movements: {
        type: Object,
        default: () => ({ data: [], total: 0 }),
    },
    products: {
        type: Array,
        default: () => [],
    },
    movementTypes: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ product_id: null, movement_type: null, date_from: '', date_to: '' }),
    },
});

const selectedProduct = ref(props.filters.product_id || null);
const selectedMovementType = ref(props.filters.movement_type || null);
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const filterData = () => {
    router.get(
        '/stock/movements',
        {
            product_id: selectedProduct.value || undefined,
            movement_type: selectedMovementType.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    selectedProduct.value = null;
    selectedMovementType.value = null;
    dateFrom.value = '';
    dateTo.value = '';
    filterData();
};

const getMovementSeverity = (type) => {
    const map = {
        PURCHASE_IN: 'success',
        PURCHASE_RETURN_SUPPLIER: 'danger',
        LOAD_TO_VEHICLE: 'warn',
        RETURN_FROM_VEHICLE: 'info',
        DIRECT_SALE: 'secondary',
        DAMAGE_RETURN: 'danger',
        EXCHANGE_RETURN: 'info',
        SCRAP_DISPOSAL: 'contrast',
        AUDIT_ADJUSTMENT: 'primary',
    };
    return map[type] || 'secondary';
};

const formatDate = (isoString) => {
    if (!isoString) return '-';
    const d = new Date(isoString);
    return d.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Stock Movement Audit Trail Ledger" />

        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                        <i class="pi pi-history text-indigo-600"></i>
                        Immutable Stock Movement Audit Ledger
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Cryptographic-style append-only ledger tracking every stock addition, vehicle dispatch, customer return, and physical audit variance.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/stock">
                        <Button
                            label="Back to Central Stock"
                            icon="pi pi-arrow-left"
                            severity="secondary"
                            outlined
                        />
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-wrap items-center gap-3">
                <div class="w-64">
                    <Select
                        v-model="selectedProduct"
                        :options="products"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="All Products"
                        showClear
                        class="w-full text-sm"
                        @change="filterData"
                    />
                </div>

                <div class="w-56">
                    <Select
                        v-model="selectedMovementType"
                        :options="movementTypes"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="All Movement Types"
                        showClear
                        class="w-full text-sm"
                        @change="filterData"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <InputText
                        v-model="dateFrom"
                        type="date"
                        class="text-sm py-2"
                        @change="filterData"
                    />
                    <span class="text-slate-400 text-xs font-semibold">to</span>
                    <InputText
                        v-model="dateTo"
                        type="date"
                        class="text-sm py-2"
                        @change="filterData"
                    />
                </div>

                <Button
                    icon="pi pi-filter"
                    label="Filter"
                    severity="primary"
                    outlined
                    @click="filterData"
                />

                <Button
                    icon="pi pi-refresh"
                    label="Reset"
                    severity="secondary"
                    text
                    @click="resetFilters"
                />
            </div>

            <!-- Audit Trail Table -->
            <AppDataTable
                :items="movements.data || []"
                :total="movements.total || 0"
                :perPage="movements.per_page || 30"
                :currentPage="movements.current_page || 1"
                tableTitle="Recorded Stock Movements"
            >
                <Column field="id" header="Entry #" style="width: 100px">
                    <template #body="{ data }">
                        <span class="font-mono text-xs font-semibold text-slate-500">
                            #{{ String(data.id).padStart(6, '0') }}
                        </span>
                    </template>
                </Column>

                <Column field="created_at" header="Timestamp" style="width: 160px">
                    <template #body="{ data }">
                        <span class="text-xs text-slate-600 font-mono">
                            {{ formatDate(data.created_at) }}
                        </span>
                    </template>
                </Column>

                <Column field="product.name" header="Product">
                    <template #body="{ data }">
                        <div>
                            <div class="font-bold text-slate-800">{{ data.product?.name || 'Deleted Product' }}</div>
                            <div class="font-mono text-[11px] text-slate-400">{{ data.product?.code }}</div>
                        </div>
                    </template>
                </Column>

                <Column field="movement_type" header="Movement Type" style="width: 170px">
                    <template #body="{ data }">
                        <Tag
                            :value="data.movement_type"
                            :severity="getMovementSeverity(data.movement_type)"
                            rounded
                            class="text-[11px]"
                        />
                    </template>
                </Column>

                <Column field="quantity" header="Quantity" style="width: 110px">
                    <template #body="{ data }">
                        <span class="font-bold text-sm text-slate-800">
                            {{ Number(data.quantity).toLocaleString() }}
                        </span>
                    </template>
                </Column>

                <Column header="Balance (Before → After)" style="width: 180px">
                    <template #body="{ data }">
                        <div class="text-xs font-mono flex items-center gap-1.5">
                            <span class="text-slate-500">{{ Number(data.balance_before).toLocaleString() }}</span>
                            <i class="pi pi-arrow-right text-[10px] text-slate-400"></i>
                            <span class="font-bold text-slate-800">{{ Number(data.balance_after).toLocaleString() }}</span>
                        </div>
                    </template>
                </Column>

                <Column field="creator.name" header="Logged By" style="width: 130px">
                    <template #body="{ data }">
                        <span class="text-xs text-slate-600">
                            {{ data.creator?.name || 'System Auto' }}
                        </span>
                    </template>
                </Column>

                <Column field="remarks" header="Remarks & Reference">
                    <template #body="{ data }">
                        <div class="text-xs text-slate-600 truncate max-w-xs" :title="data.remarks">
                            {{ data.remarks || '-' }}
                        </div>
                        <div v-if="data.reference_type" class="text-[10px] text-slate-400 font-mono mt-0.5">
                            Ref: {{ data.reference_type }} #{{ data.reference_id }}
                        </div>
                    </template>
                </Column>
            </AppDataTable>
        </div>
    </AppLayout>
</template>
