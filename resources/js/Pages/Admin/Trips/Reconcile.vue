<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import Message from 'primevue/message';

const props = defineProps({
    trip: {
        type: Object,
        required: true,
    },
    items: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
});

const isCompleted = computed(() => props.trip.status === 'COMPLETED');

// Initialize returned quantities with expected values or previously saved values
const reconciliationItems = ref(
    props.items.map((item) => ({
        product_id: item.product_id,
        product_name: item.product_name,
        product_code: item.product_code,
        packaging_type: item.packaging_type,
        wholesale_price: item.wholesale_price,
        loaded_quantity: item.loaded_quantity,
        sold_quantity: item.sold_quantity,
        damaged_quantity: item.damaged_quantity,
        exchanged_quantity: item.exchanged_quantity,
        expected_return: item.expected_return,
        returned_quantity: isCompleted.value ? item.returned_quantity : item.expected_return,
    }))
);

const form = useForm({
    notes: props.trip.notes || '',
    items: [],
});

const totalLoaded = computed(() =>
    reconciliationItems.value.reduce((acc, i) => acc + (Number(i.loaded_quantity) || 0), 0)
);

const totalSold = computed(() =>
    reconciliationItems.value.reduce((acc, i) => acc + (Number(i.sold_quantity) || 0), 0)
);

const totalExchanged = computed(() =>
    reconciliationItems.value.reduce((acc, i) => acc + (Number(i.exchanged_quantity) || 0), 0)
);

const totalDamaged = computed(() =>
    reconciliationItems.value.reduce((acc, i) => acc + (Number(i.damaged_quantity) || 0), 0)
);

const totalExpected = computed(() =>
    reconciliationItems.value.reduce((acc, i) => acc + (Number(i.expected_return) || 0), 0)
);

const totalReturned = computed(() =>
    reconciliationItems.value.reduce((acc, i) => acc + (Number(i.returned_quantity) || 0), 0)
);

const totalVariance = computed(() => {
    return reconciliationItems.value.reduce((acc, i) => {
        const loaded = Number(i.loaded_quantity) || 0;
        const exchanged = Number(i.exchanged_quantity) || 0;
        const sold = Number(i.sold_quantity) || 0;
        const returned = Number(i.returned_quantity) || 0;
        return acc + ((loaded + exchanged) - (sold + returned));
    }, 0);
});

const hasDiscrepancy = computed(() => Math.abs(totalVariance.value) > 0.001);

const calculateVariance = (item) => {
    const loaded = Number(item.loaded_quantity) || 0;
    const exchanged = Number(item.exchanged_quantity) || 0;
    const sold = Number(item.sold_quantity) || 0;
    const returned = Number(item.returned_quantity) || 0;
    return Number(((loaded + exchanged) - (sold + returned)).toFixed(2));
};

const submitReconciliation = () => {
    form.items = reconciliationItems.value.map((item) => ({
        product_id: item.product_id,
        returned_quantity: Number(item.returned_quantity) || 0,
    }));

    form.post(route('trips.reconcile.store', props.trip.id));
};

const printSheet = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Reconciliation - ${trip.trip_no}`" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Top Action Bar -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 print:hidden">
                <div class="flex items-center gap-3">
                    <Link :href="route('trips.index')">
                        <Button icon="pi pi-arrow-left" severity="secondary" rounded outlined size="small" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-2xl font-bold tracking-tight text-slate-900 font-mono">
                                {{ trip.trip_no }}
                            </h1>
                            <Tag
                                :value="trip.status"
                                :severity="trip.status === 'COMPLETED' ? 'success' : 'warn'"
                                rounded
                            />
                        </div>
                        <p class="text-sm text-slate-500">
                            Daily Trip Closing & Unsold Inventory Return Reconciliation
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        icon="pi pi-print"
                        label="Print Settlement Slip"
                        severity="secondary"
                        outlined
                        @click="printSheet"
                    />

                    <Button
                        v-if="!isCompleted"
                        icon="pi pi-check-circle"
                        label="Confirm & Close Trip"
                        severity="primary"
                        :loading="form.processing"
                        @click="submitReconciliation"
                    />
                </div>
            </div>

            <!-- Print Header (Only visible during print) -->
            <div class="hidden print:block text-center border-b pb-4 mb-4">
                <h1 class="text-xl font-bold uppercase tracking-wider text-slate-900">Mominul Bekari Wholesale Distribution</h1>
                <p class="text-xs text-slate-600">Daily Vehicle Trip Reconciliation & Settlement Statement</p>
                <div class="flex justify-between text-xs mt-2 border-t pt-2 font-mono">
                    <span>Trip: {{ trip.trip_no }}</span>
                    <span>Date: {{ trip.trip_date }}</span>
                    <span>Road: {{ trip.road?.name }}</span>
                    <span>Vehicle: {{ trip.vehicle_number }}</span>
                    <span>Driver: {{ trip.driver_name }}</span>
                </div>
            </div>

            <!-- Status Alert if Completed -->
            <Message v-if="isCompleted" severity="success" :closable="false" class="print:hidden">
                <div class="flex items-center gap-2 text-sm font-medium">
                    <i class="pi pi-check-circle text-emerald-600"></i>
                    <span>
                        This trip was finalized and settled on <strong>{{ trip.completed_at }}</strong>. Unsold items have been deposited back into Central Warehouse inventory.
                    </span>
                </div>
            </Message>

            <!-- Trip Meta & Route Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Assigned Route</div>
                    <div class="text-base font-bold text-slate-900 mt-1 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                        {{ trip.road?.name }}
                    </div>
                    <div class="text-xs font-mono text-slate-500 mt-0.5">Route Code: {{ trip.road?.code }}</div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Vehicle & Driver</div>
                    <div class="text-base font-bold text-slate-900 mt-1">{{ trip.vehicle_number }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        Driver: {{ trip.driver_name }} <span v-if="trip.driver_phone">({{ trip.driver_phone }})</span>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Trip Date & Dispatch</div>
                    <div class="text-base font-bold text-slate-900 mt-1">{{ trip.trip_date }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        Dispatched: {{ trip.dispatched_at || 'In Depot' }}
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Reconciliation Audit</div>
                    <div class="text-base font-bold mt-1" :class="hasDiscrepancy ? 'text-rose-600' : 'text-emerald-600'">
                        {{ hasDiscrepancy ? `${Math.abs(totalVariance)} Units Variance` : '100% Balanced' }}
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        Loaded + Exchanged = Sold + Returned
                    </div>
                </div>
            </div>

            <!-- Financial Settlement KPI Ribbon -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-sm">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 mb-3 flex items-center gap-2">
                    <i class="pi pi-wallet text-amber-600"></i>
                    Financial Settlement Summary (Memos on this Trip)
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-center">
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                        <div class="text-[11px] font-medium text-slate-500">Memos Issued</div>
                        <div class="text-lg font-bold text-slate-800 mt-0.5">{{ summary.memos_count || 0 }}</div>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                        <div class="text-[11px] font-medium text-slate-500">Gross Sales</div>
                        <div class="text-lg font-bold text-slate-800 mt-0.5">৳{{ Number(summary.gross_sales || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</div>
                    </div>

                    <div class="p-3 bg-rose-50/60 rounded-lg border border-rose-100">
                        <div class="text-[11px] font-medium text-rose-700">Damage Returns</div>
                        <div class="text-lg font-bold text-rose-700 mt-0.5">-৳{{ Number(summary.damage_deductions || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</div>
                    </div>

                    <div class="p-3 bg-sky-50/60 rounded-lg border border-sky-100">
                        <div class="text-[11px] font-medium text-sky-700">Exchanges</div>
                        <div class="text-lg font-bold text-sky-700 mt-0.5">-৳{{ Number(summary.exchange_deductions || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</div>
                    </div>

                    <div class="p-3 bg-indigo-50/60 rounded-lg border border-indigo-100">
                        <div class="text-[11px] font-medium text-indigo-700">Net Sales Bill</div>
                        <div class="text-lg font-bold text-indigo-800 mt-0.5">৳{{ Number(summary.net_sales || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</div>
                    </div>

                    <div class="p-3 bg-emerald-50/60 rounded-lg border border-emerald-100">
                        <div class="text-[11px] font-medium text-emerald-700">Total Collected</div>
                        <div class="text-lg font-bold text-emerald-800 mt-0.5">৳{{ Number(summary.total_collected || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</div>
                        <div class="text-[10px] text-emerald-600 mt-0.5">
                            Cash: ৳{{ Number(summary.cash_collected || 0).toFixed(0) }} | MFS: ৳{{ Number(summary.digital_collected || 0).toFixed(0) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Reconciliation Table -->
            <div class="bg-white rounded-xl border border-slate-200/80 overflow-hidden shadow-sm">
                <div class="p-4 bg-slate-50/70 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base font-bold text-slate-800">Physical Product Stock Audit</h2>
                        <p class="text-xs text-slate-500">
                            Verify returned unsold items physically present in the vehicle before depositing to warehouse.
                        </p>
                    </div>
                    <div class="text-xs font-mono bg-white px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700">
                        Invariant: <strong>[Loaded + Exchanged] = [Sold + Returned]</strong>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-100/70 text-slate-700 font-bold text-xs uppercase tracking-wider border-b border-slate-200">
                                <th class="p-3.5">Product SKU & Name</th>
                                <th class="p-3.5 text-center">Packaging</th>
                                <th class="p-3.5 text-right">Loaded Out</th>
                                <th class="p-3.5 text-right text-emerald-700">Sold</th>
                                <th class="p-3.5 text-right text-sky-700">Exchanged (+)</th>
                                <th class="p-3.5 text-right text-rose-700">Damaged (Quarantine)</th>
                                <th class="p-3.5 text-right font-bold text-slate-800">Expected Unsold</th>
                                <th class="p-3.5 text-center w-36 bg-amber-50/60 text-amber-900 border-x border-amber-200">
                                    Actual Returned
                                </th>
                                <th class="p-3.5 text-center">Variance / Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(item, index) in reconciliationItems"
                                :key="item.product_id"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td class="p-3.5 font-medium text-slate-900">
                                    <div>{{ item.product_name }}</div>
                                    <div class="text-xs font-mono text-slate-400">{{ item.product_code }}</div>
                                </td>

                                <td class="p-3.5 text-center text-xs text-slate-600">
                                    {{ item.packaging_type }}
                                </td>

                                <td class="p-3.5 text-right font-mono text-slate-700 font-semibold">
                                    {{ item.loaded_quantity }}
                                </td>

                                <td class="p-3.5 text-right font-mono text-emerald-700 font-semibold">
                                    {{ item.sold_quantity }}
                                </td>

                                <td class="p-3.5 text-right font-mono text-sky-700 font-semibold">
                                    +{{ item.exchanged_quantity }}
                                </td>

                                <td class="p-3.5 text-right font-mono text-rose-600">
                                    {{ item.damaged_quantity }}
                                </td>

                                <td class="p-3.5 text-right font-mono font-bold text-slate-800">
                                    {{ item.expected_return }}
                                </td>

                                <!-- Actual Returned Input -->
                                <td class="p-2 text-center bg-amber-50/40 border-x border-amber-200">
                                    <div v-if="!isCompleted" class="flex justify-center">
                                        <InputNumber
                                            v-model="item.returned_quantity"
                                            :min="0"
                                            :maxFractionDigits="2"
                                            class="w-24 text-center font-mono font-bold text-sm"
                                            inputClass="text-center py-1.5 px-2 font-mono font-bold text-slate-900"
                                        />
                                    </div>
                                    <div v-else class="font-mono font-bold text-slate-900 text-center">
                                        {{ item.returned_quantity }}
                                    </div>
                                </td>

                                <!-- Variance -->
                                <td class="p-3.5 text-center font-mono text-xs font-bold">
                                    <span
                                        v-if="calculateVariance(item) === 0"
                                        class="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200"
                                    >
                                        <i class="pi pi-check text-[10px]"></i> Balanced
                                    </span>
                                    <span
                                        v-else-if="calculateVariance(item) > 0"
                                        class="inline-flex items-center gap-1 text-rose-700 bg-rose-50 px-2.5 py-1 rounded-full border border-rose-200"
                                    >
                                        <i class="pi pi-exclamation-triangle text-[10px]"></i>
                                        -{{ calculateVariance(item) }} Shortage
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 text-amber-700 bg-amber-50 px-2.5 py-1 rounded-full border border-amber-200"
                                    >
                                        <i class="pi pi-info-circle text-[10px]"></i>
                                        +{{ Math.abs(calculateVariance(item)) }} Surplus
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-slate-100/90 font-bold text-xs uppercase tracking-wider text-slate-800 border-t-2 border-slate-300">
                            <tr>
                                <td class="p-3.5" colspan="2">Consolidated Inventory Totals</td>
                                <td class="p-3.5 text-right font-mono">{{ totalLoaded.toFixed(2) }}</td>
                                <td class="p-3.5 text-right font-mono text-emerald-800">{{ totalSold.toFixed(2) }}</td>
                                <td class="p-3.5 text-right font-mono text-sky-800">+{{ totalExchanged.toFixed(2) }}</td>
                                <td class="p-3.5 text-right font-mono text-rose-800">{{ totalDamaged.toFixed(2) }}</td>
                                <td class="p-3.5 text-right font-mono text-slate-900">{{ totalExpected.toFixed(2) }}</td>
                                <td class="p-3.5 text-center font-mono text-amber-900 bg-amber-100/50 border-x border-amber-300">
                                    {{ totalReturned.toFixed(2) }}
                                </td>
                                <td class="p-3.5 text-center font-mono">
                                    <span :class="hasDiscrepancy ? 'text-rose-700' : 'text-emerald-700'">
                                        {{ hasDiscrepancy ? `${totalVariance.toFixed(2)} Net Variance` : '0 Variance' }}
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Closing Notes & Supervisor Sign-off -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Closing & Reconciliation Audit Notes
                    </label>
                    <Textarea
                        v-if="!isCompleted"
                        v-model="form.notes"
                        rows="3"
                        placeholder="Add remarks regarding road delays, physical discrepancies, or driver cash handover..."
                        class="w-full text-sm"
                    />
                    <div v-else class="text-sm text-slate-700 bg-slate-50 p-3 rounded-lg border border-slate-100">
                        {{ trip.notes || 'No notes recorded.' }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Driver & Storekeeper Signatures</h4>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Both parties confirm that physical counts match returned inventory deposited to depot.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-6 pt-8 text-center text-xs text-slate-600 border-t border-slate-100 mt-4">
                        <div>
                            <div class="border-b border-slate-300 pb-1 mb-1 font-semibold text-slate-800">
                                {{ trip.driver_name }}
                            </div>
                            <span>Driver Signature</span>
                        </div>
                        <div>
                            <div class="border-b border-slate-300 pb-1 mb-1 font-semibold text-slate-800">
                                Storekeeper / Manager
                            </div>
                            <span>Depot Receiver Signature</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    body {
        background: white !important;
        font-size: 11px !important;
    }
    .print\:hidden {
        display: none !important;
    }
    .print\:block {
        display: block !important;
    }
}
</style>
