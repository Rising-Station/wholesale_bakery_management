<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    purchase: {
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
    if (params.get('print') === 'true') {
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
        <Head :title="`Purchase Invoice: ${purchase.purchase_no}`" />

        <div class="space-y-6 max-w-4xl mx-auto">
            <!-- Non-printable Toolbar -->
            <div class="print:hidden flex flex-wrap items-center justify-between gap-4 bg-white p-4 rounded-xl shadow-sm border border-slate-200">
                <Link href="/purchases" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-slate-800">
                    <i class="pi pi-arrow-left"></i> Back to Purchase Invoices
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
                        label="Print Invoice"
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
                            Central Warehouse & Wholesale Hub
                        </p>
                        <div class="text-xs text-slate-500 mt-2">
                            Depot: Main Road, Wholesale Market &bull; Phone: 01711-000001
                        </div>
                    </div>

                    <div class="text-right">
                        <div class="inline-block px-3 py-1 rounded bg-slate-900 text-white font-mono text-xs font-bold uppercase tracking-wider mb-2">
                            Stock-In Purchase Bill
                        </div>
                        <div class="font-mono text-base font-bold text-slate-900">{{ purchase.purchase_no }}</div>
                        <div class="text-xs text-slate-500">Date: {{ formatDate(purchase.purchase_date) }}</div>
                    </div>
                </div>

                <!-- Supplier Info -->
                <div class="grid grid-cols-2 gap-6 py-6 border-b border-slate-200 text-xs">
                    <div>
                        <span class="text-slate-400 font-semibold uppercase text-[10px] block mb-1">Supplier / Manufacturer</span>
                        <div class="font-bold text-sm text-slate-900">{{ purchase.supplier?.name }}</div>
                        <div class="text-slate-600">{{ purchase.supplier?.company_name }}</div>
                        <div class="text-slate-500 mt-1">Phone: {{ purchase.supplier?.phone || 'N/A' }}</div>
                        <div class="text-slate-500">Address: {{ purchase.supplier?.address || 'N/A' }}</div>
                    </div>

                    <div class="text-right space-y-1">
                        <span class="text-slate-400 font-semibold uppercase text-[10px] block mb-1">Procurement Record</span>
                        <div><strong>Status:</strong> {{ purchase.status }}</div>
                        <div><strong>Logged By:</strong> {{ purchase.creator?.name || 'Authorized Staff' }}</div>
                        <div v-if="purchase.notes" class="text-slate-500 italic mt-1 text-[11px]">
                            Note: {{ purchase.notes }}
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="py-6">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b-2 border-slate-900 text-[11px] font-bold uppercase text-slate-700">
                                <th class="py-2 w-10">#</th>
                                <th class="py-2">Product Description</th>
                                <th class="py-2 w-28">Packaging</th>
                                <th class="py-2 text-right w-24">Qty Received</th>
                                <th class="py-2 text-right w-28">Unit Cost (৳)</th>
                                <th class="py-2 text-right w-32">Total (৳)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(item, idx) in purchase.items" :key="item.id" class="text-slate-800">
                                <td class="py-2.5 font-mono text-slate-400">{{ idx + 1 }}</td>
                                <td class="py-2.5 font-medium">
                                    <div class="font-bold">{{ item.product?.name }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">{{ item.product?.code }}</div>
                                </td>
                                <td class="py-2.5 text-slate-600 uppercase text-[11px] font-mono">
                                    {{ item.packaging_type }}
                                </td>
                                <td class="py-2.5 text-right font-mono font-bold">
                                    {{ Number(item.quantity).toLocaleString() }}
                                </td>
                                <td class="py-2.5 text-right font-mono">
                                    {{ Number(item.unit_cost).toFixed(2) }}
                                </td>
                                <td class="py-2.5 text-right font-mono font-bold">
                                    {{ Number(item.line_total).toFixed(2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Financial Calculation -->
                <div class="border-t-2 border-slate-900 pt-4 flex justify-end">
                    <div class="w-72 space-y-2 text-xs">
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal:</span>
                            <span class="font-mono">৳ {{ Number(purchase.subtotal).toFixed(2) }}</span>
                        </div>
                        <div v-if="Number(purchase.discount) > 0" class="flex justify-between text-amber-600">
                            <span>- Discount:</span>
                            <span class="font-mono font-bold">- ৳ {{ Number(purchase.discount).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-slate-900 border-t border-slate-300 pt-1.5">
                            <span>Grand Total Bill:</span>
                            <span class="font-mono text-sm text-indigo-700">৳ {{ Number(purchase.grand_total).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-emerald-700 font-bold">
                            <span>- Amount Paid:</span>
                            <span class="font-mono">৳ {{ Number(purchase.paid_amount).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between font-black text-rose-600 text-sm border-t border-slate-400 pt-1.5">
                            <span>Outstanding Due:</span>
                            <span class="font-mono">৳ {{ Number(purchase.due_amount).toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Signatures -->
                <div class="grid grid-cols-2 gap-12 mt-16 pt-6 border-t border-dashed border-slate-300 text-center text-xs">
                    <div>
                        <div class="h-8 border-b border-slate-400 mx-auto w-44 mb-1"></div>
                        <span class="font-bold text-slate-700 block">Supplier / Driver Signature</span>
                        <span class="text-[10px] text-slate-400">Goods Delivered & Verified</span>
                    </div>

                    <div>
                        <div class="h-8 border-b border-slate-400 mx-auto w-44 mb-1"></div>
                        <span class="font-bold text-slate-700 block">Warehouse Receiving Officer</span>
                        <span class="text-[10px] text-slate-400">Stock Inward Count & Quality Accepted</span>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- THERMAL 80MM POS RECEIPT LAYOUT            -->
            <!-- ========================================== -->
            <div
                v-else
                class="thermal-slip bg-white p-6 rounded-2xl shadow-sm border border-slate-200 mx-auto w-[340px] text-slate-900 font-mono text-xs print:w-[78mm] print:border-none print:shadow-none print:p-0"
            >
                <div class="text-center border-b border-dashed border-slate-400 pb-3">
                    <h1 class="text-lg font-black uppercase tracking-wider">MOMINUL BEKARI</h1>
                    <p class="text-[10px] text-slate-600 uppercase">Central Warehouse Stock-In</p>
                    <p class="text-[10px] text-slate-500">Phone: 01711-000001</p>
                    <div class="mt-2 text-xs font-bold">{{ purchase.purchase_no }}</div>
                    <div class="text-[10px] text-slate-600">Date: {{ formatDate(purchase.purchase_date) }}</div>
                </div>

                <div class="py-2.5 border-b border-dashed border-slate-400 text-[11px]">
                    <div class="font-bold text-slate-900">{{ purchase.supplier?.name }}</div>
                    <div>{{ purchase.supplier?.company_name }}</div>
                    <div>Phone: {{ purchase.supplier?.phone || 'N/A' }}</div>
                </div>

                <!-- Items -->
                <div class="py-2.5 border-b border-dashed border-slate-400">
                    <div class="flex justify-between font-bold text-[10px] uppercase border-b pb-1 mb-1">
                        <span>Item</span>
                        <span>Qty x Cost</span>
                        <span>Total</span>
                    </div>

                    <div v-for="item in purchase.items" :key="item.id" class="text-[11px] py-0.5">
                        <div class="font-bold truncate">{{ item.product?.name }}</div>
                        <div class="flex justify-between text-slate-600 text-[10px]">
                            <span>{{ item.product?.code }}</span>
                            <span>{{ Number(item.quantity).toFixed(0) }} x {{ Number(item.unit_cost).toFixed(0) }}</span>
                            <span class="font-bold text-slate-900">৳{{ Number(item.line_total).toFixed(0) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Financial Calculation -->
                <div class="py-2.5 border-b-2 border-slate-900 space-y-1 text-xs">
                    <div class="flex justify-between">
                        <span>Total Bill:</span>
                        <span class="font-bold">৳ {{ Number(purchase.grand_total).toFixed(0) }}</span>
                    </div>
                    <div class="flex justify-between text-emerald-700 font-bold">
                        <span>Paid:</span>
                        <span>৳ {{ Number(purchase.paid_amount).toFixed(0) }}</span>
                    </div>
                    <div class="flex justify-between font-black text-rose-600 text-sm border-t pt-1">
                        <span>Due:</span>
                        <span>৳ {{ Number(purchase.due_amount).toFixed(0) }}</span>
                    </div>
                </div>

                <div class="text-center text-[10px] text-slate-500 pt-3">
                    Warehouse Inward Recorded<br />
                    Mominul Bekari Hub
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    @page {
        size: auto;
        margin: 5mm;
    }
    body, main, #app, .flex, .min-h-screen {
        overflow: visible !important;
        height: auto !important;
        min-height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
    }
    aside, header, nav, .print\:hidden {
        display: none !important;
    }
}
</style>
