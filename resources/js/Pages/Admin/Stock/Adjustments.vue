<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppDataTable from '@/Components/AppDataTable.vue';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    adjustments: {
        type: Object,
        default: () => ({ data: [], total: 0 }),
    },
    products: {
        type: Array,
        default: () => [],
    },
});

const showAuditModal = ref(false);

const actionOptions = [
    { label: 'Stock In (+)', value: 'IN' },
    { label: 'Stock Out (-)', value: 'OUT' },
    { label: 'Physical Count (=)', value: 'COUNT' },
];

const reasonPresets = [
    'Stock In: Found Stock / Surplus',
    'Stock Out: Spoiled / Damaged / Scrap',
    'Stock Out: Sampling & Tasting',
    'Physical Audit: Routine Count Check',
];

const form = useForm({
    adjustment_date: new Date().toISOString().split('T')[0],
    reason: 'Stock In: Surplus / Return Inward',
    notes: '',
    items: [],
});

const openNewAuditModal = (defaultAction = 'IN', defaultReason = null) => {
    form.reset();
    form.clearErrors();
    form.adjustment_date = new Date().toISOString().split('T')[0];
    if (defaultReason) {
        form.reason = defaultReason;
    } else if (defaultAction === 'IN') {
        form.reason = 'Stock In: Surplus / Return Inward';
    } else if (defaultAction === 'OUT') {
        form.reason = 'Stock Out: Damage / Spillage / Expired';
    } else {
        form.reason = 'Physical Count Verification';
    }
    form.items = [];
    addItemRow(defaultAction);
    showAuditModal.value = true;
};

const addItemRow = (defaultAction = 'IN') => {
    form.items.push({
        product_id: null,
        action_type: defaultAction,
        system_stock: 0,
        input_qty: 0,
        physical_stock: 0,
        variance: 0,
    });
};

const removeItemRow = (index) => {
    form.items.splice(index, 1);
};

const onProductSelect = (index, productId) => {
    const product = props.products.find((p) => p.id === productId);
    if (product) {
        form.items[index].system_stock = Number(product.warehouse_stock);
        if (form.items[index].action_type === 'COUNT') {
            form.items[index].input_qty = Number(product.warehouse_stock);
        } else if (!form.items[index].input_qty || form.items[index].input_qty === 0) {
            form.items[index].input_qty = 1;
        }
        recalculateItem(form.items[index]);
    }
};

const recalculateItem = (item) => {
    const sys = Number(item.system_stock || 0);
    const qty = Number(item.input_qty || 0);

    if (item.action_type === 'IN') {
        item.physical_stock = Math.round((sys + qty) * 100) / 100;
        item.variance = qty;
    } else if (item.action_type === 'OUT') {
        item.physical_stock = Math.max(0, Math.round((sys - qty) * 100) / 100);
        item.variance = -Math.min(sys, qty);
    } else {
        item.physical_stock = qty;
        item.variance = Math.round((qty - sys) * 100) / 100;
    }
};

const resetForm = () => {
    form.reset();
    form.clearErrors();
    form.items = [];
    showAuditModal.value = false;
};

const submitAudit = () => {
    form.post('/stock-adjustments', {
        onSuccess: () => {
            resetForm();
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Stock In/Out & Inventory Adjustments" />

        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                        <i class="pi pi-sliders-h text-amber-600"></i>
                        Stock In/Out & Inventory Adjustments
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Record direct stock inward (+), stock deduction/spoilage (-), or physical stocktaking counts to balance central warehouse inventory.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link href="/stock">
                        <Button
                            label="Central Stock"
                            icon="pi pi-arrow-left"
                            severity="secondary"
                            outlined
                        />
                    </Link>
                    <Button
                        label="Stock In (+)"
                        icon="pi pi-plus-circle"
                        severity="success"
                        @click="openNewAuditModal('IN')"
                    />
                    <Button
                        label="Stock Out (-)"
                        icon="pi pi-minus-circle"
                        severity="danger"
                        @click="openNewAuditModal('OUT')"
                    />
                    <Button
                        label="Physical Count (=)"
                        icon="pi pi-calculator"
                        severity="primary"
                        @click="openNewAuditModal('COUNT')"
                    />
                </div>
            </div>

            <!-- Adjustments Table -->
            <AppDataTable
                :items="adjustments.data || []"
                :total="adjustments.total || 0"
                :perPage="adjustments.per_page || 20"
                :currentPage="adjustments.current_page || 1"
                tableTitle="Historical Stock Adjustments & Movements"
            >
                <Column field="adjustment_no" header="Adjustment #" style="width: 170px">
                    <template #body="{ data }">
                        <span class="font-mono text-xs font-bold text-indigo-700 bg-indigo-50 px-2 py-1 rounded border border-indigo-100">
                            {{ data.adjustment_no }}
                        </span>
                    </template>
                </Column>

                <Column field="adjustment_date" header="Adjustment Date" style="width: 140px">
                    <template #body="{ data }">
                        <span class="text-xs text-slate-700 font-medium">
                            {{ formatDate(data.adjustment_date) }}
                        </span>
                    </template>
                </Column>

                <Column field="reason" header="Adjustment Reason">
                    <template #body="{ data }">
                        <div class="font-semibold text-slate-800 text-xs">{{ data.reason }}</div>
                        <div v-if="data.notes" class="text-xs text-slate-400 mt-0.5 truncate max-w-sm">
                            {{ data.notes }}
                        </div>
                    </template>
                </Column>

                <Column header="Products Adjusted" style="width: 260px">
                    <template #body="{ data }">
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="item in data.items"
                                :key="item.id"
                                class="text-[11px] font-mono px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-700 inline-flex items-center gap-1"
                            >
                                <span>{{ item.product?.code }}:</span>
                                <span :class="item.adjustment_type === 'ADD' ? 'text-emerald-600 font-bold' : 'text-rose-600 font-bold'">
                                    {{ item.adjustment_type === 'ADD' ? '+' : '-' }}{{ Number(item.variance_quantity).toFixed(0) }}
                                </span>
                            </span>
                        </div>
                    </template>
                </Column>

                <Column field="status" header="Status" style="width: 110px">
                    <template #body="{ data }">
                        <Tag
                            :value="data.status"
                            :severity="data.status === 'APPROVED' ? 'success' : 'warn'"
                            rounded
                        />
                    </template>
                </Column>

                <Column field="creator.name" header="Conducted By" style="width: 140px">
                    <template #body="{ data }">
                        <span class="text-xs text-slate-600">
                            {{ data.creator?.name || 'Staff' }}
                        </span>
                    </template>
                </Column>
            </AppDataTable>

            <!-- New Stock In / Out / Adjustment Modal -->
            <Dialog
                v-model:visible="showAuditModal"
                header="Stock In / Out & Inventory Adjustment"
                modal
                @hide="resetForm"
                :style="{ width: '820px' }"
                class="p-fluid"
            >
                <form @submit.prevent="submitAudit" class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Adjustment Date <span class="text-rose-500">*</span>
                            </label>
                            <InputText
                                v-model="form.adjustment_date"
                                type="date"
                                class="w-full text-sm"
                                :class="{ 'p-invalid': form.errors.adjustment_date }"
                            />
                            <small v-if="form.errors.adjustment_date" class="text-rose-500 text-xs">{{ form.errors.adjustment_date }}</small>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Reason / Operation Type <span class="text-rose-500">*</span>
                            </label>
                            <InputText
                                v-model="form.reason"
                                placeholder="e.g. Stock In (Surplus), Stock Out (Spoiled), Physical Count"
                                class="w-full text-sm"
                                :class="{ 'p-invalid': form.errors.reason }"
                            />
                            <div class="flex flex-wrap gap-1.5 mt-1.5">
                                <button
                                    v-for="preset in reasonPresets"
                                    :key="preset"
                                    type="button"
                                    @click="form.reason = preset"
                                    class="text-[10px] px-2 py-0.5 rounded-full border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-600 transition"
                                >
                                    {{ preset }}
                                </button>
                            </div>
                            <small v-if="form.errors.reason" class="text-rose-500 text-xs">{{ form.errors.reason }}</small>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden mt-2">
                        <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-600">
                                Products to Adjust
                            </span>
                            <div class="flex items-center gap-1.5">
                                <Button
                                    label="Add In (+)"
                                    icon="pi pi-plus"
                                    size="small"
                                    severity="success"
                                    text
                                    @click="addItemRow('IN')"
                                />
                                <Button
                                    label="Add Out (-)"
                                    icon="pi pi-minus"
                                    size="small"
                                    severity="danger"
                                    text
                                    @click="addItemRow('OUT')"
                                />
                                <Button
                                    label="Add Count (=)"
                                    icon="pi pi-calculator"
                                    size="small"
                                    severity="secondary"
                                    text
                                    @click="addItemRow('COUNT')"
                                />
                            </div>
                        </div>

                        <div class="p-3 space-y-3">
                            <div
                                v-for="(item, idx) in form.items"
                                :key="idx"
                                class="grid grid-cols-12 gap-2 items-center bg-white p-2.5 rounded-lg border border-slate-100 shadow-sm"
                            >
                                <div class="col-span-4">
                                    <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">Product</label>
                                    <Select
                                        v-model="item.product_id"
                                        :options="products"
                                        optionLabel="name"
                                        optionValue="id"
                                        placeholder="Select Product"
                                        filter
                                        class="w-full text-xs"
                                        @change="(e) => onProductSelect(idx, e.value)"
                                    />
                                </div>

                                <div class="col-span-3">
                                    <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">Operation</label>
                                    <Select
                                        v-model="item.action_type"
                                        :options="actionOptions"
                                        optionLabel="label"
                                        optionValue="value"
                                        filter
                                        class="w-full text-xs"
                                        @change="recalculateItem(item)"
                                    />
                                </div>

                                <div class="col-span-2">
                                    <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">
                                        {{ item.action_type === 'COUNT' ? 'Counted' : (item.action_type === 'IN' ? '+ Quantity' : '- Quantity') }}
                                    </label>
                                    <InputText
                                        v-model.number="item.input_qty"
                                        type="number"
                                        min="0"
                                        step="any"
                                        class="w-full text-xs text-center py-1 font-bold"
                                        @input="recalculateItem(item)"
                                    />
                                </div>

                                <div class="col-span-2 text-center">
                                    <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">New Stock / Var</label>
                                    <div class="text-[11px] font-mono font-bold text-slate-800 bg-slate-50 py-1.5 px-1 rounded border border-slate-200">
                                        {{ item.physical_stock }}
                                        <span :class="item.variance >= 0 ? 'text-emerald-600 font-bold ml-1' : 'text-rose-600 font-bold ml-1'">
                                            ({{ item.variance >= 0 ? '+' : '' }}{{ item.variance }})
                                        </span>
                                    </div>
                                </div>

                                <div class="col-span-1 text-right">
                                    <Button
                                        icon="pi pi-times"
                                        severity="danger"
                                        text
                                        rounded
                                        size="small"
                                        :disabled="form.items.length <= 1"
                                        @click="removeItemRow(idx)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Additional Adjustment Notes
                        </label>
                        <Textarea
                            v-model="form.notes"
                            rows="2"
                            placeholder="Reason details, authorization notes, or supervisor explanation..."
                            class="w-full text-sm"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                        <Button
                            label="Cancel"
                            severity="secondary"
                            text
                            @click="showAuditModal = false"
                        />
                        <Button
                            label="Apply Stock Adjustments"
                            icon="pi pi-check"
                            type="submit"
                            severity="primary"
                            :loading="form.processing"
                        />
                    </div>
                </form>
            </Dialog>
        </div>
    </AppLayout>
</template>
