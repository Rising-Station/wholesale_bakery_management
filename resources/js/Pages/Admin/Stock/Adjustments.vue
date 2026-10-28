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

const form = useForm({
    adjustment_date: new Date().toISOString().split('T')[0],
    reason: 'Physical Count Verification',
    notes: '',
    items: [],
});

const openNewAuditModal = () => {
    form.reset();
    form.clearErrors();
    form.adjustment_date = new Date().toISOString().split('T')[0];
    form.reason = 'Physical Count Verification';
    form.items = [];
    addItemRow();
    showAuditModal.value = true;
};

const addItemRow = () => {
    form.items.push({
        product_id: null,
        system_stock: 0,
        physical_stock: 0,
    });
};

const removeItemRow = (index) => {
    form.items.splice(index, 1);
};

const onProductSelect = (index, productId) => {
    const product = props.products.find((p) => p.id === productId);
    if (product) {
        form.items[index].system_stock = Number(product.warehouse_stock);
        form.items[index].physical_stock = Number(product.warehouse_stock);
    }
};

const getVariance = (item) => {
    return Number((Number(item.physical_stock || 0) - Number(item.system_stock || 0)).toFixed(2));
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
        <Head title="Physical Stock Audit & Adjustments" />

        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                        <i class="pi pi-sliders-h text-amber-600"></i>
                        Physical Count Audits & Inventory Adjustments
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Conduct physical warehouse stocktaking, reconcile system count variances, and balance central inventory.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/stock">
                        <Button
                            label="Central Stock"
                            icon="pi pi-arrow-left"
                            severity="secondary"
                            outlined
                        />
                    </Link>
                    <Button
                        label="New Stock Audit Session"
                        icon="pi pi-plus"
                        severity="primary"
                        @click="openNewAuditModal"
                    />
                </div>
            </div>

            <!-- Adjustments Table -->
            <AppDataTable
                :items="adjustments.data || []"
                :total="adjustments.total || 0"
                :perPage="adjustments.per_page || 20"
                :currentPage="adjustments.current_page || 1"
                tableTitle="Historical Stock Audit Sessions"
            >
                <Column field="adjustment_no" header="Adjustment #" style="width: 170px">
                    <template #body="{ data }">
                        <span class="font-mono text-xs font-bold text-indigo-700 bg-indigo-50 px-2 py-1 rounded border border-indigo-100">
                            {{ data.adjustment_no }}
                        </span>
                    </template>
                </Column>

                <Column field="adjustment_date" header="Audit Date" style="width: 130px">
                    <template #body="{ data }">
                        <span class="text-xs text-slate-700 font-medium">
                            {{ formatDate(data.adjustment_date) }}
                        </span>
                    </template>
                </Column>

                <Column field="reason" header="Audit Reason">
                    <template #body="{ data }">
                        <div class="font-semibold text-slate-800">{{ data.reason }}</div>
                        <div v-if="data.notes" class="text-xs text-slate-400 mt-0.5 truncate max-w-sm">
                            {{ data.notes }}
                        </div>
                    </template>
                </Column>

                <Column header="Products Adjusted" style="width: 220px">
                    <template #body="{ data }">
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="item in data.items"
                                :key="item.id"
                                class="text-[11px] font-mono px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-700"
                            >
                                {{ item.product?.code }}:
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

            <!-- New Audit Session Modal -->
            <Dialog
                v-model:visible="showAuditModal"
                header="Conduct Physical Stock Count Audit"
                modal
                @hide="resetForm"
                :style="{ width: '750px' }"
                class="p-fluid"
            >
                <form @submit.prevent="submitAudit" class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Audit Date <span class="text-rose-500">*</span>
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
                                Reason / Audit Type <span class="text-rose-500">*</span>
                            </label>
                            <InputText
                                v-model="form.reason"
                                placeholder="e.g. Monthly Physical Verification, Shrinkage correction"
                                class="w-full text-sm"
                                :class="{ 'p-invalid': form.errors.reason }"
                            />
                            <small v-if="form.errors.reason" class="text-rose-500 text-xs">{{ form.errors.reason }}</small>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden mt-2">
                        <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-600">
                                Products to Reconcile
                            </span>
                            <Button
                                label="Add Product Line"
                                icon="pi pi-plus"
                                size="small"
                                severity="secondary"
                                text
                                @click="addItemRow"
                            />
                        </div>

                        <div class="p-3 space-y-3">
                            <div
                                v-for="(item, idx) in form.items"
                                :key="idx"
                                class="grid grid-cols-12 gap-2 items-center bg-white p-2.5 rounded-lg border border-slate-100 shadow-sm"
                            >
                                <div class="col-span-5">
                                    <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">Product</label>
                                    <Select
                                        v-model="item.product_id"
                                        :options="products"
                                        optionLabel="name"
                                        optionValue="id"
                                        placeholder="Select Product"
                                        class="w-full text-xs"
                                        @change="(e) => onProductSelect(idx, e.value)"
                                    />
                                </div>

                                <div class="col-span-2">
                                    <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">System Stock</label>
                                    <div class="font-mono text-xs font-bold text-slate-700 bg-slate-100 py-1.5 px-2 rounded text-center">
                                        {{ Number(item.system_stock).toFixed(0) }}
                                    </div>
                                </div>

                                <div class="col-span-2">
                                    <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">Counted Stock</label>
                                    <InputText
                                        v-model.number="item.physical_stock"
                                        type="number"
                                        min="0"
                                        class="w-full text-xs text-center py-1 font-bold"
                                    />
                                </div>

                                <div class="col-span-2 text-center">
                                    <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">Variance</label>
                                    <Tag
                                        :value="`${getVariance(item) >= 0 ? '+' : ''}${getVariance(item)}`"
                                        :severity="getVariance(item) === 0 ? 'secondary' : (getVariance(item) > 0 ? 'success' : 'danger')"
                                        rounded
                                    />
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
                            Additional Audit Notes
                        </label>
                        <Textarea
                            v-model="form.notes"
                            rows="2"
                            placeholder="Discrepancy explanation or supervisor comments..."
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
                            label="Confirm & Apply Adjustments"
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
