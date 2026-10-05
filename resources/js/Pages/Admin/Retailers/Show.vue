<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';

const props = defineProps({
    retailer: {
        type: Object,
        required: true,
    },
});

const formatMoney = (val) => {
    return '৳ ' + Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <Head :title="`${retailer.shop_name} - Retailer Profile`" />

    <AppLayout :title="`Shop: ${retailer.shop_name}`">
        <div class="space-y-6">
            <!-- Back button & Navigation -->
            <div class="flex items-center justify-between">
                <Link
                    href="/retailers"
                    class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-slate-900 transition"
                >
                    <i class="pi pi-arrow-left text-xs"></i>
                    <span>Back to Retailer Directory</span>
                </Link>

                <div class="flex items-center gap-2">
                    <Link
                        :href="`/memos/create?retailer_id=${retailer.id}&road_id=${retailer.road_id}`"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs shadow-sm transition"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        <span>New Memo for Shop</span>
                    </Link>
                </div>
            </div>

            <!-- Profile Summary Card -->
            <div class="bg-white rounded-3xl p-6 lg:p-8 border border-slate-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-amber-500 to-amber-300 flex items-center justify-center text-slate-950 font-black text-2xl shadow-md shrink-0">
                        {{ retailer.shop_name.charAt(0) }}
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-black text-slate-900">{{ retailer.shop_name }}</h2>
                            <Tag
                                :value="retailer.is_active ? 'Active' : 'Inactive'"
                                :severity="retailer.is_active ? 'success' : 'secondary'"
                                class="text-[10px] font-bold"
                            />
                        </div>
                        <p class="text-xs text-slate-500 flex items-center gap-2">
                            <i class="pi pi-user text-xs"></i>
                            <span>Proprietor: <strong>{{ retailer.owner_name }}</strong></span>
                            <span>•</span>
                            <i class="pi pi-phone text-xs"></i>
                            <span>{{ retailer.phone }}</span>
                            <span v-if="retailer.alt_phone">/ {{ retailer.alt_phone }}</span>
                        </p>
                        <p class="text-xs text-slate-500 flex items-center gap-1.5">
                            <i class="pi pi-map-marker text-xs"></i>
                            <span>{{ retailer.address || 'No physical address recorded' }}</span>
                        </p>
                    </div>
                </div>

                <!-- Road Badge -->
                <div class="flex flex-col items-start md:items-end">
                    <span class="text-[10px] uppercase font-bold text-slate-400">Assigned Route</span>
                    <span class="mt-1 px-3 py-1 rounded-xl bg-slate-900 text-amber-400 font-bold text-xs flex items-center gap-1.5">
                        <i class="pi pi-map"></i>
                        <span>{{ retailer.road?.code }} - {{ retailer.road?.name }}</span>
                    </span>
                </div>
            </div>

            <!-- Financial Balance Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm flex flex-col justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Current Due Outstanding</span>
                    <div class="mt-3">
                        <div
                            :class="[
                                'text-2xl font-black',
                                Number(retailer.current_due) > 0 ? 'text-rose-600' : 'text-emerald-600',
                            ]"
                        >
                            {{ formatMoney(retailer.current_due) }}
                        </div>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Net balance to collect from shop</span>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm flex flex-col justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Approved Credit Limit</span>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-slate-800">{{ formatMoney(retailer.credit_limit) }}</div>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Maximum permissible ledger due</span>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm flex flex-col justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Opening Due Balance</span>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-slate-800">{{ formatMoney(retailer.opening_due) }}</div>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Recorded at registration</span>
                    </div>
                </div>
            </div>

            <!-- Ledger History Tabs -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <Tabs value="0">
                    <TabList>
                        <Tab value="0">
                            <i class="pi pi-receipt mr-2 text-xs"></i>
                            <span>Sales Memos</span>
                        </Tab>
                        <Tab value="1">
                            <i class="pi pi-wallet mr-2 text-xs"></i>
                            <span>Collections / Payments</span>
                        </Tab>
                        <Tab value="2">
                            <i class="pi pi-exclamation-triangle mr-2 text-xs"></i>
                            <span>Damage Credits</span>
                        </Tab>
                    </TabList>
                    <TabPanels class="pt-4">
                        <TabPanel value="0">
                            <div class="py-12 text-center text-slate-400">
                                <i class="pi pi-file-edit text-3xl mb-2 block"></i>
                                <p class="text-sm font-medium">No sales memos recorded yet for this shop.</p>
                                <p class="text-xs text-slate-400 mt-1">Generated road delivery memos will appear here.</p>
                            </div>
                        </TabPanel>
                        <TabPanel value="1">
                            <div class="py-12 text-center text-slate-400">
                                <i class="pi pi-wallet text-3xl mb-2 block"></i>
                                <p class="text-sm font-medium">No direct payment collections recorded yet.</p>
                            </div>
                        </TabPanel>
                        <TabPanel value="2">
                            <div class="py-12 text-center text-slate-400">
                                <i class="pi pi-box text-3xl mb-2 block"></i>
                                <p class="text-sm font-medium">No damage or expiry claims recorded.</p>
                            </div>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </div>
    </AppLayout>
</template>
