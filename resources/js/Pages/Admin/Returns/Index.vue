<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
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
    returns: {
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
    products: {
        type: Array,
        default: () => [],
    },
    suppliers: {
        type: Array,
        default: () => [],
    },
    metrics: {
        type: Object,
        default: () => ({
            total_damage_deductions: 0,
            total_exchange_deductions: 0,
            quarantine_scrap_quantity: 0,
            repack_holding_quantity: 0,
            repack_holding_weight: 0,
            repack_holding_pieces: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ return_type: null, road_id: null, retailer_id: null, product_id: null, date_from: '', date_to: '' }),
    },
});

const returnTypeFilter = ref(props.filters.return_type || null);
const selectedRoadFilter = ref(props.filters.road_id || null);
const selectedRetailerFilter = ref(props.filters.retailer_id || null);
const selectedProductFilter = ref(props.filters.product_id || null);
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const showClaimModal = ref(false);

const claimForm = useForm({
    supplier_id: null,
    product_id: null,
    quantity: 10,
    unit_cost: 0,
    reason: 'EXPIRED_OR_DEFECTIVE',
    notes: '',
});

const filterData = () => {
    router.get(
        '/damage-returns',
        {
            return_type: returnTypeFilter.value || undefined,
            road_id: selectedRoadFilter.value || undefined,
            retailer_id: selectedRetailerFilter.value || undefined,
            product_id: selectedProductFilter.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const openClaimModal = () => {
    claimForm.reset();
    claimForm.clearErrors();
    if (props.suppliers.length > 0) claimForm.supplier_id = props.suppliers[0].id;
    if (props.products.length > 0) {
        claimForm.product_id = props.products[0].id;
        claimForm.unit_cost = Number(props.products[0].cost_price || 0);
    }
    showClaimModal.value = true;
};

const resetClaimForm = () => {
    claimForm.reset();
    claimForm.clearErrors();
    showClaimModal.value = false;
};

const submitClaim = () => {
    claimForm.post('/damage-returns/claims', {
        onSuccess: () => {
            resetClaimForm();
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Returns & Product Exchanges Audit Report" />

        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                        <i class="pi pi-replay text-indigo-600"></i>
                        Returns & Product Exchanges Engine
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Audit trail distinguishing <strong>Scenario 1: Damage / Expiry Returns</strong> (routed to scrap quarantine) from <strong>Scenario 2: Product Exchanges</strong> (good stock restored to inventory).
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Button
                        label="File Supplier Return Claim"
                        icon="pi pi-send"
                        severity="secondary"
                        outlined
                        @click="openClaimModal"
                    />
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-500">Scenario 1: Damage Credits</span>
                        <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
                            <i class="pi pi-times-circle text-lg"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-rose-600 mt-2">
                        ৳ {{ Number(metrics.total_damage_deductions).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-xs text-slate-400 mt-1">Deducted on memos & routed to scrap</div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-blue-600">Scenario 2: Exchange Credits</span>
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                            <i class="pi pi-sync text-lg"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-blue-600 mt-2">
                        ৳ {{ Number(metrics.total_exchange_deductions).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-xs text-slate-400 mt-1">Offset on memos against new items</div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-600">Quarantine Scrap</span>
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                            <i class="pi pi-trash text-lg"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-800 mt-2">
                        {{ Number(metrics.quarantine_scrap_quantity).toLocaleString() }} units
                    </div>
                    <div class="text-xs text-slate-400 mt-1">Awaiting factory claim or write-off</div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Repack Bay Holding</span>
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                            <i class="pi pi-box text-lg"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-indigo-700 mt-2">
                        {{ Number(metrics.repack_holding_quantity || 0).toFixed(2) }} <span class="text-sm font-semibold text-slate-500">units</span>
                    </div>
                    <div class="text-xs text-slate-400 mt-1">
                        <span v-if="Number(metrics.repack_holding_pieces || 0) > 0">{{ Number(metrics.repack_holding_pieces).toLocaleString() }} loose pcs</span>
                        <span v-if="Number(metrics.repack_holding_pieces || 0) > 0 && Number(metrics.repack_holding_weight || 0) > 0"> &bull; </span>
                        <span v-if="Number(metrics.repack_holding_weight || 0) > 0">{{ Number(metrics.repack_holding_weight || 0).toFixed(2) }} kg loose</span>
                        <span v-if="!Number(metrics.repack_holding_pieces || 0) && !Number(metrics.repack_holding_weight || 0)">Loose packages held</span>
                    </div>
                </div>
            </div>

            <!-- Filters Toolbar -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-wrap items-center gap-3">
                <div class="w-48">
                    <Select
                        v-model="returnTypeFilter"
                        :options="[
                            { label: 'All Returns & Exchanges', value: null },
                            { label: 'Scenario 1: Damage / Expiry', value: 'DAMAGE' },
                            { label: 'Scenario 2: Product Exchange', value: 'EXCHANGE' },
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="All Types"
                        class="w-full text-sm"
                        @change="filterData"
                    />
                </div>

                <div class="w-44">
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
                        v-model="selectedProductFilter"
                        :options="products"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="All Products"
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

            <!-- Returns Table -->
            <AppDataTable
                :items="returns.data || []"
                :total="returns.total || 0"
                :perPage="returns.per_page || 25"
                :currentPage="returns.current_page || 1"
                tableTitle="Recorded Returns & Exchanges Audit Log"
            >
                <Column field="created_at" header="Date" style="width: 120px">
                    <template #body="{ data }">
                        <span class="text-xs text-slate-700 font-medium">
                            {{ formatDate(data.created_at) }}
                        </span>
                    </template>
                </Column>

                <Column field="sales_memo.memo_no" header="Memo #" style="width: 140px">
                    <template #body="{ data }">
                        <span class="font-mono text-xs font-semibold text-slate-800 bg-slate-100 px-2 py-1 rounded">
                            {{ data.sales_memo?.memo_no || 'Direct Return' }}
                        </span>
                    </template>
                </Column>

                <Column field="retailer.shop_name" header="Retailer Shop">
                    <template #body="{ data }">
                        <div class="font-bold text-slate-800 text-xs">{{ data.retailer?.shop_name }}</div>
                        <div class="text-[11px] text-slate-400">{{ data.retailer?.owner_name }}</div>
                    </template>
                </Column>

                <Column field="product.name" header="Product Returned">
                    <template #body="{ data }">
                        <div class="font-semibold text-slate-800 text-xs">{{ data.product?.name }}</div>
                        <div class="font-mono text-[10px] text-slate-400">{{ data.product?.code }}</div>
                    </template>
                </Column>

                <Column field="return_type" header="Return Nature" style="width: 190px">
                    <template #body="{ data }">
                        <Tag
                            :value="data.return_type === 'DAMAGE' ? 'Damage / Expiry' : (data.damage_type === 'BROKEN_PACKET' ? 'Broken Pack Exchange' : 'Product Exchange')"
                            :severity="data.return_type === 'DAMAGE' ? 'danger' : (data.damage_type === 'BROKEN_PACKET' ? 'warn' : 'info')"
                            rounded
                            class="text-[10px]"
                        />
                        <div class="text-[10px] mt-0.5">
                            <span v-if="data.destination_location === 'REPACK_HOLDING'" class="text-indigo-600 font-semibold">
                                Repack Bay Holding
                            </span>
                            <span v-else-if="data.affects_sellable_stock" class="text-emerald-600 font-semibold">
                                Stock Increased
                            </span>
                            <span v-else class="text-rose-500 font-semibold">
                                Quarantine Scrap
                            </span>
                        </div>
                    </template>
                </Column>

                <Column field="quantity" header="Qty / Units" style="width: 130px">
                    <template #body="{ data }">
                        <template v-if="data.return_unit === 'PCS' && data.sub_unit_quantity">
                            <span class="font-mono font-bold text-sm text-slate-900">
                                {{ Number(data.sub_unit_quantity).toFixed(0) }} pcs
                            </span>
                            <span class="block text-[10px] text-slate-400 font-normal">
                                ≈ {{ Number(data.quantity).toFixed(2) }} ctn eq
                            </span>
                        </template>
                        <template v-else-if="data.return_unit === 'KG' && data.sub_unit_quantity">
                            <span class="font-mono font-bold text-sm text-slate-900">
                                {{ Number(data.sub_unit_quantity).toFixed(2) }} kg
                            </span>
                            <span class="block text-[10px] text-slate-400 font-normal">
                                ≈ {{ Number(data.quantity).toFixed(2) }} pkt eq
                            </span>
                        </template>
                        <template v-else>
                            <span class="font-mono font-bold text-sm text-slate-900">
                                {{ Number(data.quantity).toFixed(0) }} {{ data.product?.packaging_type === 'CARTOON' ? 'ctn' : 'pkt' }}
                            </span>
                        </template>
                    </template>
                </Column>

                <Column field="unit_deduction_rate" header="Deduct Rate" style="width: 120px">
                    <template #body="{ data }">
                        <span class="font-mono text-xs text-slate-600">
                            ৳ {{ Number(data.unit_deduction_rate).toFixed(2) }}
                        </span>
                        <span class="text-[10px] text-slate-400 block font-normal">
                            / {{ data.return_unit === 'PCS' ? 'pc' : (data.return_unit === 'KG' ? 'kg' : (data.product?.packaging_type === 'CARTOON' ? 'ctn' : 'pkt')) }}
                        </span>
                    </template>
                </Column>

                <Column field="total_deduction_amount" header="Total Credit (৳)" style="width: 140px">
                    <template #body="{ data }">
                        <span class="font-mono font-bold text-sm text-rose-600">
                            - ৳ {{ Number(data.total_deduction_amount).toFixed(2) }}
                        </span>
                    </template>
                </Column>
            </AppDataTable>

            <!-- File Supplier Return Claim Modal -->
            <Dialog
                v-model:visible="showClaimModal"
                header="File Supplier Return Claim (Factory Defect / Expiry)"
                modal
                @hide="resetClaimForm"
                :style="{ width: '560px' }"
                class="p-fluid"
            >
                <form @submit.prevent="submitClaim" class="space-y-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Supplier / Manufacturer <span class="text-rose-500">*</span>
                        </label>
                        <Select
                            v-model="claimForm.supplier_id"
                            :options="suppliers"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select Supplier"
                            class="w-full text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Defective / Expired Product <span class="text-rose-500">*</span>
                        </label>
                        <Select
                            v-model="claimForm.product_id"
                            :options="products"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select Product"
                            class="w-full text-sm"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Claim Quantity <span class="text-rose-500">*</span>
                            </label>
                            <InputText
                                v-model.number="claimForm.quantity"
                                type="number"
                                min="0.01"
                                class="w-full text-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Unit Cost (৳) <span class="text-rose-500">*</span>
                            </label>
                            <CurrencyInput v-model="claimForm.unit_cost" placeholder="0.00" class="w-full" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Reason for Claim <span class="text-rose-500">*</span>
                        </label>
                        <InputText
                            v-model="claimForm.reason"
                            placeholder="e.g. Broken master carton packaging, expired shelf life"
                            class="w-full text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Additional Claim Notes</label>
                        <Textarea v-model="claimForm.notes" rows="2" placeholder="Batch number or factory date..." class="w-full text-sm" />
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                        <Button label="Cancel" severity="secondary" text @click="showClaimModal = false" />
                        <Button label="Submit Claim" icon="pi pi-check" type="submit" severity="primary" :loading="claimForm.processing" />
                    </div>
                </form>
            </Dialog>
        </div>
    </AppLayout>
</template>
