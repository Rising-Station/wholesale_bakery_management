<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            todaySales: 124500,
            todayCollected: 98000,
            totalRetailerDue: 450200,
            totalSupplierDue: 215000,
            activeTripsCount: 3,
            lowStockItemsCount: 2,
        }),
    },
    roads: {
        type: Array,
        default: () => [
            { id: 1, name: 'Road A (Main Bazaar & Commercial)', code: 'RD-A', vehicle: 'DHK-METRO-11-2034', driver: 'Rafiqul Islam', shops: 100, loadedValue: 85000, status: 'DISPATCHED' },
            { id: 2, name: 'Road B (East Bypass & Station)', code: 'RD-B', vehicle: 'DHK-METRO-14-5512', driver: 'Anwar Hossain', shops: 50, loadedValue: 52000, status: 'DISPATCHED' },
            { id: 3, name: 'Road C (North Upazila Link)', code: 'RD-C', vehicle: 'DHK-METRO-19-8801', driver: 'Kabir Ahmed', shops: 75, loadedValue: 68000, status: 'LOADED' },
        ],
    },
});

const formatMoney = (val) => {
    return '৳ ' + Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
};
</script>

<template>
    <Head title="Dashboard - Distribution Hub" />

    <AppLayout title="Wholesale Distribution Hub">
        <div class="space-y-6">
            <!-- Distribution Chain Banner -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-amber-950 p-6 lg:p-8 text-white shadow-xl border border-slate-700/60">
                <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div class="space-y-2 max-w-2xl">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 text-xs font-semibold uppercase tracking-wider border border-amber-500/30">
                            <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                            3-Tier Wholesale Supply Chain
                        </div>
                        <h2 class="text-2xl lg:text-3xl font-extrabold tracking-tight">
                            Mominul Bekari Central Distribution
                        </h2>
                        <p class="text-slate-300 text-xs sm:text-sm leading-relaxed">
                            Suppliers &rarr; <strong>Mominul Bekari Hub</strong> &rarr; Retailers (Road A, Road B, Road C). Real-time inventory tracking, mobile vehicle loading, automated damage settlement, and dual-ledger balances.
                        </p>
                    </div>

                    <!-- Quick Actions -->
                    <div class="flex flex-wrap gap-2.5 w-full md:w-auto">
                        <Link
                            href="/memos/create"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs transition shadow-lg shadow-amber-500/20"
                        >
                            <i class="pi pi-receipt"></i>
                            <span>Create Memo</span>
                        </Link>
                        <Link
                            href="/trips/create"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs border border-slate-700 transition"
                        >
                            <i class="pi pi-truck"></i>
                            <span>Load Trip</span>
                        </Link>
                        <Link
                            href="/purchases/create"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs border border-slate-700 transition"
                        >
                            <i class="pi pi-download"></i>
                            <span>Procure Stock</span>
                        </Link>
                    </div>
                </div>

                <!-- Subtle pattern glow -->
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
            </div>

            <!-- Key Metrics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1 -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200/90 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Today's Sales</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i class="pi pi-shopping-bag text-base"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-2xl font-black text-slate-900">{{ formatMoney(stats.todaySales) }}</div>
                        <div class="text-[11px] text-emerald-600 font-medium mt-1 flex items-center gap-1">
                            <i class="pi pi-arrow-up text-[10px]"></i>
                            <span>Cash Collected: {{ formatMoney(stats.todayCollected) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200/90 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Retailer Dues</span>
                        <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <i class="pi pi-users text-base"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-2xl font-black text-rose-600">{{ formatMoney(stats.totalRetailerDue) }}</div>
                        <div class="text-[11px] text-slate-500 font-medium mt-1">
                            Across ~225 Shops (Roads A, B, C)
                        </div>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200/90 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Supplier Payables</span>
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <i class="pi pi-building text-base"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-2xl font-black text-slate-900">{{ formatMoney(stats.totalSupplierDue) }}</div>
                        <div class="text-[11px] text-slate-500 font-medium mt-1">
                            Tier 1 Bakery Manufacturers
                        </div>
                    </div>
                </div>

                <!-- Card 4 -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200/90 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Vehicles Dispatched</span>
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i class="pi pi-truck text-base"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-2xl font-black text-slate-900">{{ stats.activeTripsCount }} Trips</div>
                        <div class="text-[11px] text-blue-600 font-medium mt-1 flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                            <span>Roads A, B & C In Delivery</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Road Fleet Status Overview -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Road Delivery Fleets (1 Vehicle per Road)</h3>
                        <p class="text-xs text-slate-500">Live operational status of delivery runs across dedicated route segments</p>
                    </div>
                    <Link href="/trips" class="text-xs font-semibold text-amber-600 hover:text-amber-700 flex items-center gap-1">
                        <span>View All Trips</span>
                        <i class="pi pi-arrow-right text-[10px]"></i>
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                        v-for="road in roads"
                        :key="road.id"
                        class="p-5 rounded-2xl bg-white border border-slate-200/90 shadow-sm hover:border-amber-400/60 transition group flex flex-col justify-between"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2.5 py-0.5 rounded-lg bg-slate-900 text-amber-400 font-black text-xs">
                                    {{ road.code }}
                                </span>
                                <Tag
                                    :value="road.status"
                                    :severity="road.status === 'DISPATCHED' ? 'warn' : 'info'"
                                    class="text-[10px] font-bold"
                                />
                            </div>
                            <h4 class="font-bold text-slate-900 text-sm group-hover:text-amber-600 transition">
                                {{ road.name }}
                            </h4>
                            <div class="mt-3 space-y-1 text-xs text-slate-600">
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-truck text-slate-400 text-xs"></i>
                                    <span>{{ road.vehicle }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-user text-slate-400 text-xs"></i>
                                    <span>{{ road.driver }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-shop text-slate-400 text-xs"></i>
                                    <span>{{ road.shops }} Registered Retailer Shops</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-slate-400 block uppercase">Stock Loaded</span>
                                <span class="text-xs font-bold text-slate-900">{{ formatMoney(road.loadedValue) }}</span>
                            </div>
                            <Link
                                :href="`/trips?road_id=${road.id}`"
                                class="p-2 rounded-xl bg-slate-100 hover:bg-amber-100 text-slate-700 hover:text-amber-800 transition"
                                title="View Trip Details"
                            >
                                <i class="pi pi-angle-right text-xs"></i>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
