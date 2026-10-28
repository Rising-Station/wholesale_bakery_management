<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Tag from 'primevue/tag';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    date_from: {
        type: String,
        required: true,
    },
    date_to: {
        type: String,
        required: true,
    },
    roads: {
        type: Array,
        default: () => [],
    },
    road_reports: {
        type: Object,
        default: () => ({}),
    },
    consolidated: {
        type: Object,
        default: () => ({}),
    },
    selected_road_id: {
        type: Number,
        default: null,
    },
});

const dateFrom = ref(props.date_from);
const dateTo = ref(props.date_to);

const activeTab = ref(props.selected_road_id ? String(props.selected_road_id) : 'all');

const applyFilter = () => {
    router.get(
        route('reports.road-settlement'),
        {
            date_from: dateFrom.value,
            date_to: dateTo.value,
            road_id: activeTab.value !== 'all' ? activeTab.value : null,
        },
        { preserveState: true }
    );
};

const setDatePreset = (preset) => {
    const today = new Date();
    if (preset === 'today') {
        const d = today.toISOString().split('T')[0];
        dateFrom.value = d;
        dateTo.value = d;
    } else if (preset === 'yesterday') {
        const y = new Date(today);
        y.setDate(today.getDate() - 1);
        const d = y.toISOString().split('T')[0];
        dateFrom.value = d;
        dateTo.value = d;
    } else if (preset === 'this_week') {
        const start = new Date(today);
        start.setDate(today.getDate() - today.getDay());
        dateFrom.value = start.toISOString().split('T')[0];
        dateTo.value = today.toISOString().split('T')[0];
    } else if (preset === 'this_month') {
        const start = new Date(today.getFullYear(), today.getMonth(), 1);
        dateFrom.value = start.toISOString().split('T')[0];
        dateTo.value = today.toISOString().split('T')[0];
    }
    applyFilter();
};

const printReport = () => {
    window.print();
};
</script>

<template>
    <Head title="Road-Wise Sales & Settlement Dashboard" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header & Filter Bar -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 print:hidden">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                        Road-Wise Sales & Settlement
                    </h1>
                    <p class="text-sm text-slate-500">
                        Comprehensive financial ledger and inventory flow analytics by distribution route (Road A, B, C).
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        icon="pi pi-print"
                        label="Print Settlement Sheet"
                        severity="secondary"
                        outlined
                        @click="printReport"
                    />
                </div>
            </div>

            <!-- Print Header -->
            <div class="hidden print:block text-center border-b pb-4 mb-4">
                <h1 class="text-xl font-bold uppercase tracking-wider text-slate-900">Mominul Bekari Wholesale Distribution</h1>
                <p class="text-xs text-slate-600">Road-Wise Daily Settlement & Performance Audit</p>
                <div class="text-xs mt-1 text-slate-500">Period: {{ date_from }} to {{ date_to }}</div>
            </div>

            <!-- Date Filter Ribbon -->
            <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm flex flex-wrap items-center justify-between gap-3 print:hidden">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500 uppercase">Preset:</span>
                    <Button label="Today" size="small" text severity="secondary" @click="setDatePreset('today')" />
                    <Button label="Yesterday" size="small" text severity="secondary" @click="setDatePreset('yesterday')" />
                    <Button label="This Week" size="small" text severity="secondary" @click="setDatePreset('this_week')" />
                    <Button label="This Month" size="small" text severity="secondary" @click="setDatePreset('this_month')" />
                </div>

                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5 text-xs text-slate-600">
                        <span>From:</span>
                        <InputText v-model="dateFrom" type="date" class="text-xs py-1.5 px-2.5 w-36" />
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-600">
                        <span>To:</span>
                        <InputText v-model="dateTo" type="date" class="text-xs py-1.5 px-2.5 w-36" />
                    </div>
                    <Button icon="pi pi-filter" label="Filter" size="small" severity="primary" @click="applyFilter" />
                </div>
            </div>

            <!-- Tabbed Road Interface -->
            <Tabs v-model:value="activeTab">
                <TabList class="bg-white rounded-t-xl border-x border-t border-slate-200/80">
                    <Tab value="all" class="font-bold text-sm">
                        <i class="pi pi-chart-bar mr-1.5 text-indigo-600"></i>
                        Grand Consolidated Total
                    </Tab>
                    <Tab v-for="road in roads" :key="road.id" :value="String(road.id)" class="font-bold text-sm">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 mr-1.5"></span>
                        {{ road.code }} ({{ road.name }})
                    </Tab>
                </TabList>

                <TabPanels class="bg-white rounded-b-xl border border-slate-200/80 p-6 shadow-sm">
                    <!-- Grand Total Panel -->
                    <TabPanel value="all">
                        <div class="space-y-6">
                            <!-- Financial Metric Cards -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Gross Sales</div>
                                    <div class="text-2xl font-bold text-slate-900 mt-1">
                                        ৳{{ Number(consolidated.gross_sales || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                    </div>
                                    <div class="text-xs text-slate-500 mt-0.5">{{ consolidated.memos_count }} Sales Memos</div>
                                </div>

                                <div class="p-4 bg-emerald-50/60 rounded-xl border border-emerald-100">
                                    <div class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Total Collections</div>
                                    <div class="text-2xl font-bold text-emerald-800 mt-1">
                                        ৳{{ Number(consolidated.total_collected || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                    </div>
                                    <div class="text-xs text-emerald-600 mt-0.5">
                                        Cash: ৳{{ Number(consolidated.cash_collected || 0).toFixed(0) }} | MFS: ৳{{ Number(consolidated.digital_collected || 0).toFixed(0) }}
                                    </div>
                                </div>

                                <div class="p-4 bg-amber-50/60 rounded-xl border border-amber-100">
                                    <div class="text-xs font-semibold text-amber-700 uppercase tracking-wider">New Dues Created</div>
                                    <div class="text-2xl font-bold text-amber-800 mt-1">
                                        ৳{{ Number(consolidated.dues_generated || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                    </div>
                                    <div class="text-xs text-amber-600 mt-0.5">Deferred Credit</div>
                                </div>

                                <div class="p-4 bg-rose-50/60 rounded-xl border border-rose-100">
                                    <div class="text-xs font-semibold text-rose-700 uppercase tracking-wider">All Routes Outstanding Due</div>
                                    <div class="text-2xl font-bold text-rose-800 mt-1">
                                        ৳{{ Number(consolidated.total_current_due || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                    </div>
                                    <div class="text-xs text-rose-600 mt-0.5">{{ consolidated.active_retailers_count }} Active Shops</div>
                                </div>
                            </div>

                            <!-- Inventory Flow Summary -->
                            <div class="border rounded-xl p-4 bg-slate-50/50">
                                <h3 class="text-sm font-bold text-slate-800 mb-3 uppercase tracking-wider">
                                    Consolidated Inventory Movement (All Routes)
                                </h3>
                                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-center">
                                    <div class="bg-white p-3 rounded-lg border border-slate-200">
                                        <div class="text-xs text-slate-500 font-medium">Total Loaded</div>
                                        <div class="text-lg font-bold text-slate-800 mt-0.5">{{ consolidated.total_loaded_qty.toFixed(2) }}</div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-slate-200">
                                        <div class="text-xs text-emerald-600 font-medium">Total Sold</div>
                                        <div class="text-lg font-bold text-emerald-700 mt-0.5">{{ consolidated.total_sold_qty.toFixed(2) }}</div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-slate-200">
                                        <div class="text-xs text-sky-600 font-medium">Exchanges (+)</div>
                                        <div class="text-lg font-bold text-sky-700 mt-0.5">+{{ consolidated.total_exchanged_qty.toFixed(2) }}</div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-slate-200">
                                        <div class="text-xs text-rose-600 font-medium">Damaged (Scrap)</div>
                                        <div class="text-lg font-bold text-rose-700 mt-0.5">{{ consolidated.total_damaged_qty.toFixed(2) }}</div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-slate-200">
                                        <div class="text-xs text-amber-600 font-medium">Returned to Depot</div>
                                        <div class="text-lg font-bold text-amber-700 mt-0.5">{{ consolidated.total_returned_qty.toFixed(2) }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Route Comparison Table -->
                            <div class="border rounded-xl overflow-hidden">
                                <div class="p-3 bg-slate-100 font-bold text-xs uppercase tracking-wider text-slate-700">
                                    Route Performance Breakdown
                                </div>
                                <table class="w-full text-left text-sm">
                                    <thead>
                                        <tr class="border-b bg-slate-50 text-xs font-semibold text-slate-600">
                                            <th class="p-3">Route</th>
                                            <th class="p-3 text-center">Trips</th>
                                            <th class="p-3 text-right">Gross Sales</th>
                                            <th class="p-3 text-right">Net Sales</th>
                                            <th class="p-3 text-right text-emerald-700">Collected</th>
                                            <th class="p-3 text-right text-amber-700">New Due</th>
                                            <th class="p-3 text-right text-rose-700">Total Route Due</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="road in roads" :key="road.id" class="hover:bg-slate-50/70">
                                            <td class="p-3 font-semibold text-slate-800">
                                                <div>{{ road.name }}</div>
                                                <div class="text-xs font-mono text-slate-400">{{ road.code }}</div>
                                            </td>
                                            <td class="p-3 text-center font-mono">
                                                {{ road_reports[road.id]?.total_trips || 0 }} ({{ road_reports[road.id]?.completed_trips || 0 }} closed)
                                            </td>
                                            <td class="p-3 text-right font-mono">
                                                ৳{{ Number(road_reports[road.id]?.gross_sales || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                            </td>
                                            <td class="p-3 text-right font-mono font-bold text-slate-900">
                                                ৳{{ Number(road_reports[road.id]?.net_sales || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                            </td>
                                            <td class="p-3 text-right font-mono text-emerald-700 font-semibold">
                                                ৳{{ Number(road_reports[road.id]?.total_collected || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                            </td>
                                            <td class="p-3 text-right font-mono text-amber-700">
                                                ৳{{ Number(road_reports[road.id]?.dues_generated || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                            </td>
                                            <td class="p-3 text-right font-mono text-rose-700 font-bold">
                                                ৳{{ Number(road_reports[road.id]?.total_current_due || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- Individual Road Tab Panels -->
                    <TabPanel v-for="road in roads" :key="road.id" :value="String(road.id)">
                        <div class="space-y-6">
                            <!-- Road Details Card -->
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center p-4 bg-slate-50 rounded-xl border border-slate-100 gap-3">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full bg-indigo-600"></span>
                                        {{ road.name }} ({{ road.code }})
                                    </h2>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        Assigned Vehicle: {{ road.vehicle_number || 'None' }} | Driver: {{ road.driver_name || 'None' }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-slate-500">Retailers on this Route</div>
                                    <div class="text-base font-bold text-slate-800">
                                        {{ road_reports[road.id]?.active_retailers_count || 0 }} Shops
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Metrics -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                                    <div class="text-xs font-semibold text-slate-500 uppercase">Gross Sales</div>
                                    <div class="text-xl font-bold text-slate-900 mt-1">
                                        ৳{{ Number(road_reports[road.id]?.gross_sales || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                    </div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        {{ road_reports[road.id]?.memos_count || 0 }} Memos
                                    </div>
                                </div>

                                <div class="p-4 bg-emerald-50/60 rounded-xl border border-emerald-100">
                                    <div class="text-xs font-semibold text-emerald-700 uppercase">Collected</div>
                                    <div class="text-xl font-bold text-emerald-800 mt-1">
                                        ৳{{ Number(road_reports[road.id]?.total_collected || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                    </div>
                                    <div class="text-xs text-emerald-600 mt-0.5">
                                        Cash: ৳{{ Number(road_reports[road.id]?.cash_collected || 0).toFixed(0) }} | MFS: ৳{{ Number(road_reports[road.id]?.digital_collected || 0).toFixed(0) }}
                                    </div>
                                </div>

                                <div class="p-4 bg-amber-50/60 rounded-xl border border-amber-100">
                                    <div class="text-xs font-semibold text-amber-700 uppercase">New Dues Created</div>
                                    <div class="text-xl font-bold text-amber-800 mt-1">
                                        ৳{{ Number(road_reports[road.id]?.dues_generated || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                    </div>
                                    <div class="text-xs text-amber-600 mt-0.5">Deferred Credit</div>
                                </div>

                                <div class="p-4 bg-rose-50/60 rounded-xl border border-rose-100">
                                    <div class="text-xs font-semibold text-rose-700 uppercase">Route Outstanding Due</div>
                                    <div class="text-xl font-bold text-rose-800 mt-1">
                                        ৳{{ Number(road_reports[road.id]?.total_current_due || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                    </div>
                                    <div class="text-xs text-rose-600 mt-0.5">Accumulated Shop Dues</div>
                                </div>
                            </div>

                            <!-- Inventory Movement on Road -->
                            <div class="border rounded-xl p-4 bg-slate-50/50">
                                <h3 class="text-sm font-bold text-slate-800 mb-3 uppercase tracking-wider">
                                    Inventory Handled on {{ road.name }}
                                </h3>
                                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-center">
                                    <div class="bg-white p-3 rounded-lg border border-slate-200">
                                        <div class="text-xs text-slate-500 font-medium">Loaded Out</div>
                                        <div class="text-lg font-bold text-slate-800 mt-0.5">
                                            {{ (road_reports[road.id]?.total_loaded_qty || 0).toFixed(2) }}
                                        </div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-slate-200">
                                        <div class="text-xs text-emerald-600 font-medium">Sold Out</div>
                                        <div class="text-lg font-bold text-emerald-700 mt-0.5">
                                            {{ (road_reports[road.id]?.total_sold_qty || 0).toFixed(2) }}
                                        </div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-slate-200">
                                        <div class="text-xs text-sky-600 font-medium">Exchanges (+)</div>
                                        <div class="text-lg font-bold text-sky-700 mt-0.5">
                                            +{{ (road_reports[road.id]?.total_exchanged_qty || 0).toFixed(2) }}
                                        </div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-slate-200">
                                        <div class="text-xs text-rose-600 font-medium">Damaged Return</div>
                                        <div class="text-lg font-bold text-rose-700 mt-0.5">
                                            {{ (road_reports[road.id]?.total_damaged_qty || 0).toFixed(2) }}
                                        </div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-slate-200">
                                        <div class="text-xs text-amber-600 font-medium">Returned Unsold</div>
                                        <div class="text-lg font-bold text-amber-700 mt-0.5">
                                            {{ (road_reports[road.id]?.total_returned_qty || 0).toFixed(2) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Trips Dispatched on this Road -->
                            <div class="border rounded-xl overflow-hidden">
                                <div class="p-3 bg-slate-100 font-bold text-xs uppercase tracking-wider text-slate-700">
                                    Dispatched Trips on this Route
                                </div>
                                <table class="w-full text-left text-sm">
                                    <thead>
                                        <tr class="border-b bg-slate-50 text-xs font-semibold text-slate-600">
                                            <th class="p-3">Trip #</th>
                                            <th class="p-3">Date</th>
                                            <th class="p-3">Driver & Vehicle</th>
                                            <th class="p-3 text-center">Status</th>
                                            <th class="p-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr
                                            v-for="trip in road_reports[road.id]?.trips_list || []"
                                            :key="trip.id"
                                            class="hover:bg-slate-50/70"
                                        >
                                            <td class="p-3 font-mono font-bold text-slate-800">
                                                {{ trip.trip_no }}
                                            </td>
                                            <td class="p-3 text-slate-600">{{ formatDate(trip.trip_date) }}</td>
                                            <td class="p-3">
                                                <div class="font-medium text-slate-800">{{ trip.driver_name }}</div>
                                                <div class="text-xs text-slate-500 font-mono">{{ trip.vehicle_number }}</div>
                                            </td>
                                            <td class="p-3 text-center">
                                                <Tag
                                                    :value="trip.status"
                                                    :severity="trip.status === 'COMPLETED' ? 'success' : 'warn'"
                                                    rounded
                                                    class="text-[10px]"
                                                />
                                            </td>
                                            <td class="p-3 text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    <Link :href="route('trips.show', trip.id)">
                                                        <Button icon="pi pi-print" label="Gate Pass" size="small" text severity="secondary" />
                                                    </Link>
                                                    <Link :href="route('trips.reconcile', trip.id)">
                                                        <Button
                                                            :icon="trip.status === 'COMPLETED' ? 'pi pi-file' : 'pi pi-check-circle'"
                                                            :label="trip.status === 'COMPLETED' ? 'Settlement' : 'Reconcile'"
                                                            size="small"
                                                            text
                                                            :severity="trip.status === 'COMPLETED' ? 'info' : 'primary'"
                                                        />
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="!road_reports[road.id]?.trips_list?.length">
                                            <td colspan="5" class="p-4 text-center text-xs text-slate-400">
                                                No trips recorded on this route for the selected period.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </TabPanel>
                </TabPanels>
            </Tabs>
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
