<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({
    memo: {
        type: Object,
        required: true,
    },
});

const printFormat = ref('A4'); // 'A4' or 'THERMAL'

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('format') === 'thermal') {
        printFormat.value = 'THERMAL';
    }
    if (params.get('auto_print') === '1') {
        setTimeout(() => {
            window.print();
        }, 300);
    }
});

const printInvoice = () => {
    window.print();
};
</script>

<template>
    <AppLayout>
        <Head :title="`Sales Memo: ${memo.memo_no}`" />

        <div class="space-y-6 max-w-4xl mx-auto">
            <!-- Non-printable Toolbar -->
            <div class="print:hidden flex flex-wrap items-center justify-between gap-4 bg-white p-4 rounded-xl shadow-sm border border-slate-200">
                <Link href="/memos" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-slate-800">
                    <i class="pi pi-arrow-left"></i> Back to Sales Memos
                </Link>

                <div class="flex items-center gap-3">
                    <!-- Format Switcher -->
                    <div class="inline-flex rounded-lg border border-slate-200 p-1 bg-slate-50 text-xs font-semibold">
                        <button
                            type="button"
                            :class="[
                                'px-3 py-1.5 rounded-md transition',
                                printFormat === 'A4' ? 'bg-white shadow text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-800'
                            ]"
                            @click="printFormat = 'A4'"
                        >
                            Standard A4 Invoice
                        </button>
                        <button
                            type="button"
                            :class="[
                                'px-3 py-1.5 rounded-md transition',
                                printFormat === 'THERMAL' ? 'bg-white shadow text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-800'
                            ]"
                            @click="printFormat = 'THERMAL'"
                        >
                            Thermal 80mm Slip
                        </button>
                    </div>

                    <Button
                        label="Print Sales Memo"
                        icon="pi pi-print"
                        severity="primary"
                        @click="printInvoice"
                    />
                </div>
            </div>

            <!-- ========================================== -->
            <!-- STANDARD A4 FORMAT LAYOUT                  -->
            <!-- ========================================== -->
            <div
                v-if="printFormat === 'A4'"
                class="bg-white p-8 md:p-12 rounded-2xl shadow-sm border border-slate-200 print:border-none print:shadow-none print:p-0 text-slate-900"
            >
                <!-- Company Header -->
                <div class="border-b-2 border-slate-900 pb-6 flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-black uppercase tracking-wider text-slate-900">
                            Mominul Bekari
                        </h1>
                        <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold mt-0.5">
                            Wholesale Bakery & Distribution Hub
                        </p>
                        <div class="text-xs text-slate-500 mt-2">
                            Central Depot: Main Road, Wholesale Market &bull; Phone: 01711-000001
                        </div>
                    </div>

                    <div class="text-right">
                        <div class="inline-block px-3 py-1 rounded bg-slate-900 text-white font-mono text-xs font-bold uppercase tracking-wider mb-2">
                            Delivery Sales Memo
                        </div>
                        <div class="font-mono text-base font-bold text-slate-900">{{ memo.memo_no }}</div>
                        <div class="text-xs text-slate-500">Date: {{ memo.memo_date }}</div>
                    </div>
                </div>

                <!-- Billed Retailer Info -->
                <div class="grid grid-cols-2 gap-6 py-6 border-b border-slate-200 text-xs">
                    <div>
                        <span class="text-slate-400 font-semibold uppercase text-[10px] block mb-1">Delivered To (Retailer)</span>
                        <div class="text-base font-bold text-slate-900">{{ memo.retailer?.shop_name }}</div>
                        <div class="text-slate-600 font-medium mt-0.5">Proprietor: {{ memo.retailer?.owner_name }}</div>
                        <div class="text-slate-500 font-mono mt-0.5">Phone: {{ memo.retailer?.phone }}</div>
                        <div v-if="memo.retailer?.address" class="text-slate-400 mt-0.5">{{ memo.retailer?.address }}</div>
                    </div>

                    <div class="text-right">
                        <span class="text-slate-400 font-semibold uppercase text-[10px] block mb-1">Route & Delivery Info</span>
                        <div class="font-bold text-slate-800">{{ memo.road?.name }} ({{ memo.road?.code }})</div>
                        <div v-if="memo.vehicle_trip" class="text-slate-600 font-mono mt-0.5">
                            Vehicle: {{ memo.vehicle_trip?.vehicle_number }}
                        </div>
                        <div class="mt-2">
                            <Tag :value="memo.memo_status" severity="info" rounded class="text-[10px]" />
                        </div>
                    </div>
                </div>

                <!-- Delivered Goods Table -->
                <div class="mt-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Delivered Products
                    </h2>

                    <table class="w-full text-xs text-left border-collapse border border-slate-300">
                        <thead>
                            <tr class="bg-slate-100 border-b border-slate-300 text-slate-700 font-bold uppercase text-[10px]">
                                <th class="p-2 border-r border-slate-300 w-10 text-center">#</th>
                                <th class="p-2 border-r border-slate-300 w-28">SKU Code</th>
                                <th class="p-2 border-r border-slate-300">Item Description</th>
                                <th class="p-2 border-r border-slate-300 w-20 text-center">Packaging</th>
                                <th class="p-2 border-r border-slate-300 w-20 text-right">Qty</th>
                                <th class="p-2 border-r border-slate-300 w-24 text-right">Rate (৳)</th>
                                <th class="p-2 w-28 text-right">Total (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, idx) in memo.items"
                                :key="item.id"
                                class="border-b border-slate-200"
                            >
                                <td class="p-2 border-r border-slate-200 text-center font-mono text-slate-500">{{ idx + 1 }}</td>
                                <td class="p-2 border-r border-slate-200 font-mono font-semibold">{{ item.product?.code }}</td>
                                <td class="p-2 border-r border-slate-200 font-bold">{{ item.product?.name }}</td>
                                <td class="p-2 border-r border-slate-200 text-center text-slate-600">{{ item.packaging_type }}</td>
                                <td class="p-2 border-r border-slate-200 text-right font-black font-mono">{{ Number(item.quantity).toFixed(0) }}</td>
                                <td class="p-2 border-r border-slate-200 text-right font-mono">{{ Number(item.unit_price).toFixed(2) }}</td>
                                <td class="p-2 text-right font-mono font-bold">{{ Number(item.line_total).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Returns & Exchanges Section (If Any) -->
                <div v-if="memo.returns && memo.returns.length > 0" class="mt-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-rose-600 mb-2">
                        Returns & Product Exchanges (Deductions Applied)
                    </h2>

                    <table class="w-full text-xs text-left border-collapse border border-rose-200">
                        <thead>
                            <tr class="bg-rose-50 border-b border-rose-200 text-rose-800 font-bold uppercase text-[10px]">
                                <th class="p-2 border-r border-rose-200">Returned Product</th>
                                <th class="p-2 border-r border-rose-200 w-44">Return Nature</th>
                                <th class="p-2 border-r border-rose-200 w-20 text-right">Qty</th>
                                <th class="p-2 border-r border-rose-200 w-24 text-right">Deduct Rate</th>
                                <th class="p-2 w-28 text-right text-rose-700">Deduction (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="ret in memo.returns" :key="ret.id" class="border-b border-rose-100 text-slate-800">
                                <td class="p-2 border-r border-rose-100 font-semibold">{{ ret.product?.name }}</td>
                                <td class="p-2 border-r border-rose-100 text-[11px]">
                                    <span :class="ret.return_type === 'DAMAGE' ? 'text-rose-600 font-bold' : 'text-blue-600 font-bold'">
                                        {{ ret.return_type === 'DAMAGE' ? 'Damage / Expiry Return' : 'Product Exchange (Good)' }}
                                    </span>
                                </td>
                                <td class="p-2 border-r border-rose-100 text-right font-mono font-bold">{{ Number(ret.quantity).toFixed(0) }}</td>
                                <td class="p-2 border-r border-rose-100 text-right font-mono">{{ Number(ret.unit_deduction_rate).toFixed(2) }}</td>
                                <td class="p-2 text-right font-mono font-bold text-rose-600">- ৳{{ Number(ret.total_deduction_amount).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Financial Ledger Calculation Summary Box -->
                <div class="mt-6 flex justify-end">
                    <div class="w-80 text-xs space-y-1.5 border border-slate-300 rounded-lg p-3 bg-slate-50">
                        <div class="flex justify-between text-slate-600">
                            <span>Delivered Subtotal:</span>
                            <span class="font-mono font-bold">৳ {{ Number(memo.subtotal).toFixed(2) }}</span>
                        </div>
                        <div v-if="Number(memo.damage_deduction) > 0" class="flex justify-between text-rose-600">
                            <span>- Damage Return:</span>
                            <span class="font-mono font-bold">- ৳ {{ Number(memo.damage_deduction).toFixed(2) }}</span>
                        </div>
                        <div v-if="Number(memo.exchange_deduction) > 0" class="flex justify-between text-blue-600">
                            <span>- Product Exchange:</span>
                            <span class="font-mono font-bold">- ৳ {{ Number(memo.exchange_deduction).toFixed(2) }}</span>
                        </div>
                        <div v-if="Number(memo.custom_deduction) > 0" class="flex justify-between text-amber-600">
                            <span>- Custom Discount:</span>
                            <span class="font-mono font-bold">- ৳ {{ Number(memo.custom_deduction).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-slate-900 border-t border-slate-300 pt-1.5">
                            <span>Net Memo Amount:</span>
                            <span class="font-mono text-sm text-indigo-700">৳ {{ Number(memo.grand_total).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>+ Previous Outstanding Due:</span>
                            <span class="font-mono">৳ {{ Number(memo.previous_due).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-slate-900 border-t border-slate-300 pt-1.5">
                            <span>Total Net Outstanding:</span>
                            <span class="font-mono text-sm">৳ {{ Number(memo.total_net_payable).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-emerald-700 font-bold">
                            <span>- Cash / Paid Amount:</span>
                            <span class="font-mono">৳ {{ Number(memo.paid_amount).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between font-black text-rose-600 text-sm border-t border-slate-400 pt-1.5">
                            <span>Remaining Due Balance:</span>
                            <span class="font-mono">৳ {{ Number(memo.due_amount).toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Signatures -->
                <div class="grid grid-cols-2 gap-12 mt-16 pt-6 border-t border-dashed border-slate-300 text-center text-xs">
                    <div>
                        <div class="h-8 border-b border-slate-400 mx-auto w-44 mb-1"></div>
                        <span class="font-bold text-slate-700 block">Retailer Signature & Seal</span>
                        <span class="text-[10px] text-slate-400">Goods Acknowledged & Balance Accepted</span>
                    </div>

                    <div>
                        <div class="h-8 border-b border-slate-400 mx-auto w-44 mb-1"></div>
                        <span class="font-bold text-slate-700 block">Sales Delivery Rep / Driver</span>
                        <span class="text-[10px] text-slate-400">Mominul Bekari Delivery Rep</span>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- THERMAL 80MM POS RECEIPT LAYOUT            -->
            <!-- ========================================== -->
            <div
                v-else
                class="thermal-receipt-container bg-white p-6 rounded-2xl shadow-sm border border-slate-200 mx-auto w-[340px] text-slate-900 font-mono text-xs print:w-[80mm] print:border-none print:shadow-none print:p-0"
            >
                <div class="text-center border-b border-dashed border-slate-400 pb-3">
                    <h1 class="text-lg font-black uppercase tracking-wider">MOMINUL BEKARI</h1>
                    <p class="text-[10px] text-slate-600 uppercase">Wholesale Distribution</p>
                    <p class="text-[10px] text-slate-500">Phone: 01711-000001</p>
                    <div class="mt-2 text-xs font-bold">{{ memo.memo_no }}</div>
                    <div class="text-[10px] text-slate-600">Date: {{ memo.memo_date }}</div>
                </div>

                <div class="py-2.5 border-b border-dashed border-slate-400 text-[11px]">
                    <div class="font-bold text-slate-900">{{ memo.retailer?.shop_name }}</div>
                    <div>Prop: {{ memo.retailer?.owner_name }}</div>
                    <div>Phone: {{ memo.retailer?.phone }}</div>
                    <div>Route: {{ memo.road?.name }}</div>
                </div>

                <!-- Items -->
                <div class="py-2.5 border-b border-dashed border-slate-400">
                    <div class="flex justify-between font-bold text-[10px] uppercase border-b pb-1 mb-1">
                        <span>Item</span>
                        <span>Qty x Rate</span>
                        <span>Total</span>
                    </div>

                    <div v-for="item in memo.items" :key="item.id" class="text-[11px] py-0.5">
                        <div class="font-bold truncate">{{ item.product?.name }}</div>
                        <div class="flex justify-between text-slate-600 text-[10px]">
                            <span>{{ item.product?.code }}</span>
                            <span>{{ Number(item.quantity).toFixed(0) }} x {{ Number(item.unit_price).toFixed(0) }}</span>
                            <span class="font-bold text-slate-900">৳{{ Number(item.line_total).toFixed(0) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Deductions -->
                <div v-if="Number(memo.damage_deduction) > 0 || Number(memo.exchange_deduction) > 0" class="py-2 border-b border-dashed border-slate-400 text-[10px] text-rose-600">
                    <div v-if="Number(memo.damage_deduction) > 0" class="flex justify-between">
                        <span>- Damage Return:</span>
                        <span>-৳{{ Number(memo.damage_deduction).toFixed(0) }}</span>
                    </div>
                    <div v-if="Number(memo.exchange_deduction) > 0" class="flex justify-between text-blue-600">
                        <span>- Exchange Deduct:</span>
                        <span>-৳{{ Number(memo.exchange_deduction).toFixed(0) }}</span>
                    </div>
                </div>

                <!-- Financial Calculation -->
                <div class="py-2.5 border-b-2 border-slate-900 space-y-1 text-xs">
                    <div class="flex justify-between">
                        <span>Net Memo:</span>
                        <span class="font-bold">৳ {{ Number(memo.grand_total).toFixed(0) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Prev Due:</span>
                        <span>৳ {{ Number(memo.previous_due).toFixed(0) }}</span>
                    </div>
                    <div class="flex justify-between font-bold border-t pt-1">
                        <span>Total Due:</span>
                        <span>৳ {{ Number(memo.total_net_payable).toFixed(0) }}</span>
                    </div>
                    <div class="flex justify-between text-emerald-700 font-bold">
                        <span>Cash Paid:</span>
                        <span>৳ {{ Number(memo.paid_amount).toFixed(0) }}</span>
                    </div>
                    <div class="flex justify-between font-black text-rose-600 text-sm border-t pt-1">
                        <span>Final Due:</span>
                        <span>৳ {{ Number(memo.due_amount).toFixed(0) }}</span>
                    </div>
                </div>

                <div class="text-center text-[10px] text-slate-500 pt-3">
                    Thank you for your business!<br />
                    Mominul Bekari Central Wholesale
                </div>
            </div>
        </div>

        <component :is="'style'" v-if="printFormat === 'THERMAL'">
        @media print {
            @page {
                size: 80mm auto;
                margin: 2mm;
            }
            html, body {
                width: 80mm !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
            }
            .thermal-receipt-container {
                width: 76mm !important;
                max-width: 76mm !important;
                margin: 0 auto !important;
                padding: 0 !important;
            }
        }
        </component>
    </AppLayout>
</template>

<style>
@media print {
    body {
        background: white !important;
    }
    aside, header, nav, .print\:hidden {
        display: none !important;
    }
}
</style>
