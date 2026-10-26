<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppDataTable from '@/Components/AppDataTable.vue';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import CurrencyInput from '@/Components/CurrencyInput.vue';

const props = defineProps({
    purchases: {
        type: Object,
        default: () => ({ data: [], total: 0 }),
    },
    suppliers: {
        type: Array,
        default: () => [],
    },
    products: {
        type: Array,
        default: () => [],
    },
    paymentMethods: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ supplier_id: null, search: '', date_from: '', date_to: '' }),
    },
});

const searchQuery = ref(props.filters.search || '');
const selectedSupplierFilter = ref(props.filters.supplier_id || null);
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const showPurchaseModal = ref(false);

const form = useForm({
    supplier_id: null,
    purchase_date: new Date().toISOString().split('T')[0],
    discount: 0,
    paid_amount: 0,
    payment_method: 'CASH',
    notes: '',
    items: [],
});

const selectedSupplierInfo = computed(() => {
    return props.suppliers.find((s) => s.id === form.supplier_id) || null;
});

const computedSubtotal = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + (Number(item.quantity || 0) * Number(item.unit_cost || 0));
    }, 0);
});

const computedGrandTotal = computed(() => {
    return Math.max(0, computedSubtotal.value - Number(form.discount || 0));
});

const computedRemainingDue = computed(() => {
    return Math.max(0, computedGrandTotal.value - Number(form.paid_amount || 0));
});

const filterData = () => {
    router.get(
        '/purchases',
        {
            search: searchQuery.value || undefined,
            supplier_id: selectedSupplierFilter.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    form.purchase_date = new Date().toISOString().split('T')[0];
    form.payment_method = 'CASH';
    form.discount = 0;
    form.paid_amount = 0;
    form.items = [];
    addItemRow();
    if (props.suppliers.length > 0) {
        form.supplier_id = props.suppliers[0].id;
    }
    showPurchaseModal.value = true;
};

const page = usePage();
const checkActionQuery = () => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('action') === 'create' || params.get('create') === '1') {
        openCreateModal();
    }
};

onMounted(() => {
    checkActionQuery();
});

watch(
    () => page.url,
    () => {
        checkActionQuery();
    }
);

const addItemRow = () => {
    form.items.push({
        product_id: null,
        packaging_type: 'PIECES',
        quantity: 10,
        unit_cost: 0,
    });
};

const removeItemRow = (index) => {
    form.items.splice(index, 1);
};

const onProductSelect = (index, productId) => {
    const product = props.products.find((p) => p.id === productId);
    if (product) {
        form.items[index].packaging_type = product.packaging_type;
        form.items[index].unit_cost = Number(product.cost_price);
    }
};

const resetPurchaseForm = () => {
    form.reset();
    form.clearErrors();
    form.items = [];
    showPurchaseModal.value = false;
};

const submitPurchase = () => {
    form.post('/purchases', {
        onSuccess: () => {
            resetPurchaseForm();
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Purchase Invoices (Stock-In Engine)" />

        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                        <i class="pi pi-shopping-cart text-blue-600"></i>
                        Procurement & Stock-In (Tier 1 &rarr; Tier 2)
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Receive finished bakery goods from manufacturing suppliers directly into Mominul Bekari's Central Warehouse.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Button
                        label="Receive Stock-In Purchase Bill"
                        icon="pi pi-plus"
                        severity="primary"
                        @click="openCreateModal"
                    />
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[220px]">
                    <div class="relative">
                        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <InputText
                            v-model="searchQuery"
                            placeholder="Search by invoice # or supplier..."
                            class="w-full pl-9 pr-4 py-2 text-sm"
                            @keyup.enter="filterData"
                        />
                    </div>
                </div>

                <div class="w-56">
                    <Select
                        v-model="selectedSupplierFilter"
                        :options="suppliers"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="All Suppliers"
                        showClear
                        class="w-full text-sm"
                        @change="filterData"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <InputText v-model="dateFrom" type="date" class="text-sm py-2" @change="filterData" />
                    <span class="text-slate-400 text-xs">to</span>
                    <InputText v-model="dateTo" type="date" class="text-sm py-2" @change="filterData" />
                </div>

                <Button icon="pi pi-filter" label="Filter" severity="secondary" outlined @click="filterData" />
            </div>

            <!-- Purchases Table -->
            <AppDataTable
                :items="purchases.data || []"
                :total="purchases.total || 0"
                :perPage="purchases.per_page || 20"
                :currentPage="purchases.current_page || 1"
                tableTitle="Purchase Stock-In Bills"
            >
                <Column field="purchase_no" header="Invoice #" style="width: 170px">
                    <template #body="{ data }">
                        <span class="font-mono text-xs font-bold text-slate-800 bg-slate-100 px-2 py-1 rounded border border-slate-200">
                            {{ data.purchase_no }}
                        </span>
                    </template>
                </Column>

                <Column field="purchase_date" header="Date" style="width: 120px" />

                <Column field="supplier.name" header="Supplier / Manufacturer">
                    <template #body="{ data }">
                        <div>
                            <div class="font-bold text-slate-800">{{ data.supplier?.name }}</div>
                            <div class="text-xs text-slate-500">{{ data.supplier?.company_name }}</div>
                        </div>
                    </template>
                </Column>

                <Column header="Products Received">
                    <template #body="{ data }">
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="item in data.items"
                                :key="item.id"
                                class="text-[11px] font-mono px-2 py-0.5 rounded bg-blue-50 border border-blue-100 text-blue-700"
                            >
                                {{ item.product?.code }} (x{{ Number(item.quantity).toFixed(0) }})
                            </span>
                        </div>
                    </template>
                </Column>

                <Column field="grand_total" header="Total Bill" style="width: 140px">
                    <template #body="{ data }">
                        <span class="font-bold text-slate-800">
                            ৳ {{ Number(data.grand_total).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                        </span>
                    </template>
                </Column>

                <Column field="paid_amount" header="Paid" style="width: 130px">
                    <template #body="{ data }">
                        <span class="text-emerald-600 font-semibold text-xs">
                            ৳ {{ Number(data.paid_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                        </span>
                    </template>
                </Column>

                <Column field="due_amount" header="Due" style="width: 130px">
                    <template #body="{ data }">
                        <span :class="Number(data.due_amount) > 0 ? 'text-rose-600 font-bold text-xs' : 'text-slate-400 text-xs'">
                            ৳ {{ Number(data.due_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                        </span>
                    </template>
                </Column>

                <Column field="status" header="Status" style="width: 100px">
                    <template #body="{ data }">
                        <Tag :value="data.status" severity="success" rounded class="text-[10px]" />
                    </template>
                </Column>

                <Column header="Actions" :exportable="false" style="width: 140px; text-align: right;">
                    <template #body="{ data }">
                        <Link
                            :href="`/purchases/${data.id}`"
                            class="p-button p-button-sm p-button-outlined p-button-secondary !py-1 !px-2.5 text-xs font-semibold inline-flex items-center gap-1.5"
                            title="View & Print Invoice"
                        >
                            <i class="pi pi-print"></i>
                            <span>View / Print</span>
                        </Link>
                    </template>
                </Column>
            </AppDataTable>

            <!-- New Purchase Invoice Modal -->
            <Dialog
                v-model:visible="showPurchaseModal"
                header="Receive Stock-In Purchase Bill"
                modal
                @hide="resetPurchaseForm"
                :style="{ width: '800px' }"
                class="p-fluid"
            >
                <form @submit.prevent="submitPurchase" class="space-y-4 pt-2">
                    <!-- Supplier & Date -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Supplier / Manufacturer <span class="text-rose-500">*</span>
                            </label>
                            <Select
                                v-model="form.supplier_id"
                                :options="suppliers"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select Supplier"
                                class="w-full text-sm"
                                :class="{ 'p-invalid': form.errors.supplier_id }"
                            />
                            <div v-if="selectedSupplierInfo" class="text-xs text-rose-600 font-medium mt-1">
                                Current Due Owed: ৳ {{ Number(selectedSupplierInfo.current_due).toLocaleString() }}
                            </div>
                            <small v-if="form.errors.supplier_id" class="text-rose-500 text-xs">{{ form.errors.supplier_id }}</small>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Purchase Bill Date <span class="text-rose-500">*</span>
                            </label>
                            <InputText
                                v-model="form.purchase_date"
                                type="date"
                                class="w-full text-sm"
                                :class="{ 'p-invalid': form.errors.purchase_date }"
                            />
                        </div>
                    </div>

                    <!-- Products Table -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden mt-3">
                        <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-600">
                                Goods Being Received
                            </span>
                            <Button
                                label="Add Product Item"
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
                                    <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">Quantity</label>
                                    <InputText
                                        v-model.number="item.quantity"
                                        type="number"
                                        min="0.01"
                                        step="any"
                                        class="w-full text-xs py-1 font-bold text-center"
                                    />
                                </div>

                                <div class="col-span-2">
                                    <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">Unit Cost (৳)</label>
                                    <InputText
                                        v-model.number="item.unit_cost"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="w-full text-xs py-1 text-center"
                                    />
                                </div>

                                <div class="col-span-2 text-right">
                                    <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">Line Total</label>
                                    <span class="font-bold text-xs text-slate-800">
                                        ৳ {{ (Number(item.quantity || 0) * Number(item.unit_cost || 0)).toFixed(2) }}
                                    </span>
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

                    <!-- Bill Totals & Payment Summary -->
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Discount (৳)</label>
                                <CurrencyInput v-model="form.discount" placeholder="0.00" class="w-full" />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Paid Amount (৳)</label>
                                <CurrencyInput v-model="form.paid_amount" placeholder="0.00" class="w-full" />
                            </div>

                            <div v-if="Number(form.paid_amount) > 0">
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Payment Method</label>
                                <Select
                                    v-model="form.payment_method"
                                    :options="paymentMethods"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full text-sm"
                                />
                            </div>
                        </div>

                        <!-- Computed Balance Box -->
                        <div class="space-y-2 text-sm bg-white p-3.5 rounded-lg border border-slate-200">
                            <div class="flex justify-between text-slate-600">
                                <span>Subtotal:</span>
                                <span class="font-mono font-semibold">৳ {{ computedSubtotal.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-slate-600">
                                <span>Discount:</span>
                                <span class="font-mono text-rose-600">- ৳ {{ Number(form.discount || 0).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-slate-800 border-t pt-2">
                                <span>Grand Total:</span>
                                <span class="font-mono text-base text-indigo-700">৳ {{ computedGrandTotal.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-emerald-600 font-medium">
                                <span>Paid:</span>
                                <span class="font-mono">৳ {{ Number(form.paid_amount || 0).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between font-bold border-t pt-2">
                                <span class="text-rose-600">Added to Supplier Due:</span>
                                <span class="font-mono text-base text-rose-600">৳ {{ computedRemainingDue.toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Informational Note -->
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-800 flex items-start gap-2">
                        <i class="pi pi-info-circle text-blue-600 mt-0.5"></i>
                        <span>
                            Confirming this bill increments <strong>warehouse_stock</strong> in Mominul Bekari's Central Warehouse and records an immutable <strong>PURCHASE_IN</strong> movement in the audit ledger.
                        </span>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                        <Button label="Cancel" severity="secondary" text @click="showPurchaseModal = false" />
                        <Button
                            label="Confirm & Receive Stock"
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
