<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, useForm, router, Link, usePage } from '@inertiajs/vue3';
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
import { formatDate } from '@/Utils/date';

const props = defineProps({
    memos: {
        type: Object,
        default: () => ({ data: [], total: 0 }),
    },
    roads: {
        type: Array,
        default: () => [],
    },
    retailers: {
        type: Array,
        default: () => [],
    },
    trips: {
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
    returnTypes: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ road_id: null, retailer_id: null, search: '', date_from: '', date_to: '' }),
    },
});

const selectedRoadFilter = ref(props.filters.road_id || null);
const selectedRetailerFilter = ref(props.filters.retailer_id || null);
const searchQuery = ref(props.filters.search || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const showMemoModal = ref(false);
const showDueModal = ref(false);

const form = useForm({
    road_id: null,
    retailer_id: null,
    vehicle_trip_id: null,
    memo_date: new Date().toISOString().split('T')[0],
    custom_deduction: 0,
    paid_amount: 0,
    payment_method: 'CASH',
    notes: '',
    items: [],
    returns: [],
});

const dueForm = useForm({
    retailer_id: null,
    road_id: null,
    payment_method: 'CASH',
    amount: 0,
    payment_date: new Date().toISOString().split('T')[0],
    transaction_ref: '',
    notes: '',
});

// Filter retailers strictly according to the selected road in modal
const availableRetailersForRoad = computed(() => {
    if (!form.road_id) return props.retailers;
    return props.retailers.filter((r) => r.road_id === form.road_id);
});

// Filter trips according to selected road
const availableTripsForRoad = computed(() => {
    if (!form.road_id) return props.trips;
    return props.trips.filter((t) => t.road_id === form.road_id);
});

const availableTripsOptions = computed(() => {
    const list = availableTripsForRoad.value.map((t) => ({
        id: t.id,
        label: `${t.trip_no} (${t.driver_name || 'Driver'} - ${t.items?.length || 0} items)`,
    }));
    return [
        ...list,
        { id: null, label: '🏬 Direct Central Depot Sale (No Vehicle)' },
    ];
});

const getAvailableStockForProduct = (productId) => {
    if (!productId) return 0;
    if (form.vehicle_trip_id) {
        const trip = props.trips.find((t) => t.id === form.vehicle_trip_id);
        if (trip && trip.items) {
            const tripItem = trip.items.find((ti) => ti.product_id === productId);
            if (!tripItem) return 0;
            return Number(tripItem.available_trip_stock ?? (Number(tripItem.loaded_quantity) - Number(tripItem.sold_quantity)));
        }
        return 0;
    }
    const product = props.products.find((p) => p.id === productId);
    return product ? Number(product.warehouse_stock || 0) : 0;
};

const availableProductsForMemo = computed(() => {
    if (form.vehicle_trip_id) {
        const trip = props.trips.find((t) => t.id === form.vehicle_trip_id);
        if (trip && trip.items) {
            return trip.items
                .map((ti) => {
                    const avail = Number(ti.available_trip_stock ?? (Number(ti.loaded_quantity) - Number(ti.sold_quantity)));
                    const prodName = ti.product?.name || `Product #${ti.product_id}`;
                    return {
                        id: ti.product_id,
                        name: `${prodName} (Vehicle Stock: ${avail.toFixed(0)})`,
                        wholesale_price: ti.product?.wholesale_price || 0,
                        packaging_type: ti.product?.packaging_type || 'PIECES',
                        available_stock: avail,
                    };
                })
                .filter((p) => p.available_stock > 0);
        }
        return [];
    }

    return props.products
        .filter((p) => Number(p.warehouse_stock) > 0)
        .map((p) => ({
            id: p.id,
            name: `${p.name} (Depot Stock: ${Number(p.warehouse_stock).toFixed(0)})`,
            wholesale_price: p.wholesale_price,
            packaging_type: p.packaging_type,
            available_stock: Number(p.warehouse_stock),
        }));
});

const hasInvalidStockQuantities = computed(() => {
    if (form.items.length === 0) return false;
    return form.items.some((item) => {
        if (!item.product_id) return false;
        const avail = Number(item.available_stock || 0);
        return avail <= 0 || Number(item.quantity || 0) > avail;
    });
});

const selectedRetailerInfo = computed(() => {
    return props.retailers.find((r) => r.id === form.retailer_id) || null;
});

const subtotal = computed(() => {
    return form.items.reduce((s, i) => s + (Number(i.quantity || 0) * Number(i.unit_price || 0)), 0);
});

const damageDeduction = computed(() => {
    return form.returns
        .filter((r) => r.return_type === 'DAMAGE')
        .reduce((s, r) => {
            const qty = r.return_unit !== 'PACKAGE' && r.sub_unit_quantity != null ? Number(r.sub_unit_quantity) : Number(r.quantity || 0);
            return s + (qty * Number(r.unit_deduction_rate || 0));
        }, 0);
});

const exchangeDeduction = computed(() => {
    return form.returns
        .filter((r) => r.return_type === 'EXCHANGE')
        .reduce((s, r) => {
            const qty = r.return_unit !== 'PACKAGE' && r.sub_unit_quantity != null ? Number(r.sub_unit_quantity) : Number(r.quantity || 0);
            return s + (qty * Number(r.unit_deduction_rate || 0));
        }, 0);
});

const grandTotal = computed(() => {
    const totalDeductions = damageDeduction.value + exchangeDeduction.value + Number(form.custom_deduction || 0);
    return Math.max(0, subtotal.value - totalDeductions);
});

const previousDue = computed(() => {
    return Number(selectedRetailerInfo.value?.current_due || 0);
});

const totalNetPayable = computed(() => {
    return previousDue.value + grandTotal.value;
});

const remainingDue = computed(() => {
    return Math.max(0, totalNetPayable.value - Number(form.paid_amount || 0));
});

const filterData = () => {
    router.get(
        '/memos',
        {
            road_id: selectedRoadFilter.value || undefined,
            retailer_id: selectedRetailerFilter.value || undefined,
            search: searchQuery.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    form.memo_date = new Date().toISOString().split('T')[0];
    form.payment_method = 'CASH';
    form.items = [];
    form.returns = [];
    addItemRow();

    if (props.roads.length > 0) {
        onRoadSelect(props.roads[0].id);
    }
    showMemoModal.value = true;
};

const page = usePage();
const checkActionQuery = () => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('action') === 'create' || params.get('create') === '1') {
        openCreateModal();
        const roadId = Number(params.get('road_id'));
        const retailerId = Number(params.get('retailer_id'));
        if (roadId) {
            onRoadSelect(roadId);
        }
        if (retailerId) {
            form.retailer_id = retailerId;
        }
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

const onRoadSelect = (roadId) => {
    form.road_id = roadId;
    form.retailer_id = null;
    form.vehicle_trip_id = null;

    const tripsOnRoad = props.trips.filter((t) => t.road_id === roadId);
    if (tripsOnRoad.length > 0) {
        form.vehicle_trip_id = tripsOnRoad[0].id;
    }

    const shopsOnRoad = props.retailers.filter((r) => r.road_id === roadId);
    if (shopsOnRoad.length > 0) {
        form.retailer_id = shopsOnRoad[0].id;
    }
    syncItemStocks();
};

const onTripChange = (tripId) => {
    form.vehicle_trip_id = tripId;
    syncItemStocks();
};

const syncItemStocks = () => {
    form.items.forEach((item) => {
        if (item.product_id) {
            item.available_stock = getAvailableStockForProduct(item.product_id);
        }
    });
};

const addItemRow = () => {
    form.items.push({
        product_id: null,
        packaging_type: 'PIECES',
        quantity: 1,
        unit_price: 0,
        available_stock: 0,
    });
};

const removeItemRow = (idx) => {
    form.items.splice(idx, 1);
};

const onProductSelect = (idx, productId) => {
    const product = props.products.find((p) => p.id === productId);
    if (product) {
        form.items[idx].packaging_type = product.packaging_type;
        form.items[idx].unit_price = Number(product.wholesale_price);
        const avail = getAvailableStockForProduct(productId);
        form.items[idx].available_stock = avail;
        if (avail > 0 && form.items[idx].quantity > avail) {
            form.items[idx].quantity = avail;
        }
    }
};

const addReturnRow = () => {
    form.returns.push({
        product_id: null,
        return_type: 'DAMAGE',
        damage_type: 'DAMAGED',
        return_unit: 'PACKAGE',
        sub_unit_quantity: null,
        condition_status: 'DAMAGED_EXPIRED',
        quantity: 1,
        unit_deduction_rate: 0,
        notes: '',
    });
};

const removeReturnRow = (idx) => {
    form.returns.splice(idx, 1);
};

const getProductForReturn = (productId) => {
    return props.products.find((p) => p.id === productId) || null;
};

const getAvailableUnitsForProduct = (productId) => {
    const product = getProductForReturn(productId);
    if (!product) return [{ label: 'Full Pack / Cartoon', value: 'PACKAGE' }];

    const units = [{ label: 'Full Pack / Cartoon', value: 'PACKAGE' }];
    const pcsPerCtn = Number(product.units_per_cartoon || product.pieces_per_packet || 0);
    if (pcsPerCtn > 1) {
        units.push({ label: `Loose Pieces (Pcs)`, value: 'PCS' });
    }
    const weight = Number(product.weight_per_unit_kg || 0);
    if (weight > 0) {
        units.push({ label: `Loose Weight (Kg)`, value: 'KG' });
    }
    return units;
};

const onReturnProductSelect = (idx, productId) => {
    const product = props.products.find((p) => p.id === productId);
    if (!product) return;
    const row = form.returns[idx];
    const weight = Number(product.weight_per_unit_kg || 0);
    const pcsPerCtn = Number(product.units_per_cartoon || product.pieces_per_packet || 0);

    if (row.return_unit === 'PCS' && pcsPerCtn > 1) {
        row.unit_deduction_rate = Number((Number(product.wholesale_price) / pcsPerCtn).toFixed(2));
        if (!row.sub_unit_quantity) row.sub_unit_quantity = 1;
        row.quantity = Number((Number(row.sub_unit_quantity) / pcsPerCtn).toFixed(3));
        if (row.return_type === 'EXCHANGE') {
            row.damage_type = 'BROKEN_PACKET';
            row.condition_status = 'BROKEN_PACKET';
        }
    } else if (row.return_unit === 'KG' && weight > 0) {
        row.unit_deduction_rate = Number((Number(product.wholesale_price) / weight).toFixed(2));
        if (!row.sub_unit_quantity) row.sub_unit_quantity = 1;
        row.quantity = Number((Number(row.sub_unit_quantity) / weight).toFixed(3));
        if (row.return_type === 'EXCHANGE') {
            row.damage_type = 'BROKEN_PACKET';
            row.condition_status = 'BROKEN_PACKET';
        }
    } else {
        row.return_unit = 'PACKAGE';
        row.unit_deduction_rate = Number(product.wholesale_price);
        row.sub_unit_quantity = null;
        if (!row.quantity || Number(row.quantity) <= 0) row.quantity = 1;
        if (row.return_type === 'EXCHANGE') {
            row.condition_status = 'GOOD_SEALED';
        }
    }
};

const onReturnUnitChange = (idx) => {
    const row = form.returns[idx];
    const product = props.products.find((p) => p.id === row.product_id);
    if (!product) return;
    const weight = Number(product.weight_per_unit_kg || 0);
    const pcsPerCtn = Number(product.units_per_cartoon || product.pieces_per_packet || 0);

    if (row.return_unit === 'PCS' && pcsPerCtn > 1) {
        row.unit_deduction_rate = Number((Number(product.wholesale_price) / pcsPerCtn).toFixed(2));
        if (!row.sub_unit_quantity) row.sub_unit_quantity = 1;
        row.quantity = Number((Number(row.sub_unit_quantity) / pcsPerCtn).toFixed(3));
        if (row.return_type === 'EXCHANGE') {
            row.damage_type = 'BROKEN_PACKET';
            row.condition_status = 'BROKEN_PACKET';
        }
    } else if (row.return_unit === 'KG' && weight > 0) {
        row.unit_deduction_rate = Number((Number(product.wholesale_price) / weight).toFixed(2));
        if (!row.sub_unit_quantity) row.sub_unit_quantity = 1;
        row.quantity = Number((Number(row.sub_unit_quantity) / weight).toFixed(3));
        if (row.return_type === 'EXCHANGE') {
            row.damage_type = 'BROKEN_PACKET';
            row.condition_status = 'BROKEN_PACKET';
        }
    } else {
        row.return_unit = 'PACKAGE';
        row.unit_deduction_rate = Number(product.wholesale_price);
        row.sub_unit_quantity = null;
        if (!row.quantity || Number(row.quantity) <= 0) row.quantity = 1;
        if (row.return_type === 'EXCHANGE') {
            row.damage_type = 'DAMAGED';
            row.condition_status = 'GOOD_SEALED';
        }
    }
};

const onSubUnitQtyChange = (idx) => {
    const row = form.returns[idx];
    const product = props.products.find((p) => p.id === row.product_id);
    if (!product) return;
    const weight = Number(product.weight_per_unit_kg || 0);
    const pcsPerCtn = Number(product.units_per_cartoon || product.pieces_per_packet || 0);

    if (row.return_unit === 'PCS' && pcsPerCtn > 1 && row.sub_unit_quantity != null) {
        row.quantity = Number((Number(row.sub_unit_quantity) / pcsPerCtn).toFixed(3));
    } else if (row.return_unit === 'KG' && weight > 0 && row.sub_unit_quantity != null) {
        row.quantity = Number((Number(row.sub_unit_quantity) / weight).toFixed(3));
    }
};

const onReturnTypeChange = (idx) => {
    const row = form.returns[idx];
    if (row.return_type === 'EXCHANGE') {
        if (row.return_unit !== 'PACKAGE') {
            row.damage_type = 'BROKEN_PACKET';
            row.condition_status = 'BROKEN_PACKET';
        } else {
            row.condition_status = 'GOOD_SEALED';
        }
    } else {
        row.condition_status = 'DAMAGED_EXPIRED';
        if (row.damage_type === 'BROKEN_PACKET' && row.return_unit === 'PACKAGE') {
            row.damage_type = 'DAMAGED';
        }
    }
};

const resetMemoForm = () => {
    form.reset();
    form.clearErrors();
    form.items = [];
    form.returns = [];
    showMemoModal.value = false;
};

const resetDueForm = () => {
    dueForm.reset();
    dueForm.clearErrors();
    showDueModal.value = false;
};

const submitMemo = () => {
    if (hasInvalidStockQuantities.value) {
        return;
    }
    form.post('/memos', {
        onSuccess: () => {
            resetMemoForm();
        },
    });
};

const openDueCollectionModal = (retailer = null) => {
    dueForm.reset();
    dueForm.clearErrors();
    dueForm.payment_date = new Date().toISOString().split('T')[0];
    dueForm.payment_method = 'CASH';

    if (retailer) {
        dueForm.retailer_id = retailer.id;
        dueForm.road_id = retailer.road_id;
        dueForm.amount = Number(retailer.current_due);
    } else if (props.retailers.length > 0) {
        dueForm.retailer_id = props.retailers[0].id;
        dueForm.road_id = props.retailers[0].road_id;
        dueForm.amount = Number(props.retailers[0].current_due);
    }
    showDueModal.value = true;
};

const submitDuePayment = () => {
    dueForm.post('/memos/collect-due', {
        onSuccess: () => {
            resetDueForm();
        },
    });
};

const getStatusBadge = (status) => {
    const map = {
        PAID: 'success',
        PARTIAL: 'warn',
        DUE: 'danger',
        CANCELLED: 'secondary',
    };
    return map[status] || 'secondary';
};
</script>

<template>
    <AppLayout>
        <Head title="Sales Memos & Billing" />

        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                        <i class="pi pi-file-edit text-emerald-600"></i>
                        Sales Memos & Road Billing Engine
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Generate road-wise retail delivery memos, record damage returns and product exchanges, update live credit dues, and print thermal receipts.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Button
                        label="Collect Due Payment"
                        icon="pi pi-credit-card"
                        severity="secondary"
                        outlined
                        @click="openDueCollectionModal()"
                    />
                    <Button
                        label="Create New Sales Memo"
                        icon="pi pi-plus"
                        severity="primary"
                        @click="openCreateModal"
                    />
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <InputText
                            v-model="searchQuery"
                            placeholder="Search by memo #, shop name, or phone..."
                            class="w-full pl-9 pr-4 py-2 text-sm"
                            @keyup.enter="filterData"
                        />
                    </div>
                </div>

                <div class="w-44">
                    <Select
                        v-model="selectedRoadFilter"
                        :options="roads"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="All Roads"
                        filter
                        showClear
                        class="w-full text-sm"
                        @change="filterData"
                    />
                </div>

                <div class="w-48">
                    <Select
                        v-model="selectedRetailerFilter"
                        :options="retailers"
                        optionLabel="shop_name"
                        optionValue="id"
                        placeholder="All Retailers"
                        filter
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

            <!-- Memos Table -->
            <AppDataTable
                :items="memos.data || []"
                :total="memos.total || 0"
                :perPage="memos.per_page || 20"
                :currentPage="memos.current_page || 1"
                tableTitle="Generated Sales Memos"
            >
                <Column field="memo_no" header="Memo #" style="width: 170px">
                    <template #body="{ data }">
                        <span class="font-mono text-xs font-bold text-slate-800 bg-slate-100 px-2 py-1 rounded border border-slate-200">
                            {{ data.memo_no }}
                        </span>
                    </template>
                </Column>

                <Column field="memo_date" header="Date" style="width: 125px">
                    <template #body="{ data }">
                        <span class="text-xs text-slate-700 font-medium">
                            {{ formatDate(data.memo_date) }}
                        </span>
                    </template>
                </Column>

                <Column field="retailer.shop_name" header="Retailer Shop">
                    <template #body="{ data }">
                        <div>
                            <div class="font-bold text-slate-800">{{ data.retailer?.shop_name }}</div>
                            <div class="text-xs text-slate-500">
                                {{ data.retailer?.owner_name }} &bull; <span class="font-mono">{{ data.retailer?.phone }}</span>
                            </div>
                        </div>
                    </template>
                </Column>

                <Column field="road.name" header="Road" style="width: 110px">
                    <template #body="{ data }">
                        <span class="text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-100 px-2 py-1 rounded">
                            {{ data.road?.code }}
                        </span>
                    </template>
                </Column>

                <Column header="Bill Breakdown" style="width: 170px">
                    <template #body="{ data }">
                        <div class="text-xs">
                            <span class="text-slate-500">Subtotal:</span>
                            <span class="font-mono font-semibold text-slate-700 ml-1">৳{{ Number(data.subtotal).toFixed(0) }}</span>
                        </div>
                        <div v-if="Number(data.damage_deduction) > 0" class="text-[11px] text-rose-500">
                            - Damage: ৳{{ Number(data.damage_deduction).toFixed(0) }}
                        </div>
                        <div v-if="Number(data.exchange_deduction) > 0" class="text-[11px] text-blue-500">
                            - Exchange: ৳{{ Number(data.exchange_deduction).toFixed(0) }}
                        </div>
                    </template>
                </Column>

                <Column field="grand_total" header="Net Bill" style="width: 130px">
                    <template #body="{ data }">
                        <span class="font-bold text-sm text-slate-900">
                            ৳ {{ Number(data.grand_total).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                        </span>
                    </template>
                </Column>

                <Column field="paid_amount" header="Paid" style="width: 110px">
                    <template #body="{ data }">
                        <span class="text-emerald-600 font-semibold text-xs">
                            ৳ {{ Number(data.paid_amount).toLocaleString('en-US', { minimumFractionDigits: 0 }) }}
                        </span>
                    </template>
                </Column>

                <Column field="due_amount" header="Balance Due" style="width: 130px">
                    <template #body="{ data }">
                        <span :class="Number(data.due_amount) > 0 ? 'text-rose-600 font-bold text-xs' : 'text-slate-400 text-xs'">
                            ৳ {{ Number(data.due_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                        </span>
                    </template>
                </Column>

                <Column field="memo_status" header="Status" style="width: 90px">
                    <template #body="{ data }">
                        <Tag :value="data.memo_status" :severity="getStatusBadge(data.memo_status)" rounded class="text-[10px]" />
                    </template>
                </Column>

                <Column header="Actions" :exportable="false" style="width: 170px; text-align: right;">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1.5 justify-end">
                            <Link
                                :href="`/memos/${data.id}`"
                                class="p-button p-button-sm p-button-outlined p-button-secondary !py-1 !px-2 text-xs font-semibold inline-flex items-center gap-1"
                                title="Standard A4 Invoice"
                            >
                                <i class="pi pi-print"></i>
                                <span>A4</span>
                            </Link>
                            <Link
                                :href="`/memos/${data.id}?format=thermal`"
                                class="p-button p-button-sm p-button-outlined p-button-help !py-1 !px-2 text-xs font-semibold inline-flex items-center gap-1"
                                title="Thermal 80mm Slip"
                            >
                                <i class="pi pi-receipt"></i>
                                <span>80mm</span>
                            </Link>
                        </div>
                    </template>
                </Column>
            </AppDataTable>

            <!-- Create Memo Modal -->
            <Dialog
                v-model:visible="showMemoModal"
                header="Create Retailer Sales Delivery Memo"
                modal
                @hide="resetMemoForm"
                :style="{ width: '1620px', maxWidth: '98vw' }"
                class="p-fluid"
            >
                <form @submit.prevent="submitMemo" class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                        <!-- Road Selection -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Delivery Road <span class="text-rose-500">*</span>
                            </label>
                            <Select
                                :modelValue="form.road_id"
                                :options="roads"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select Road"
                                filter
                                class="w-full text-sm"
                                @update:modelValue="onRoadSelect"
                            />
                        </div>

                        <!-- Vehicle Mobile Trip Selection -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Vehicle Trip / Channel
                            </label>
                            <Select
                                :modelValue="form.vehicle_trip_id"
                                :options="availableTripsOptions"
                                optionLabel="label"
                                optionValue="id"
                                placeholder="Select Trip or Direct Sale"
                                filter
                                class="w-full text-sm"
                                @update:modelValue="onTripChange"
                            />
                        </div>

                        <!-- Retailer Selection -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Retailer Shop <span class="text-rose-500">*</span>
                            </label>
                            <Select
                                v-model="form.retailer_id"
                                :options="availableRetailersForRoad"
                                optionLabel="shop_name"
                                optionValue="id"
                                placeholder="Select Shop on Road"
                                filter
                                class="w-full text-sm"
                            />
                            <div v-if="selectedRetailerInfo" class="text-xs text-rose-600 font-bold mt-1">
                                Previous Due: ৳ {{ Number(selectedRetailerInfo.current_due).toLocaleString() }}
                            </div>
                        </div>

                        <!-- Date -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Memo Date <span class="text-rose-500">*</span>
                            </label>
                            <InputText v-model="form.memo_date" type="date" class="w-full text-sm" />
                        </div>
                    </div>

                    <!-- Sold Items Table -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden mt-3">
                        <div class="bg-slate-50 px-3 sm:px-4 py-2 border-b border-slate-200 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                                <i class="pi pi-box text-emerald-600"></i> Sold Products
                            </span>
                            <Button
                                label="Add Sold Product"
                                icon="pi pi-plus"
                                size="small"
                                severity="secondary"
                                text
                                @click="addItemRow"
                            />
                        </div>

                        <!-- 0 Stock Warning Alert -->
                        <div v-if="availableProductsForMemo.length === 0" class="m-3 p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-xs flex items-center gap-2">
                            <i class="pi pi-exclamation-triangle text-amber-600 text-base"></i>
                            <span>
                                <strong>0 Stock Available:</strong>
                                <span v-if="form.vehicle_trip_id">This vehicle trip has no remaining loaded stock to sell.</span>
                                <span v-else>Central Warehouse has no available stock to sell.</span>
                                Stock must first be purchased from a manufacturing supplier before it can be loaded onto vehicles or sold.
                            </span>
                        </div>

                        <div class="p-2.5 sm:p-3 space-y-2.5">
                            <div
                                v-for="(item, idx) in form.items"
                                :key="idx"
                                class="grid grid-cols-12 gap-2 sm:gap-2.5 items-start sm:items-center bg-white p-2.5 rounded-xl border border-slate-200/90 sm:border-slate-100 shadow-sm"
                            >
                                <div class="col-span-12 sm:col-span-5">
                                    <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">Product</label>
                                    <Select
                                        v-model="item.product_id"
                                        :options="availableProductsForMemo"
                                        optionLabel="name"
                                        optionValue="id"
                                        placeholder="Select In-Stock Product"
                                        filter
                                        class="w-full text-xs"
                                        @change="(e) => onProductSelect(idx, e.value)"
                                    />
                                </div>

                                <div class="col-span-4 sm:col-span-2">
                                    <div class="flex items-center justify-between">
                                        <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">Quantity</label>
                                        <span class="text-[9px] font-bold" :class="Number(item.available_stock || 0) > 0 ? 'text-emerald-700' : 'text-rose-600'">
                                            Av: {{ Number(item.available_stock || 0).toFixed(0) }}
                                        </span>
                                    </div>
                                    <InputText
                                        v-model.number="item.quantity"
                                        type="number"
                                        min="0.01"
                                        step="any"
                                        :class="[
                                            'w-full text-xs py-1.5 text-center font-bold',
                                            Number(item.quantity) > Number(item.available_stock || 0) || Number(item.available_stock || 0) <= 0
                                                ? 'border-rose-500 text-rose-600'
                                                : ''
                                        ]"
                                    />
                                    <div v-if="Number(item.quantity) > Number(item.available_stock || 0)" class="text-[10px] text-rose-500 font-medium">
                                        Exceeds stock!
                                    </div>
                                </div>

                                <div class="col-span-4 sm:col-span-2">
                                    <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">Rate (৳)</label>
                                    <InputText
                                        v-model.number="item.unit_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="w-full text-xs py-1.5 text-center font-semibold"
                                    />
                                </div>

                                <div class="col-span-3 sm:col-span-2 text-right">
                                    <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">Total</label>
                                    <span class="font-bold text-xs text-slate-800">
                                        ৳ {{ (Number(item.quantity || 0) * Number(item.unit_price || 0)).toFixed(2) }}
                                    </span>
                                </div>

                                <div class="col-span-1 sm:col-span-1 text-right flex items-center justify-end pt-3 sm:pt-0">
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

                    <!-- Returns & Exchanges Section (Scenario 1 & 2) -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden mt-3 bg-slate-50">
                        <div class="px-3 sm:px-4 py-2 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                                    <i class="pi pi-replay text-indigo-600"></i> Returns & Exchanges
                                </span>
                                <span class="text-[10px] text-slate-500 block">Scenario 1: Damage Return (Scrap) | Scenario 2: Product Exchange</span>
                            </div>
                            <Button
                                label="Add Returned Item"
                                icon="pi pi-plus"
                                size="small"
                                severity="secondary"
                                text
                                @click="addReturnRow"
                            />
                        </div>

                        <div v-if="form.returns.length === 0" class="p-3 text-center text-xs text-slate-400">
                            No damage returns or product exchanges added. Click "Add Returned Item" to apply deductions.
                        </div>

                        <div v-else class="p-2.5 sm:p-3 space-y-2.5">
                            <div
                                v-for="(ret, idx) in form.returns"
                                :key="idx"
                                class="bg-white p-3 rounded-xl border border-slate-200 shadow-sm space-y-2"
                            >
                                <div class="grid grid-cols-12 gap-2 sm:gap-2.5 items-start sm:items-center">
                                    <div class="col-span-12 sm:col-span-4">
                                        <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">Product</label>
                                        <Select
                                            v-model="ret.product_id"
                                            :options="products"
                                            optionLabel="name"
                                            optionValue="id"
                                            placeholder="Select Returned Product"
                                            filter
                                            class="w-full text-xs"
                                            @change="(e) => onReturnProductSelect(idx, e.value)"
                                        />
                                    </div>

                                    <div class="col-span-6 sm:col-span-3">
                                        <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">Return Type</label>
                                        <Select
                                            v-model="ret.return_type"
                                            :options="[
                                                { label: 'Damage Return (Scrap)', value: 'DAMAGE' },
                                                { label: 'Product Exchange', value: 'EXCHANGE' },
                                            ]"
                                            optionLabel="label"
                                            optionValue="value"
                                            filter
                                            class="w-full text-xs"
                                            @change="() => onReturnTypeChange(idx)"
                                        />
                                    </div>

                                    <div class="col-span-6 sm:col-span-2">
                                        <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">Unit</label>
                                        <Select
                                            v-if="getAvailableUnitsForProduct(ret.product_id).length > 1"
                                            v-model="ret.return_unit"
                                            :options="getAvailableUnitsForProduct(ret.product_id)"
                                            optionLabel="label"
                                            optionValue="value"
                                            filter
                                            class="w-full text-xs"
                                            @change="() => onReturnUnitChange(idx)"
                                        />
                                        <div v-else class="text-xs font-semibold text-slate-500 py-1.5 px-2 bg-slate-100 rounded border border-slate-200 text-center">
                                            Full Pack / Cartoon
                                        </div>
                                    </div>

                                    <div class="col-span-6 sm:col-span-1">
                                        <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">
                                            {{ ret.return_unit === 'KG' ? 'Weight (kg)' : (ret.return_unit === 'PCS' ? 'Pieces (pcs)' : 'Cartoons / Pk') }}
                                        </label>
                                        <InputText
                                            v-if="ret.return_unit === 'KG' || ret.return_unit === 'PCS'"
                                            v-model.number="ret.sub_unit_quantity"
                                            type="number"
                                            class="w-full text-xs py-1.5 text-center font-bold"
                                            @input="() => onSubUnitQtyChange(idx)"
                                        />
                                        <InputText
                                            v-else
                                            v-model.number="ret.quantity"
                                            type="number"
                                            min="0.01"
                                            step="any"
                                            class="w-full text-xs py-1.5 text-center font-bold"
                                        />
                                    </div>

                                    <div class="col-span-5 sm:col-span-1">
                                        <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">
                                            Rate (৳/{{ ret.return_unit === 'PCS' ? 'pc' : (ret.return_unit === 'KG' ? 'kg' : 'ctn') }})
                                        </label>
                                        <InputText
                                            v-model.number="ret.unit_deduction_rate"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-full text-xs py-1.5 text-center font-semibold"
                                        />
                                    </div>

                                    <div class="col-span-1 sm:col-span-1 text-right flex items-center justify-end pt-3 sm:pt-0">
                                        <Button
                                            icon="pi pi-times"
                                            severity="danger"
                                            text
                                            rounded
                                            size="small"
                                            @click="removeReturnRow(idx)"
                                        />
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center justify-between text-[11px] pt-1 border-t border-slate-100 text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <span v-if="ret.return_unit === 'PCS' && Number(getProductForReturn(ret.product_id)?.units_per_cartoon || getProductForReturn(ret.product_id)?.pieces_per_packet || 0) > 1" class="font-medium text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                            <i class="pi pi-box text-[10px] mr-1"></i> Loose Pieces: {{ Number(ret.sub_unit_quantity || 0) }} pcs of {{ Number(getProductForReturn(ret.product_id).units_per_cartoon || getProductForReturn(ret.product_id).pieces_per_packet) }} pcs ctn (≈ {{ Number(ret.quantity || 0).toFixed(2) }} ctn eq)
                                        </span>
                                        <span v-else-if="ret.return_unit === 'KG' && Number(getProductForReturn(ret.product_id)?.weight_per_unit_kg || 0) > 0" class="font-medium text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                            <i class="pi pi-box text-[10px] mr-1"></i> Loose Weight: {{ Number(ret.sub_unit_quantity || 0) }} kg of {{ Number(getProductForReturn(ret.product_id).weight_per_unit_kg) }} kg pack (≈ {{ Number(ret.quantity || 0).toFixed(2) }} pkt eq)
                                        </span>

                                        <span v-if="ret.return_type === 'EXCHANGE' && ret.return_unit !== 'PACKAGE'" class="text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200 font-medium">
                                            <i class="pi pi-shield text-[10px] mr-1"></i> Broken ctn/pack to Repack Bay (wholesale stock intact)
                                        </span>
                                        <span v-else-if="ret.return_type === 'EXCHANGE'" class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 font-medium">
                                            <i class="pi pi-check text-[10px] mr-1"></i> Good sealed cartoon/pack restored to stock
                                        </span>
                                        <span v-else class="text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 font-medium">
                                            <i class="pi pi-trash text-[10px] mr-1"></i> Damaged / scrap to quarantine
                                        </span>
                                    </div>
                                    <div class="font-bold text-slate-800">
                                        Credit: <span class="text-rose-600">- ৳ {{ ((ret.return_unit !== 'PACKAGE' && ret.sub_unit_quantity != null ? Number(ret.sub_unit_quantity) : Number(ret.quantity || 0)) * Number(ret.unit_deduction_rate || 0)).toFixed(2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bill Totals, Dual-Ledger Due & Payment -->
                    <div class="bg-slate-50 p-3 sm:p-4 rounded-xl border border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Custom Supervisor Deduction / Discount (৳)</label>
                                <CurrencyInput v-model="form.custom_deduction" placeholder="0.00" class="w-full" />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Cash / Payment Collected (৳)</label>
                                <CurrencyInput v-model="form.paid_amount" placeholder="0.00" class="w-full" />
                            </div>

                            <div v-if="Number(form.paid_amount) > 0">
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Collection Payment Method</label>
                                <Select
                                    v-model="form.payment_method"
                                    :options="paymentMethods"
                                    optionLabel="label"
                                    optionValue="value"
                                    filter
                                    class="w-full text-sm"
                                />
                            </div>
                        </div>

                        <!-- Computed Financial Ledger Box -->
                        <div class="space-y-1.5 text-xs bg-white p-3.5 rounded-lg border border-slate-200">
                            <div class="flex justify-between text-slate-600">
                                <span>Delivered Items Subtotal:</span>
                                <span class="font-mono font-bold">৳ {{ subtotal.toFixed(2) }}</span>
                            </div>
                            <div v-if="damageDeduction > 0" class="flex justify-between text-rose-600">
                                <span>- Damage Deduction:</span>
                                <span class="font-mono font-semibold">- ৳ {{ damageDeduction.toFixed(2) }}</span>
                            </div>
                            <div v-if="exchangeDeduction > 0" class="flex justify-between text-blue-600">
                                <span>- Exchange Deduction:</span>
                                <span class="font-mono font-semibold">- ৳ {{ exchangeDeduction.toFixed(2) }}</span>
                            </div>
                            <div v-if="Number(form.custom_deduction) > 0" class="flex justify-between text-amber-600">
                                <span>- Custom Discount:</span>
                                <span class="font-mono font-semibold">- ৳ {{ Number(form.custom_deduction).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-slate-800 border-t pt-1.5">
                                <span>Current Memo Net Bill:</span>
                                <span class="font-mono text-sm text-indigo-700">৳ {{ grandTotal.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-slate-500">
                                <span>+ Shop Previous Due:</span>
                                <span class="font-mono">৳ {{ previousDue.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-slate-900 border-t pt-1.5">
                                <span>Total Net Outstanding:</span>
                                <span class="font-mono text-sm">৳ {{ totalNetPayable.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-emerald-600 font-semibold">
                                <span>- Amount Paid Now:</span>
                                <span class="font-mono">৳ {{ Number(form.paid_amount || 0).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between font-bold border-t pt-1.5 text-rose-600 text-sm">
                                <span>Final Remaining Due:</span>
                                <span class="font-mono">৳ {{ remainingDue.toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-2 pt-3 border-t border-slate-200">
                        <Button label="Cancel" severity="secondary" text class="w-full sm:w-auto" @click="resetMemoForm" />
                        <Button
                            label="Generate & Finalize Memo"
                            icon="pi pi-check"
                            type="submit"
                            severity="primary"
                            :loading="form.processing"
                            class="w-full sm:w-auto font-bold"
                        />
                    </div>
                </form>
            </Dialog>

            <!-- Direct Due Collection Modal -->
            <Dialog
                v-model:visible="showDueModal"
                header="Direct Retailer Due Payment Collection"
                modal
                @hide="resetDueForm"
                :style="{ width: '480px' }"
                class="p-fluid"
            >
                <form @submit.prevent="submitDuePayment" class="space-y-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Retailer Shop <span class="text-rose-500">*</span>
                        </label>
                        <Select
                            v-model="dueForm.retailer_id"
                            :options="retailers"
                            optionLabel="shop_name"
                            optionValue="id"
                            placeholder="Select Retailer"
                            filter
                            class="w-full text-sm"
                            @change="(e) => {
                                const r = retailers.find(shop => shop.id === e.value);
                                if (r) {
                                    dueForm.road_id = r.road_id;
                                    dueForm.amount = Number(r.current_due);
                                }
                            }"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Collected Amount (৳) <span class="text-rose-500">*</span>
                        </label>
                        <CurrencyInput v-model="dueForm.amount" placeholder="0.00" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Payment Method <span class="text-rose-500">*</span>
                        </label>
                        <Select
                            v-model="dueForm.payment_method"
                            :options="paymentMethods"
                            optionLabel="label"
                            optionValue="value"
                            filter
                            class="w-full text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Collection Date <span class="text-rose-500">*</span>
                        </label>
                        <InputText v-model="dueForm.payment_date" type="date" class="w-full text-sm" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Reference / Notes
                        </label>
                        <InputText v-model="dueForm.transaction_ref" placeholder="Trx ID, Cheque #, or road collector note" class="w-full text-sm" />
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                        <Button label="Cancel" severity="secondary" text @click="showDueModal = false" />
                        <Button label="Confirm Collection" icon="pi pi-check" type="submit" severity="success" :loading="dueForm.processing" />
                    </div>
                </form>
            </Dialog>
        </div>
    </AppLayout>
</template>
