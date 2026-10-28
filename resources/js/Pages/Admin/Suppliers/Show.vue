<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import CurrencyInput from '@/Components/CurrencyInput.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    supplier: {
        type: Object,
        required: true,
    },
    paymentMethods: {
        type: Array,
        default: () => [],
    },
});

const showPaymentModal = ref(false);

const paymentForm = useForm({
    supplier_id: props.supplier.id,
    purchase_id: null,
    payment_method: 'CASH',
    amount: 0,
    payment_date: new Date().toISOString().split('T')[0],
    transaction_ref: '',
    notes: '',
});

const openPaymentModal = () => {
    paymentForm.reset();
    paymentForm.clearErrors();
    paymentForm.supplier_id = props.supplier.id;
    paymentForm.payment_date = new Date().toISOString().split('T')[0];
    paymentForm.payment_method = 'CASH';
    paymentForm.amount = Number(props.supplier.current_due) > 0 ? Number(props.supplier.current_due) : 0;
    showPaymentModal.value = true;
};

const resetPaymentForm = () => {
    paymentForm.reset();
    paymentForm.clearErrors();
    showPaymentModal.value = false;
};

const submitPayment = () => {
    paymentForm.post('/suppliers/payments', {
        onSuccess: () => {
            resetPaymentForm();
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="`Supplier Ledger: ${supplier.name}`" />

        <div class="space-y-6">
            <!-- Breadcrumbs & Actions -->
            <div class="flex items-center justify-between">
                <Link href="/suppliers" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-slate-800">
                    <i class="pi pi-arrow-left"></i> Back to Suppliers Directory
                </Link>
                <Button
                    label="Record Disbursement Payment"
                    icon="pi pi-credit-card"
                    severity="success"
                    @click="openPaymentModal"
                />
            </div>

            <!-- Profile & Balances Card -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-black text-xl">
                            {{ supplier.name.charAt(0) }}
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">{{ supplier.name }}</h1>
                            <div class="text-sm font-medium text-indigo-600">{{ supplier.company_name || 'Independent Supplier' }}</div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-4 text-xs text-slate-500">
                        <div class="flex items-center gap-1.5">
                            <i class="pi pi-phone text-slate-400"></i>
                            <span class="font-mono text-slate-700">{{ supplier.phone }}</span>
                        </div>
                        <div v-if="supplier.email" class="flex items-center gap-1.5">
                            <i class="pi pi-envelope text-slate-400"></i>
                            <span>{{ supplier.email }}</span>
                        </div>
                        <div v-if="supplier.address" class="flex items-center gap-1.5">
                            <i class="pi pi-map-marker text-slate-400"></i>
                            <span>{{ supplier.address }}</span>
                        </div>
                    </div>
                </div>

                <!-- Live Due Balance Box -->
                <div class="bg-rose-50 border border-rose-200 rounded-2xl p-5 text-center">
                    <span class="text-xs font-bold uppercase tracking-wider text-rose-600 block">
                        Outstanding Payable Due
                    </span>
                    <div class="text-3xl font-black text-rose-700 mt-1">
                        ৳ {{ Number(supplier.current_due).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-[11px] text-rose-500 mt-1">
                        Opening balance: ৳ {{ Number(supplier.opening_due).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                    </div>
                </div>
            </div>

            <!-- Purchases History Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <h2 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="pi pi-shopping-cart text-blue-600"></i>
                    Procurement Invoices (Stock-In) History
                </h2>

                <DataTable
                    :value="supplier.purchases || []"
                    stripedRows
                    class="p-datatable-sm"
                    emptyMessage="No purchases recorded from this supplier yet."
                >
                    <Column field="purchase_no" header="Invoice #">
                        <template #body="{ data }">
                            <span class="font-mono text-xs font-bold text-slate-700 bg-slate-100 px-2 py-1 rounded">
                                {{ data.purchase_no }}
                            </span>
                        </template>
                    </Column>
                    <Column field="purchase_date" header="Date">
                        <template #body="{ data }">
                            <span class="text-xs text-slate-700 font-medium">{{ formatDate(data.purchase_date) }}</span>
                        </template>
                    </Column>
                    <Column header="Products Procured">
                        <template #body="{ data }">
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="item in data.items"
                                    :key="item.id"
                                    class="text-[11px] font-mono px-1.5 py-0.5 rounded bg-slate-100 text-slate-600"
                                >
                                    {{ item.product?.code }} (x{{ Number(item.quantity).toFixed(0) }})
                                </span>
                            </div>
                        </template>
                    </Column>
                    <Column field="grand_total" header="Total (৳)">
                        <template #body="{ data }">
                            <span class="font-bold text-slate-800">
                                ৳ {{ Number(data.grand_total).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                            </span>
                        </template>
                    </Column>
                    <Column field="paid_amount" header="Paid (৳)">
                        <template #body="{ data }">
                            <span class="text-emerald-600 font-semibold">
                                ৳ {{ Number(data.paid_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                            </span>
                        </template>
                    </Column>
                    <Column field="due_amount" header="Due (৳)">
                        <template #body="{ data }">
                            <span :class="Number(data.due_amount) > 0 ? 'text-rose-600 font-bold' : 'text-slate-400'">
                                ৳ {{ Number(data.due_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                            </span>
                        </template>
                    </Column>
                    <Column field="status" header="Status">
                        <template #body="{ data }">
                            <Tag :value="data.status" severity="info" rounded class="text-[10px]" />
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- Payments History Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <h2 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="pi pi-credit-card text-emerald-600"></i>
                    Disbursement & Payment History
                </h2>

                <DataTable
                    :value="supplier.payments || []"
                    stripedRows
                    class="p-datatable-sm"
                    emptyMessage="No payments made to this supplier yet."
                >
                    <Column field="payment_date" header="Payment Date">
                        <template #body="{ data }">
                            <span class="text-xs text-slate-700 font-medium">{{ formatDate(data.payment_date) }}</span>
                        </template>
                    </Column>
                    <Column field="payment_method" header="Method">
                        <template #body="{ data }">
                            <Tag :value="data.payment_method" severity="secondary" rounded class="text-[11px]" />
                        </template>
                    </Column>
                    <Column field="amount" header="Amount Paid">
                        <template #body="{ data }">
                            <span class="font-bold text-emerald-700">
                                ৳ {{ Number(data.amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                            </span>
                        </template>
                    </Column>
                    <Column field="transaction_ref" header="Reference / Cheque #">
                        <template #body="{ data }">
                            <span class="font-mono text-xs text-slate-600">{{ data.transaction_ref || '-' }}</span>
                        </template>
                    </Column>
                    <Column field="notes" header="Notes" />
                </DataTable>
            </div>

            <!-- Record Payment Dialog -->
            <Dialog
                v-model:visible="showPaymentModal"
                header="Record Supplier Payment"
                modal
                @hide="resetPaymentForm"
                :style="{ width: '480px' }"
                class="p-fluid"
            >
                <form @submit.prevent="submitPayment" class="space-y-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Payment Amount (৳) <span class="text-rose-500">*</span>
                        </label>
                        <CurrencyInput
                            v-model="paymentForm.amount"
                            placeholder="0.00"
                            class="w-full"
                        />
                        <small v-if="paymentForm.errors.amount" class="text-rose-500 text-xs">{{ paymentForm.errors.amount }}</small>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Payment Method <span class="text-rose-500">*</span>
                        </label>
                        <Select
                            v-model="paymentForm.payment_method"
                            :options="paymentMethods"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Select Payment Method"
                            class="w-full text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Payment Date <span class="text-rose-500">*</span>
                        </label>
                        <InputText
                            v-model="paymentForm.payment_date"
                            type="date"
                            class="w-full text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Transaction Reference / Cheque #
                        </label>
                        <InputText
                            v-model="paymentForm.transaction_ref"
                            placeholder="e.g. Bank Trx ID, Cheque No, bKash Trx ID"
                            class="w-full text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Notes / Remarks
                        </label>
                        <Textarea
                            v-model="paymentForm.notes"
                            rows="2"
                            placeholder="Additional payment details..."
                            class="w-full text-sm"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                        <Button label="Cancel" severity="secondary" text @click="showPaymentModal = false" />
                        <Button label="Confirm Payment" icon="pi pi-check" type="submit" severity="success" :loading="paymentForm.processing" />
                    </div>
                </form>
            </Dialog>
        </div>
    </AppLayout>
</template>
