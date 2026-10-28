<script setup>
import { Head, useForm } from '@inertiajs/vue3';
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
});

const toggleOptions = [
    { label: 'Enabled (Active)', value: '1' },
    { label: 'Disabled (Inactive)', value: '0' },
];

const submitSettings = () => {
    form.put(route('settings.update'));
};
</script>

<template>
    <Head title="System & Company Settings" />

    <AppLayout>
        <div class="space-y-6 max-w-4xl">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    General Settings & Company Profile
                </h1>
                <p class="text-sm text-slate-500">
                    Configure bakery business identity, thermal memo print formats, inventory alert thresholds, and operational rules.
                </p>
            </div>

            <form @submit.prevent="submitSettings" class="space-y-6">
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
                                class="w-full text-sm"
                            />
                            <span class="text-[11px] text-slate-400">Show or hide 1-click staff demo credentials on the login screen.</span>
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
