<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    trip: {
        type: Object,
        required: true,
    },
});

const printPass = () => {
    window.print();
};

const dispatchVehicle = () => {
    router.patch(`/trips/${props.trip.id}/dispatch`, {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="`Gate Pass Manifest: ${trip.trip_no}`" />

        <div class="space-y-6 max-w-4xl mx-auto">
            <!-- Non-printable top action toolbar -->
            <div class="print:hidden flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border border-slate-200">
                <Link href="/trips" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-slate-800">
                    <i class="pi pi-arrow-left"></i> Back to Vehicle Trips
                </Link>
                <div class="flex items-center gap-3">
                    <Button
                        v-if="trip.status === 'LOADED'"
                        label="Dispatch Vehicle Now"
                        icon="pi pi-send"
                        severity="warn"
                        @click="dispatchVehicle"
                    />
                    <Button
                        label="Print Gate Pass Manifest"
                        icon="pi pi-print"
                        severity="primary"
                        @click="printPass"
                    />
                </div>
            </div>

            <!-- Printable Sheet -->
            <div class="bg-white p-8 md:p-12 rounded-2xl shadow-sm border border-slate-200 print:border-none print:shadow-none print:p-0">
                <!-- Header -->
                <div class="border-b-2 border-slate-900 pb-6 text-center">
                    <h1 class="text-2xl font-black uppercase tracking-wider text-slate-900">
                        Mominul Bekari
                    </h1>
                    <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold mt-1">
                        Central Wholesale Distribution Hub & Depot
                    </p>
                    <div class="inline-block mt-3 px-4 py-1 rounded bg-slate-900 text-white font-mono text-xs font-bold uppercase tracking-wider">
                        Vehicle Loading Manifest / Gate Pass
                    </div>
                </div>

                <!-- Trip Details Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 py-6 border-b border-slate-200 text-xs">
                    <div>
                        <span class="text-slate-400 font-semibold block uppercase text-[10px]">Trip No</span>
                        <span class="font-mono font-bold text-slate-800 text-sm">{{ trip.trip_no }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 font-semibold block uppercase text-[10px]">Dispatch Date</span>
                        <span class="font-bold text-slate-800">{{ formatDate(trip.trip_date) }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 font-semibold block uppercase text-[10px]">Route / Road</span>
                        <span class="font-bold text-slate-800">{{ trip.road?.name }} ({{ trip.road?.code }})</span>
                    </div>

                    <div>
                        <span class="text-slate-400 font-semibold block uppercase text-[10px]">Trip Status</span>
                        <Tag :value="trip.status" severity="info" rounded class="text-[10px]" />
                    </div>

                    <div>
                        <span class="text-slate-400 font-semibold block uppercase text-[10px]">Vehicle Reg #</span>
                        <span class="font-mono font-bold text-slate-800">{{ trip.vehicle_number }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 font-semibold block uppercase text-[10px]">Driver In-Charge</span>
                        <span class="font-bold text-slate-800">{{ trip.driver_name }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 font-semibold block uppercase text-[10px]">Driver Contact</span>
                        <span class="font-mono text-slate-700">{{ trip.driver_phone || '-' }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 font-semibold block uppercase text-[10px]">Created By</span>
                        <span class="text-slate-700">{{ trip.creator?.name || 'Storekeeper' }}</span>
                    </div>
                </div>

                <!-- Loaded Items Table -->
                <div class="mt-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">
                        Loaded Products Manifest
                    </h2>

                    <table class="w-full text-xs text-left border-collapse border border-slate-300">
                        <thead>
                            <tr class="bg-slate-100 border-b border-slate-300 text-slate-700 font-bold uppercase text-[10px]">
                                <th class="p-2.5 border-r border-slate-300 w-12 text-center">#</th>
                                <th class="p-2.5 border-r border-slate-300 w-28">SKU Code</th>
                                <th class="p-2.5 border-r border-slate-300">Product Description</th>
                                <th class="p-2.5 border-r border-slate-300 w-32">Packaging Type</th>
                                <th class="p-2.5 w-32 text-right">Loaded Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, idx) in trip.items"
                                :key="item.id"
                                class="border-b border-slate-200"
                            >
                                <td class="p-2.5 border-r border-slate-200 text-center font-mono text-slate-500">
                                    {{ idx + 1 }}
                                </td>
                                <td class="p-2.5 border-r border-slate-200 font-mono font-semibold text-slate-800">
                                    {{ item.product?.code }}
                                </td>
                                <td class="p-2.5 border-r border-slate-200 font-bold text-slate-800">
                                    {{ item.product?.name }}
                                </td>
                                <td class="p-2.5 border-r border-slate-200 text-slate-600">
                                    {{ item.product?.packaging_type }}
                                </td>
                                <td class="p-2.5 text-right font-black font-mono text-sm text-slate-900">
                                    {{ Number(item.loaded_quantity).toLocaleString() }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50 font-bold text-slate-800">
                                <td colspan="4" class="p-2.5 text-right uppercase text-[11px] border-r border-slate-300">
                                    Total Loaded Units:
                                </td>
                                <td class="p-2.5 text-right font-black font-mono text-base text-indigo-700">
                                    {{ trip.items?.reduce((s, i) => s + Number(i.loaded_quantity || 0), 0).toLocaleString() }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Signatures -->
                <div class="grid grid-cols-2 gap-12 mt-20 pt-8 border-t border-dashed border-slate-300 text-center text-xs">
                    <div>
                        <div class="h-10 border-b border-slate-400 mx-auto w-48 mb-2"></div>
                        <span class="font-bold text-slate-700 block">Warehouse Storekeeper Signature</span>
                        <span class="text-[10px] text-slate-400">Stock Dispatched & Verified</span>
                    </div>

                    <div>
                        <div class="h-10 border-b border-slate-400 mx-auto w-48 mb-2"></div>
                        <span class="font-bold text-slate-700 block">Driver Receiving Signature</span>
                        <span class="text-[10px] text-slate-400">Goods Acknowledged & Received</span>
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
    }
    aside, header, nav {
        display: none !important;
    }
}
</style>
