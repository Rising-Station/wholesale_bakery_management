<script setup>
import { ref, onMounted, watch } from 'vue';
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

const props = defineProps({
    trips: {
        type: Object,
        default: () => ({ data: [], total: 0 }),
    },
    roads: {
        type: Array,
        default: () => [],
    },
    drivers: {
        type: Array,
        default: () => [],
    },
    products: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ road_id: null, status: null, date: '' }),
    },
});

const selectedRoadFilter = ref(props.filters.road_id || null);
const selectedStatusFilter = ref(props.filters.status || null);
const dateFilter = ref(props.filters.date || '');

const showTripModal = ref(false);

const form = useForm({
    road_id: null,
    trip_date: new Date().toISOString().split('T')[0],
    vehicle_number: '',
    driver_id: null,
    driver_name: '',
    driver_phone: '',
    notes: '',
    items: [],
});

const filterData = () => {
    router.get(
        '/trips',
        {
            road_id: selectedRoadFilter.value || undefined,
            status: selectedStatusFilter.value || undefined,
            date: dateFilter.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    form.trip_date = new Date().toISOString().split('T')[0];
    form.items = [];
    addItemRow();

    if (props.roads.length > 0) {
        onRoadSelect(props.roads[0].id);
    }
    showTripModal.value = true;
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

const onRoadSelect = (roadId) => {
    form.road_id = roadId;
    const road = props.roads.find((r) => r.id === roadId);
    if (road) {
        form.vehicle_number = road.vehicle_number || '';
        form.driver_name = road.driver_name || '';
        form.driver_phone = road.driver_phone || '';
        form.driver_id = road.driver_id || null;
    }
};

const addItemRow = () => {
    form.items.push({
        product_id: null,
        loaded_quantity: 10,
        available_stock: 0,
    });
};

const removeItemRow = (index) => {
    form.items.splice(index, 1);
};

const onProductSelect = (index, productId) => {
    const product = props.products.find((p) => p.id === productId);
    if (product) {
        form.items[index].available_stock = Number(product.warehouse_stock);
    }
};

const resetTripForm = () => {
    form.reset();
    form.clearErrors();
    form.items = [];
    showTripModal.value = false;
};

const submitTrip = () => {
    const hasInvalid = form.items.some(
        (i) => !i.product_id || Number(i.available_stock || 0) <= 0 || Number(i.loaded_quantity || 0) > Number(i.available_stock || 0)
    );
    if (hasInvalid) {
        return;
    }
    form.post('/trips', {
        onSuccess: () => {
            resetTripForm();
        },
    });
};

const dispatchTrip = (tripId) => {
    router.patch(`/trips/${tripId}/dispatch`, {}, {
        preserveScroll: true,
    });
};

const getStatusBadge = (status) => {
    const map = {
        DRAFT: 'secondary',
        LOADED: 'info',
        DISPATCHED: 'warn',
        COMPLETED: 'success',
    };
    return map[status] || 'secondary';
};
</script>

<template>
    <AppLayout>
        <Head title="Vehicle Mobile Stock & Trip Dispatch" />

        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                        <i class="pi pi-truck text-amber-600"></i>
                        Vehicle Mobile Stock Loading & Road Dispatch
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Dispatches 1 delivery vehicle per road at a time (Road A, Road B, Road C). Deducts central warehouse stock and creates mobile manifest.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Button
                        label="New Vehicle Loading Sheet"
                        icon="pi pi-plus"
                        severity="primary"
                        @click="openCreateModal"
                    />
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-wrap items-center gap-3">
                <div class="w-48">
                    <Select
                        v-model="selectedRoadFilter"
                        :options="roads"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="All Roads"
                        showClear
                        class="w-full text-sm"
                        @change="filterData"
                    />
                </div>

                <div class="w-44">
                    <Select
                        v-model="selectedStatusFilter"
                        :options="[
                            { label: 'Loaded (In Depot)', value: 'LOADED' },
                            { label: 'Dispatched (On Road)', value: 'DISPATCHED' },
                            { label: 'Completed (Closed)', value: 'COMPLETED' },
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="All Statuses"
                        showClear
                        class="w-full text-sm"
                        @change="filterData"
                    />
                </div>

                <div class="w-44">
                    <InputText v-model="dateFilter" type="date" class="w-full text-sm py-2" @change="filterData" />
                </div>

                <Button icon="pi pi-filter" label="Filter" severity="secondary" outlined @click="filterData" />
            </div>

            <!-- Trips Table -->
            <AppDataTable
                :items="trips.data || []"
                :total="trips.total || 0"
                :perPage="trips.per_page || 20"
                :currentPage="trips.current_page || 1"
                tableTitle="Route Vehicle Dispatches"
            >
                <Column field="trip_no" header="Trip #" style="width: 170px">
                    <template #body="{ data }">
                        <span class="font-mono text-xs font-bold text-slate-800 bg-slate-100 px-2 py-1 rounded border border-slate-200">
                            {{ data.trip_no }}
                        </span>
                    </template>
                </Column>

                <Column field="trip_date" header="Date" style="width: 120px" />

                <Column field="road.name" header="Assigned Road">
                    <template #body="{ data }">
                        <div class="font-bold text-slate-800 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            {{ data.road?.name }}
                        </div>
                        <div class="text-xs text-slate-500 font-mono">{{ data.road?.code }}</div>
                    </template>
                </Column>

                <Column header="Vehicle & Driver">
                    <template #body="{ data }">
                        <div class="font-semibold text-slate-800 text-xs">{{ data.vehicle_number }}</div>
                        <div class="text-xs text-slate-500">
                            {{ data.driver_name }} <span v-if="data.driver_phone">({{ data.driver_phone }})</span>
                        </div>
                    </template>
                </Column>

                <Column field="items_count" header="Loaded Items" style="width: 130px">
                    <template #body="{ data }">
                        <span class="font-semibold text-xs text-slate-700 bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-full">
                            {{ data.items_count || 0 }} products
                        </span>
                    </template>
                </Column>

                <Column field="status" header="Trip Status" style="width: 120px">
                    <template #body="{ data }">
                        <Tag :value="data.status" :severity="getStatusBadge(data.status)" rounded class="text-[10px]" />
                    </template>
                </Column>

                <Column header="Actions" style="width: 250px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1">
                            <Link :href="`/trips/${data.id}`">
                                <Button
                                    icon="pi pi-print"
                                    label="Gate Pass"
                                    severity="secondary"
                                    size="small"
                                    text
                                />
                            </Link>

                            <Button
                                v-if="data.status === 'LOADED'"
                                icon="pi pi-send"
                                label="Dispatch"
                                severity="warn"
                                size="small"
                                text
                                @click="dispatchTrip(data.id)"
                            />

                            <Link
                                v-if="data.status === 'DISPATCHED' || data.status === 'LOADED'"
                                :href="route('trips.reconcile', data.id)"
                            >
                                <Button
                                    icon="pi pi-check-circle"
                                    label="Reconcile"
                                    severity="success"
                                    size="small"
                                    text
                                />
                            </Link>
                            <Link
                                v-else-if="data.status === 'COMPLETED'"
                                :href="route('trips.reconcile', data.id)"
                            >
                                <Button
                                    icon="pi pi-file"
                                    label="Settlement"
                                    severity="info"
                                    size="small"
                                    text
                                />
                            </Link>
                        </div>
                    </template>
                </Column>
            </AppDataTable>

            <!-- New Trip Loading Sheet Modal -->
            <Dialog
                v-model:visible="showTripModal"
                header="Create Vehicle Trip Loading Sheet (Gate Pass)"
                modal
                @hide="resetTripForm"
                :style="{ width: '800px' }"
                class="p-fluid"
            >
                <form @submit.prevent="submitTrip" class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Road Dropdown -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Distribution Road <span class="text-rose-500">*</span>
                            </label>
                            <Select
                                :modelValue="form.road_id"
                                :options="roads"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select Distribution Road"
                                class="w-full text-sm"
                                :class="{ 'p-invalid': form.errors.road_id }"
                                @update:modelValue="onRoadSelect"
                            />
                            <small v-if="form.errors.road_id" class="text-rose-500 text-xs">{{ form.errors.road_id }}</small>
                        </div>

                        <!-- Trip Date -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Dispatch Date <span class="text-rose-500">*</span>
                            </label>
                            <InputText
                                v-model="form.trip_date"
                                type="date"
                                class="w-full text-sm"
                                :class="{ 'p-invalid': form.errors.trip_date }"
                            />
                        </div>

                        <!-- Vehicle Number -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Delivery Vehicle Reg # <span class="text-rose-500">*</span>
                            </label>
                            <InputText
                                v-model="form.vehicle_number"
                                placeholder="e.g. DHK-METRO-11-2034"
                                class="w-full text-sm font-mono"
                                :class="{ 'p-invalid': form.errors.vehicle_number }"
                            />
                        </div>

                        <!-- Driver Info -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Driver Name <span class="text-rose-500">*</span>
                            </label>
                            <InputText
                                v-model="form.driver_name"
                                placeholder="Driver in charge"
                                class="w-full text-sm"
                                :class="{ 'p-invalid': form.errors.driver_name }"
                            />
                        </div>
                    </div>

                    <!-- Products Loading Table -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden mt-3">
                        <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-600">
                                Products to Load from Central Warehouse
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

                        <!-- 0 Stock Warning Alert -->
                        <div v-if="products.length === 0" class="m-3 p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-800 text-xs flex items-center gap-2">
                            <i class="pi pi-exclamation-circle text-rose-600 text-base"></i>
                            <span>
                                <strong>0 Central Warehouse Stock:</strong> No products are available in the central warehouse to load. You must first record a <strong>Purchase from a manufacturing supplier</strong> (under Manufacturing Suppliers &gt; Purchases) to ingest stock into Mominul Bekari warehouse before loading vehicles.
                            </span>
                        </div>

                        <div class="p-3 space-y-3">
                            <div
                                v-for="(item, idx) in form.items"
                                :key="idx"
                                class="grid grid-cols-12 gap-2 items-center bg-white p-2.5 rounded-lg border border-slate-100 shadow-sm"
                            >
                                <div class="col-span-6">
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
                                    <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">Depot Stock</label>
                                    <div class="font-mono text-xs font-bold text-slate-700 bg-slate-100 py-1.5 px-2 rounded text-center">
                                        {{ Number(item.available_stock).toFixed(0) }}
                                    </div>
                                </div>

                                <div class="col-span-3">
                                    <label class="text-[10px] uppercase font-semibold text-slate-400 block mb-0.5">Load Quantity</label>
                                    <InputText
                                        v-model.number="item.loaded_quantity"
                                        type="number"
                                        min="0.01"
                                        step="any"
                                        :class="[
                                            'w-full text-xs py-1 text-center font-bold',
                                            Number(item.loaded_quantity) > Number(item.available_stock)
                                                ? 'border-rose-500 text-rose-600'
                                                : ''
                                        ]"
                                    />
                                    <div v-if="Number(item.loaded_quantity) > Number(item.available_stock)" class="text-[10px] text-rose-500 font-medium">
                                        Exceeds available!
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

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Trip Notes / Special Instructions
                        </label>
                        <Textarea
                            v-model="form.notes"
                            rows="2"
                            placeholder="Road inspection notes, helper driver, vehicle condition..."
                            class="w-full text-sm"
                        />
                    </div>

                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800 flex items-start gap-2">
                        <i class="pi pi-info-circle text-amber-600 mt-0.5"></i>
                        <span>
                            Loading this vehicle immediately transfers goods from Central Warehouse stock into Vehicle In-Transit inventory via <strong>LOAD_TO_VEHICLE</strong> movement.
                        </span>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                        <Button label="Cancel" severity="secondary" text @click="showTripModal = false" />
                        <Button
                            label="Confirm & Load Vehicle"
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
