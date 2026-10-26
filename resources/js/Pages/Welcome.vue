<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    roads: {
        type: Array,
        default: () => [],
    },
    canLogin: {
        type: Boolean,
        default: true,
    },
});

const inquirySubmitted = ref(false);
const inquiryForm = ref({
    shop_name: '',
    owner_name: '',
    phone: '',
    road: '',
    message: '',
});

const submitInquiry = () => {
    inquirySubmitted.value = true;
    setTimeout(() => {
        inquiryForm.value = {
            shop_name: '',
            owner_name: '',
            phone: '',
            road: '',
            message: '',
        };
    }, 500);
};
</script>

<template>
    <Head title="Mominul Bekari - Wholesale Distribution Network" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans selection:bg-amber-500 selection:text-slate-950">
        <!-- Top Navigation -->
        <header class="sticky top-0 z-50 backdrop-blur-md bg-slate-950/80 border-b border-slate-800/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-amber-600 via-amber-500 to-yellow-400 flex items-center justify-center shadow-lg shadow-amber-500/20 text-slate-950 font-black text-xl">
                        M
                    </div>
                    <div>
                        <div class="text-lg font-bold tracking-tight text-white flex items-center gap-2">
                            Mominul Bekari
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                Wholesale
                            </span>
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
                    <Link v-else :href="route('login')">
                        <Button
                            label="Staff Login"
                            icon="pi pi-sign-in"
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
                    Delivering Fresh Bakery Goods Daily Across Roads A, B & C
                </div>

                <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-white max-w-4xl mx-auto leading-tight">
                    Reliable Wholesale Bakery Distribution For
                    <span class="bg-gradient-to-r from-amber-400 via-amber-300 to-yellow-500 bg-clip-text text-transparent">
                        Modern Retailers
                    </span>
                </h1>

                <p class="mt-6 text-lg sm:text-xl text-slate-400 max-w-2xl mx-auto leading-relaxed">
                    Mominul Bekari connects manufacturing producers directly with road-side grocery and tea stall retailers through dedicated vehicle fleets, transparent dual-ledger dues, and instant mobile billing.
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
                        <Button
                            label="Staff & Driver Login"
                            icon="pi pi-lock"
                            size="large"
                            severity="secondary"
                            outlined
                            class="border-slate-700 text-slate-200 hover:bg-slate-900 px-6"
                        />
                    </Link>
                </div>

                <!-- Stats Highlights -->
                <div class="mt-20 grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
                    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 backdrop-blur-sm">
                        <div class="text-3xl font-extrabold text-amber-400 font-mono">100+</div>
                        <div class="text-xs text-slate-400 uppercase tracking-wider mt-1 font-semibold">Active Retail Shops</div>
                    </div>
                    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 backdrop-blur-sm">
                        <div class="text-3xl font-extrabold text-amber-400 font-mono">3 Routes</div>
                        <div class="text-xs text-slate-400 uppercase tracking-wider mt-1 font-semibold">Road A, B & C Fleet</div>
                    </div>
                    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 backdrop-blur-sm">
                        <div class="text-3xl font-extrabold text-amber-400 font-mono">100%</div>
                        <div class="text-xs text-slate-400 uppercase tracking-wider mt-1 font-semibold">Stock Reconciled Daily</div>
                    </div>
                    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 backdrop-blur-sm">
                        <div class="text-3xl font-extrabold text-amber-400 font-mono">0 Defect</div>
                        <div class="text-xs text-slate-400 uppercase tracking-wider mt-1 font-semibold">Damage Return Guarantee</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3-Tier Distribution Architecture Section -->
        <section id="network" class="py-20 bg-slate-900/40 border-y border-slate-800/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-amber-400 mb-2">Engine Architecture</h2>
                    <h3 class="text-3xl font-bold text-white tracking-tight">How Mominul Bekari Operates</h3>
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
                        <h4 class="text-xl font-bold text-white mb-3">Suppliers & Procurement</h4>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            Producers supply bulk freshly baked biscuits, bread, buns, and cakes directly into Mominul Bekari central warehouse inventory with batch tracking and supplier due management.
                        </p>
                    </div>

                    <!-- Tier 2 -->
                    <div class="p-8 rounded-2xl bg-slate-900 border border-amber-500/40 relative group shadow-lg shadow-amber-500/5">
                        <div class="w-12 h-12 rounded-xl bg-amber-500 text-slate-950 flex items-center justify-center font-bold text-lg mb-6 shadow-md shadow-amber-500/30">
                            2
                        </div>
                        <div class="text-xs font-mono uppercase tracking-wider text-amber-400 font-semibold mb-1">Tier 2: The Core Distribution Hub</div>
                        <h4 class="text-xl font-bold text-white mb-3">Mominul Bekari Hub & Vehicle Fleet</h4>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            Daily loading sheets transfer warehouse stock to route vehicles. Gate passes are generated and vehicles dispatched to Road A, Road B, and Road C with mobile stock invariants.
                        </p>
                    </div>

                    <!-- Tier 3 -->
                    <div class="p-8 rounded-2xl bg-slate-900 border border-slate-800 relative group hover:border-amber-500/50 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center font-bold text-lg mb-6">
                            3
                        </div>
                        <div class="text-xs font-mono uppercase tracking-wider text-amber-400 font-semibold mb-1">Tier 3: Downstream Delivery</div>
                        <h4 class="text-xl font-bold text-white mb-3">Road Retailer Shops</h4>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            Drivers issue instant sales memos with transparent live balance calculation, collect cash/MFS, deduct damage returns into quarantine, and exchange slow goods for fresh stock.
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
                            <span class="text-[11px] text-slate-400 bg-slate-800 px-2 py-0.5 rounded">{{ product.packaging_type }}</span>
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
                        Our dedicated distribution vehicles operate scheduled morning delivery rounds along three designated commercial roads.
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
                        <h3 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Join Our Retail Distribution Route</h3>
                        <p class="text-xs sm:text-sm text-slate-400 mt-2">
                            Run a grocery store, tea shop, or bakery retail counter on Road A, B, or C? Sign up to receive daily fresh stock delivery.
                        </p>
                    </div>

                    <div v-if="inquirySubmitted" class="p-6 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-center space-y-2">
                        <i class="pi pi-check-circle text-3xl text-emerald-400"></i>
                        <h4 class="text-base font-bold text-white">Inquiry Received Successfully!</h4>
                        <p class="text-xs text-slate-300">
                            Our route delivery supervisor will visit your shop on the next morning round to confirm pricing and opening stock.
                        </p>
                    </div>

                    <form v-else @submit.prevent="submitInquiry" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Shop / Business Name *</label>
                                <InputText v-model="inquiryForm.shop_name" placeholder="e.g. Bismillah General Store" class="w-full text-sm bg-slate-950 border-slate-700 text-white" required />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Owner / Manager Name *</label>
                                <InputText v-model="inquiryForm.owner_name" placeholder="Your Full Name" class="w-full text-sm bg-slate-950 border-slate-700 text-white" required />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Phone Number *</label>
                                <InputText v-model="inquiryForm.phone" placeholder="017xxxxxxxx" class="w-full text-sm bg-slate-950 border-slate-700 text-white" required />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Select Delivery Road</label>
                                <InputText v-model="inquiryForm.road" placeholder="Road A / Road B / Road C" class="w-full text-sm bg-slate-950 border-slate-700 text-white" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Message or Specific Product Requirements</label>
                            <Textarea v-model="inquiryForm.message" rows="2" placeholder="Tell us about daily quantities or product lines you need..." class="w-full text-sm bg-slate-950 border-slate-700 text-white" />
                        </div>

                        <div class="pt-2">
                            <Button
                                type="submit"
                                label="Submit Dealership Inquiry"
                                icon="pi pi-send"
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
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-300">Mominul Bekari Wholesale System</span>
                    <span>•</span>
                    <span>Central Commercial Zone, Dhaka</span>
                    <span>•</span>
                    <span>Hotline: +880 1711-000004</span>
                </div>
                <div>
                    © {{ new Date().getFullYear() }} Mominul Bekari. All rights reserved.
                </div>
            </div>
        </footer>
    </div>
</template>
