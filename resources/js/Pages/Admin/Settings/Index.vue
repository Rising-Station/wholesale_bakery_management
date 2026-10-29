<script setup>
import { onMounted, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const activeTab = ref('general'); // 'general' | 'cms'

const updateTabFromUrl = () => {
    const url = page.url || window.location.href;
    if (url.includes('tab=cms')) {
        activeTab.value = 'cms';
    } else {
        activeTab.value = 'general';
    }
};

onMounted(() => {
    updateTabFromUrl();
});

watch(
    () => page.url,
    () => {
        updateTabFromUrl();
    }
);

const switchTab = (tab) => {
    activeTab.value = tab;
    router.visit(`/settings?tab=${tab}`, { preserveState: true, preserveScroll: true, replace: true });
};

const form = useForm({
    bakery_name: props.settings.bakery_name || 'Mominul Bekari Wholesale Distribution',
    bakery_phone: props.settings.bakery_phone || '+880 1711-000004',
    bakery_email: props.settings.bakery_email || 'sales@mominulbekari.com',
    bakery_address: props.settings.bakery_address || 'Central Distribution Hub, Commercial Road Zone, Dhaka',
    currency_symbol: props.settings.currency_symbol || '৳',
    receipt_header: props.settings.receipt_header || 'Mominul Bekari - Quality Wholesale Bakery Goods',
    receipt_footer: props.settings.receipt_footer || 'Thank you for your business! Freshly baked daily. For orders: 01711-000004',
    low_stock_threshold_default: Number(props.settings.low_stock_threshold_default) || 20,
    sms_alerts_enabled: props.settings.sms_alerts_enabled === '1' ? '1' : '0',
    enable_credit_limit_warnings: props.settings.enable_credit_limit_warnings === '1' ? '1' : '0',
    enable_demo_login: props.settings.enable_demo_login === '0' ? '0' : '1',
    // Public Website CMS
    hero_badge: props.settings.hero_badge || 'Delivering Fresh Bakery Goods Daily Across Distribution Corridors',
    hero_title: props.settings.hero_title || 'Reliable Wholesale Bakery Distribution For Modern Retailers',
    hero_subtitle: props.settings.hero_subtitle || 'Connecting manufacturing producers directly with road-side grocery and tea stall retailers through dedicated vehicle fleets, transparent dual-ledger dues, and instant mobile billing.',
    tier1_title: props.settings.tier1_title || 'Suppliers & Procurement',
    tier1_description: props.settings.tier1_description || 'Producers supply bulk freshly baked biscuits, bread, buns, and cakes directly into central warehouse inventory with batch tracking and supplier due management.',
    tier2_title: props.settings.tier2_title || 'Bakery Hub & Vehicle Fleet',
    tier2_description: props.settings.tier2_description || 'Daily loading sheets transfer warehouse stock to route vehicles. Gate passes are generated and vehicles dispatched with mobile stock invariants.',
    tier3_title: props.settings.tier3_title || 'Road Retailer Shops',
    tier3_description: props.settings.tier3_description || 'Drivers issue instant sales memos with transparent live balance calculation, collect cash/MFS, deduct damage returns into quarantine, and exchange slow goods for fresh stock.',
    partnership_title: props.settings.partnership_title || 'Join Our Retail Distribution Route',
    partnership_subtitle: props.settings.partnership_subtitle || 'Run a grocery store, tea shop, or bakery retail counter? Sign up to receive daily fresh stock delivery.',
});

const toggleOptions = [
    { label: 'Enabled (Active)', value: '1' },
    { label: 'Disabled (Inactive)', value: '0' },
];

const submitSettings = () => {
    form.put(route('settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="activeTab === 'cms' ? 'Homepage CMS Settings' : 'General System Settings'" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header & Submenu Tabs -->
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                            {{ activeTab === 'cms' ? 'Homepage Public CMS Customizer' : 'General System Settings & Identity' }}
                        </h1>
                        <p class="text-sm text-slate-500">
                            {{ activeTab === 'cms'
                                ? 'Customize frontend public landing page headlines, marketing copy, and distribution architecture text.'
                                : 'Configure bakery business identity, thermal memo print formats, inventory alert thresholds, and operational rules.'
                            }}
                        </p>
                    </div>
                </div>

                <!-- Tab Navigation Buttons -->
                <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
                    <button
                        type="button"
                        @click="switchTab('general')"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer',
                            activeTab === 'general'
                                ? 'bg-slate-900 text-white shadow-md'
                                : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200',
                        ]"
                    >
                        <i class="pi pi-sliders-h text-xs"></i>
                        <span>General Setting</span>
                    </button>
                    <button
                        type="button"
                        @click="switchTab('cms')"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer',
                            activeTab === 'cms'
                                ? 'bg-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20'
                                : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200',
                        ]"
                    >
                        <i class="pi pi-globe text-xs"></i>
                        <span>Homepage CMS</span>
                    </button>
                </div>
            </div>

            <form @submit.prevent="submitSettings" class="space-y-6">
                <!-- ============================================== -->
                <!-- TAB 1: GENERAL SYSTEM SETTINGS                 -->
                <!-- ============================================== -->
                <div v-show="activeTab === 'general'" class="space-y-6">
                    <!-- Section 1: Business Identity -->
                    <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-sm space-y-4">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2 border-b pb-2">
                            <i class="pi pi-building text-indigo-600"></i>
                            Bakery Enterprise Identity
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Bakery Business Name *</label>
                                <InputText v-model="form.bakery_name" class="w-full text-sm" required />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Official Contact Phone *</label>
                                <InputText v-model="form.bakery_phone" class="w-full text-sm" required />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Support Email</label>
                                <InputText v-model="form.bakery_email" type="email" class="w-full text-sm" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Currency Symbol *</label>
                                <InputText v-model="form.currency_symbol" class="w-full text-sm font-bold font-mono" required />
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 mb-1">Depot & Central Warehouse Address *</label>
                                <InputText v-model="form.bakery_address" class="w-full text-sm" required />
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Thermal 80mm Memo & Invoice Slip Formats -->
                    <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-sm space-y-4">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2 border-b pb-2">
                            <i class="pi pi-print text-amber-600"></i>
                            Thermal POS Bill & Slip Customization
                        </h2>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Receipt Header Greeting</label>
                            <InputText v-model="form.receipt_header" class="w-full text-sm" />
                            <span class="text-[11px] text-slate-400">Printed below the business name on thermal 80mm receipts.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Receipt Footer Note / Promotional Message</label>
                            <Textarea v-model="form.receipt_footer" rows="2" class="w-full text-sm" />
                            <span class="text-[11px] text-slate-400">Printed at the bottom of customer sales memos and payment vouchers.</span>
                        </div>
                    </div>

                    <!-- Section 3: Inventory & Credit Governance -->
                    <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-sm space-y-4">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2 border-b pb-2">
                            <i class="pi pi-sliders-h text-emerald-600"></i>
                            Stock Alerts & Operational Governance
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Default Low Stock Alert Threshold</label>
                                <InputNumber v-model="form.low_stock_threshold_default" :min="1" class="w-full font-mono font-bold" />
                                <span class="text-[11px] text-slate-400">Minimum warehouse quantity before trigger alert.</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">SMS Notifications</label>
                                <Select
                                    v-model="form.sms_alerts_enabled"
                                    :options="toggleOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    filter
                                    class="w-full text-sm"
                                />
                                <span class="text-[11px] text-slate-400">Send dispatch & payment SMS to shop owners.</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Retailer Credit Limit Warnings</label>
                                <Select
                                    v-model="form.enable_credit_limit_warnings"
                                    :options="toggleOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    filter
                                    class="w-full text-sm"
                                />
                                <span class="text-[11px] text-slate-400">Warn salesperson if retailer exceeds credit limit.</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Quick Demo Login Shortcuts</label>
                                <Select
                                    v-model="form.enable_demo_login"
                                    :options="toggleOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    filter
                                    class="w-full text-sm"
                                />
                                <span class="text-[11px] text-slate-400">Show or hide 1-click staff demo credentials on the login screen.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================== -->
                <!-- TAB 2: HOMEPAGE CMS SETTINGS                   -->
                <!-- ============================================== -->
                <div v-show="activeTab === 'cms'" class="space-y-6">
                    <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-sm space-y-4">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2 border-b pb-2">
                            <i class="pi pi-globe text-amber-500"></i>
                            Frontend Public Website (CMS & Homepage)
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Hero Pill / Badge Tagline</label>
                                <InputText v-model="form.hero_badge" class="w-full text-sm" placeholder="e.g. Delivering Fresh Bakery Goods Daily Across Corridors" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Hero Main Headline</label>
                                <InputText v-model="form.hero_title" class="w-full text-sm" placeholder="e.g. Reliable Wholesale Bakery Distribution" />
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 mb-1">Hero Sub-Headline / Description</label>
                                <Textarea v-model="form.hero_subtitle" rows="2" class="w-full text-sm" />
                            </div>

                            <!-- 3-Tier Distribution Architecture -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tier 1 Title (Suppliers)</label>
                                <InputText v-model="form.tier1_title" class="w-full text-sm" />
                                <label class="block text-xs font-semibold text-slate-500 mt-2 mb-1">Tier 1 Description</label>
                                <Textarea v-model="form.tier1_description" rows="2" class="w-full text-xs" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tier 2 Title (Hub & Fleet)</label>
                                <InputText v-model="form.tier2_title" class="w-full text-sm" />
                                <label class="block text-xs font-semibold text-slate-500 mt-2 mb-1">Tier 2 Description</label>
                                <Textarea v-model="form.tier2_description" rows="2" class="w-full text-xs" />
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tier 3 Title (Retailers)</label>
                                <InputText v-model="form.tier3_title" class="w-full text-sm" />
                                <label class="block text-xs font-semibold text-slate-500 mt-2 mb-1">Tier 3 Description</label>
                                <Textarea v-model="form.tier3_description" rows="2" class="w-full text-xs" />
                            </div>

                            <!-- Retailer Partnership Section -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Partnership Form Heading</label>
                                <InputText v-model="form.partnership_title" class="w-full text-sm" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Partnership Form Subtitle</label>
                                <InputText v-model="form.partnership_subtitle" class="w-full text-sm" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-2">
                    <Button
                        type="submit"
                        icon="pi pi-check"
                        label="Save System Settings"
                        severity="primary"
                        :loading="form.processing"
                    />
                </div>
            </form>
        </div>
    </AppLayout>
</template>
