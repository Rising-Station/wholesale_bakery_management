<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    roads: {
        type: Array,
        default: () => [],
    },
    cms: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    canLogin: {
        type: Boolean,
        default: true,
    },
});

const page = usePage();
const bakeryName = computed(() => page.props.settings?.bakery_name || 'Mominul Bekari');
const bakeryPhone = computed(() => page.props.settings?.bakery_phone || '+880 1711-000004');
const bakeryEmail = computed(() => page.props.settings?.bakery_email || 'sales@mominulbekari.com');
const bakeryAddress = computed(() => page.props.settings?.bakery_address || 'Central Distribution Hub, Commercial Road Zone, Dhaka');

const roadsList = computed(() => {
    if (!props.roads || props.roads.length === 0) return 'Road A, B & C';
    return props.roads.map((r) => r.name).join(', ');
});

const inquirySubmitted = ref(false);
const inquiryForm = useForm({
    shop_name: '',
    owner_name: '',
    phone: '',
    road_id: null,
    message: '',
});

const submitInquiry = () => {
    inquiryForm.post(route('retailer-inquiries.store'), {
        preserveScroll: true,
        onSuccess: () => {
            inquirySubmitted.value = true;
            inquiryForm.reset();
        },
    });
};
</script>

<template>
    <Head :title="`${bakeryName} - Wholesale Distribution Network`" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans selection:bg-amber-500 selection:text-slate-950">
        <!-- Top Navigation -->
        <header class="sticky top-0 z-50 backdrop-blur-md bg-slate-950/80 border-b border-slate-800/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-amber-600 via-amber-500 to-yellow-400 flex items-center justify-center shadow-lg shadow-amber-500/20 text-slate-950 font-black text-xl">
                        {{ bakeryName.charAt(0) }}
                    </div>
                    <div>
                        <div class="text-lg font-bold tracking-tight text-white flex items-center gap-2">
                            {{ bakeryName }}
                        </div>
                        <div class="text-xs text-slate-400">Distribution & Supply Chain Hub</div>
                    </div>
                </div>

                <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
                    <a href="#network" class="hover:text-amber-400 transition-colors">3-Tier Supply Chain</a>
                    <a href="#products" class="hover:text-amber-400 transition-colors">Wholesale Products</a>
                    <a href="#routes" class="hover:text-amber-400 transition-colors">Route Coverage</a>
                    <a href="#dealership" class="hover:text-amber-400 transition-colors">Retailer Partnership</a>
                </nav>

                <div class="flex items-center gap-3">
                    <Link v-if="$page.props.auth?.user" :href="route('dashboard')">
                        <Button
                            label="Go to Dashboard"
                            icon="pi pi-th-large"
                            class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-bold border-none shadow-md shadow-amber-500/20"
                        />
                    </Link>
                  
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative pt-20 pb-24 overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(245,158,11,0.15),rgba(255,255,255,0))]"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-semibold uppercase tracking-wider mb-8">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    {{ cms?.hero_badge || `Delivering Fresh Bakery Goods Daily Across ${roadsList}` }}
                </div>

                <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-white max-w-4xl mx-auto leading-tight">
                    <span class="bg-gradient-to-r from-white via-slate-100 to-slate-300 bg-clip-text text-transparent">
                        {{ cms?.hero_title || 'Reliable Wholesale Bakery Distribution For Modern Retailers' }}
                    </span>
                </h1>

                <p class="mt-6 text-lg sm:text-xl text-slate-400 max-w-2xl mx-auto leading-relaxed">
                    {{ cms?.hero_subtitle || `${bakeryName} connects manufacturing producers directly with road-side grocery and tea stall retailers through dedicated vehicle fleets, transparent dual-ledger dues, and instant mobile billing.` }}
                </p>

                <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                    <a href="#dealership">
                        <Button
                            label="Become a Retail Partner"
                            icon="pi pi-arrow-right"
                            iconPos="right"
                            size="large"
                            class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold border-none shadow-lg shadow-amber-500/25 px-8"
                        />
                    </a>
                    <Link :href="route('dashboard')">
                       
                    </Link>
                </div>

                <!-- Stats Highlights -->
                <div class="mt-20 grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
                    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 backdrop-blur-sm">
                        <div class="text-3xl font-extrabold text-amber-400 font-mono">
                            {{ stats?.retailers_count ? `${stats.retailers_count}+` : '100+' }}
                        </div>
                        <div class="text-xs text-slate-400 uppercase tracking-wider mt-1 font-semibold">Active Retail Shops</div>
                    </div>
                    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 backdrop-blur-sm">
                        <div class="text-3xl font-extrabold text-amber-400 font-mono">
                            {{ roads.length ? `${roads.length} Routes` : '3 Routes' }}
                        </div>
                        <div class="text-xs text-slate-400 uppercase tracking-wider mt-1 font-semibold">Dedicated Delivery Corridors</div>
                    </div>
                    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 backdrop-blur-sm">
                        <div class="text-3xl font-extrabold text-amber-400 font-mono">
                            {{ stats?.products_count ? `${stats.products_count}+` : '20+' }}
                        </div>
                        <div class="text-xs text-slate-400 uppercase tracking-wider mt-1 font-semibold">Fresh Bakery Lines</div>
                    </div>
                    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 backdrop-blur-sm">
                        <div class="text-3xl font-extrabold text-amber-400 font-mono">100%</div>
                        <div class="text-xs text-slate-400 uppercase tracking-wider mt-1 font-semibold">Stock Reconciled Daily</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3-Tier Distribution Architecture Section -->
        <section id="network" class="py-20 bg-slate-900/40 border-y border-slate-800/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-amber-400 mb-2">Engine Architecture</h2>
                    <h3 class="text-3xl font-bold text-white tracking-tight">How {{ bakeryName }} Operates</h3>
                    <p class="text-sm text-slate-400 mt-3">
                        Our integrated 3-Tier model ensures full accountability from bulk supplier manufacturing to road delivery vehicles and retailer credit.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Tier 1 -->
                    <div class="p-8 rounded-2xl bg-slate-900 border border-slate-800 relative group hover:border-amber-500/50 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center font-bold text-lg mb-6">
                            1
                        </div>
                        <div class="text-xs font-mono uppercase tracking-wider text-amber-400 font-semibold mb-1">Tier 1: Upstream Inflow</div>
                        <h4 class="text-xl font-bold text-white mb-3">{{ cms?.tier1_title || 'Suppliers & Procurement' }}</h4>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            {{ cms?.tier1_description || `Producers supply bulk freshly baked biscuits, bread, buns, and cakes directly into ${bakeryName} central warehouse inventory with batch tracking and supplier due management.` }}
                        </p>
                    </div>

                    <!-- Tier 2 -->
                    <div class="p-8 rounded-2xl bg-slate-900 border border-amber-500/40 relative group shadow-lg shadow-amber-500/5">
                        <div class="w-12 h-12 rounded-xl bg-amber-500 text-slate-950 flex items-center justify-center font-bold text-lg mb-6 shadow-md shadow-amber-500/30">
                            2
                        </div>
                        <div class="text-xs font-mono uppercase tracking-wider text-amber-400 font-semibold mb-1">Tier 2: The Core Distribution Hub</div>
                        <h4 class="text-xl font-bold text-white mb-3">{{ cms?.tier2_title || `${bakeryName} Hub & Vehicle Fleet` }}</h4>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            {{ cms?.tier2_description || `Daily loading sheets transfer warehouse stock to route vehicles. Gate passes are generated and vehicles dispatched to ${roadsList} with mobile stock invariants.` }}
                        </p>
                    </div>

                    <!-- Tier 3 -->
                    <div class="p-8 rounded-2xl bg-slate-900 border border-slate-800 relative group hover:border-amber-500/50 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center font-bold text-lg mb-6">
                            3
                        </div>
                        <div class="text-xs font-mono uppercase tracking-wider text-amber-400 font-semibold mb-1">Tier 3: Downstream Delivery</div>
                        <h4 class="text-xl font-bold text-white mb-3">{{ cms?.tier3_title || 'Road Retailer Shops' }}</h4>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            {{ cms?.tier3_description || 'Drivers issue instant sales memos with transparent live balance calculation, collect cash/MFS, deduct damage returns into quarantine, and exchange slow goods for fresh stock.' }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Product Catalog Section -->
        <section id="products" class="py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-12">
                    <div>
                        <h2 class="text-xs font-bold uppercase tracking-widest text-amber-400 mb-2">Product Lineup</h2>
                        <h3 class="text-3xl font-bold text-white tracking-tight">Fresh Wholesale Offerings</h3>
                        <p class="text-sm text-slate-400 mt-2">
                            High-demand, fast-moving bakery snacks distributed daily with competitive retailer profit margins.
                        </p>
                    </div>
                    <div>
                        <a href="#dealership">
                            <Button label="Request Wholesale Price List" icon="pi pi-file-excel" severity="secondary" outlined size="small" />
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div
                        v-for="product in products"
                        :key="product.id"
                        class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-amber-500/40 transition-all group"
                    >
                        <div class="h-32 rounded-xl bg-slate-800/80 border border-slate-700/50 flex items-center justify-center text-4xl mb-4 group-hover:scale-105 transition-transform">
                            🥖
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono text-amber-400 font-semibold">{{ product.code }}</span>
                            <span class="text-[11px] text-slate-400 bg-slate-800 px-2 py-0.5 rounded">
                                {{ product.category?.name || product.packaging_type }}
                            </span>
                        </div>
                        <h4 class="text-base font-bold text-white mt-1.5">{{ product.name }}</h4>
                        <div class="mt-3 flex items-center justify-between pt-3 border-t border-slate-800">
                            <span class="text-xs text-slate-400">Wholesale Rate</span>
                            <span class="font-bold text-amber-400 font-mono text-base">৳{{ Number(product.wholesale_price).toFixed(2) }}</span>
                        </div>
                    </div>

                    <div v-if="!products.length" class="col-span-4 p-8 text-center text-slate-500 bg-slate-900/40 rounded-xl border border-slate-800">
                        Products are freshly configured in the central catalog.
                    </div>
                </div>
            </div>
        </section>

        <!-- Route Coverage Section -->
        <section id="routes" class="py-20 bg-slate-900/30 border-y border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-amber-400 mb-2">Fleet Route Network</h2>
                    <h3 class="text-3xl font-bold text-white tracking-tight">Dedicated Daily Delivery Corridors</h3>
                    <p class="text-sm text-slate-400 mt-3">
                        Our dedicated distribution vehicles operate scheduled morning delivery rounds along designated commercial roads.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        v-for="road in roads"
                        :key="road.id"
                        class="p-6 rounded-2xl bg-slate-900 border border-slate-800"
                    >
                        <div class="flex items-center gap-2 mb-3">
                            <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                            <h4 class="text-lg font-bold text-white">{{ road.name }}</h4>
                        </div>
                        <div class="text-xs font-mono text-amber-400 bg-amber-500/10 px-2.5 py-1 rounded inline-block mb-3 border border-amber-500/20">
                            Route Code: {{ road.code }}
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Scheduled morning replenishment servicing enrolled grocery shops, school canteens, and local markets.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Partnership / Dealership Inquiry Section -->
        <section id="dealership" class="py-20">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-gradient-to-br from-slate-900 via-slate-900 to-slate-950 p-8 sm:p-12 rounded-3xl border border-slate-800 shadow-2xl relative">
                    <div class="text-center max-w-xl mx-auto mb-8">
                        <h2 class="text-xs font-bold uppercase tracking-widest text-amber-400 mb-2">Retailer Partnership</h2>
                        <h3 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                            {{ cms?.partnership_title || 'Join Our Retail Distribution Route' }}
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-400 mt-2">
                            {{ cms?.partnership_subtitle || `Run a grocery store, tea shop, or bakery retail counter on ${roadsList}? Sign up to receive daily fresh stock delivery.` }}
                        </p>
                    </div>

                    <div v-if="inquirySubmitted" class="p-6 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-center space-y-2">
                        <i class="pi pi-check-circle text-3xl text-emerald-400"></i>
                        <h4 class="text-base font-bold text-white">Inquiry Received Successfully!</h4>
                        <p class="text-xs text-slate-300">
                            Our route delivery supervisor will visit your shop on the next morning round to confirm pricing and opening stock.
                        </p>
                        <div class="pt-2">
                            <Button label="Submit Another Inquiry" severity="secondary" size="small" @click="inquirySubmitted = false" />
                        </div>
                    </div>

                    <form v-else @submit.prevent="submitInquiry" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">
                                    Shop / Business Name <span class="text-amber-400">*</span>
                                </label>
                                <InputText
                                    v-model="inquiryForm.shop_name"
                                    placeholder="e.g. Bismillah General Store"
                                    class="w-full text-sm !bg-slate-950 !border-slate-700 !text-white"
                                    :class="{ 'p-invalid': inquiryForm.errors.shop_name }"
                                    required
                                />
                                <span v-if="inquiryForm.errors.shop_name" class="text-xs text-rose-400 mt-1 block">
                                    {{ inquiryForm.errors.shop_name }}
                                </span>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">
                                    Owner / Manager Name <span class="text-amber-400">*</span>
                                </label>
                                <InputText
                                    v-model="inquiryForm.owner_name"
                                    placeholder="Your Full Name"
                                    class="w-full text-sm !bg-slate-950 !border-slate-700 !text-white"
                                    :class="{ 'p-invalid': inquiryForm.errors.owner_name }"
                                    required
                                />
                                <span v-if="inquiryForm.errors.owner_name" class="text-xs text-rose-400 mt-1 block">
                                    {{ inquiryForm.errors.owner_name }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">
                                    Phone Number <span class="text-amber-400">*</span>
                                </label>
                                <InputText
                                    v-model="inquiryForm.phone"
                                    placeholder="017xxxxxxxx"
                                    class="w-full text-sm !bg-slate-950 !border-slate-700 !text-white"
                                    :class="{ 'p-invalid': inquiryForm.errors.phone }"
                                    required
                                />
                                <span v-if="inquiryForm.errors.phone" class="text-xs text-rose-400 mt-1 block">
                                    {{ inquiryForm.errors.phone }}
                                </span>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">
                                    Select Delivery Road <span class="text-amber-400">*</span>
                                </label>
                                <Select
                                    v-model="inquiryForm.road_id"
                                    :options="roads"
                                    optionLabel="name"
                                    optionValue="id"
                                    placeholder="Select Delivery Road"
                                    filter
                                    class="w-full text-sm custom-dark-select"
                                    :class="{ 'p-invalid': inquiryForm.errors.road_id }"
                                    required
                                />
                                <span v-if="inquiryForm.errors.road_id" class="text-xs text-rose-400 mt-1 block">
                                    {{ inquiryForm.errors.road_id }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Message or Specific Product Requirements</label>
                            <Textarea
                                v-model="inquiryForm.message"
                                rows="2"
                                placeholder="Tell us about daily quantities or product lines you need..."
                                class="w-full text-sm !bg-slate-950 !border-slate-700 !text-white"
                            />
                        </div>

                        <div class="pt-2">
                            <Button
                                type="submit"
                                label="Submit Dealership Inquiry"
                                icon="pi pi-send"
                                :loading="inquiryForm.processing"
                                class="w-full bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold border-none py-3 shadow-lg shadow-amber-500/20"
                            />
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="mt-auto border-t border-slate-900 bg-slate-950 py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-bold text-slate-300">{{ bakeryName }}</span>
                    <span>•</span>
                    <span>{{ bakeryAddress }}</span>
                    <span>•</span>
                    <span>Hotline: {{ bakeryPhone }}</span>
                    <span v-if="bakeryEmail">•</span>
                    <span v-if="bakeryEmail">{{ bakeryEmail }}</span>
                </div>
                <div>
                    © {{ new Date().getFullYear() }} {{ bakeryName }}. All rights reserved.
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
:deep(.custom-dark-select) {
    background-color: #020617 !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}

:deep(.custom-dark-select .p-select-label) {
    color: #f8fafc !important;
}

:deep(.custom-dark-select .p-select-label.p-placeholder) {
    color: #94a3b8 !important;
}

:deep(.custom-dark-select .p-select-dropdown) {
    color: #94a3b8 !important;
}
</style>
