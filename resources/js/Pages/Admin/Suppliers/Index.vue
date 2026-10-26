<script setup>
import { ref } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppDataTable from '@/Components/AppDataTable.vue';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import CurrencyInput from '@/Components/CurrencyInput.vue';

const props = defineProps({
    suppliers: {
        type: Object,
        default: () => ({ data: [], total: 0 }),
    },
    metrics: {
        type: Object,
        default: () => ({ total_supplier_due: 0 }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
});

const searchQuery = ref(props.filters.search || '');
const showSupplierModal = ref(false);
const editingSupplier = ref(null);
const showDeleteModal = ref(false);
const supplierToDelete = ref(null);

const form = useForm({
    name: '',
    company_name: '',
    phone: '',
    email: '',
    address: '',
    opening_due: 0,
    is_active: true,
    notes: '',
});

const filterData = () => {
    router.get(
        '/suppliers',
        { search: searchQuery.value || undefined },
        { preserveState: true, replace: true }
    );
};

const openCreateModal = () => {
    editingSupplier.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    showSupplierModal.value = true;
};

const openEditModal = (supplier) => {
    editingSupplier.value = supplier;
    form.clearErrors();
    form.name = supplier.name;
    form.company_name = supplier.company_name || '';
    form.phone = supplier.phone;
    form.email = supplier.email || '';
    form.address = supplier.address || '';
    form.opening_due = Number(supplier.opening_due);
    form.is_active = Boolean(supplier.is_active);
    form.notes = supplier.notes || '';
    showSupplierModal.value = true;
};

const resetSupplierForm = () => {
    form.reset();
    form.clearErrors();
    editingSupplier.value = null;
    showSupplierModal.value = false;
};

const submitSupplier = () => {
    if (editingSupplier.value) {
        form.put(`/suppliers/${editingSupplier.value.id}`, {
            onSuccess: () => {
                resetSupplierForm();
            },
        });
    } else {
        form.post('/suppliers', {
            onSuccess: () => {
                resetSupplierForm();
            },
        });
    }
};

const confirmDelete = (supplier) => {
    supplierToDelete.value = supplier;
    showDeleteModal.value = true;
};

const deleteSupplier = () => {
    if (!supplierToDelete.value) return;
    router.delete(`/suppliers/${supplierToDelete.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            supplierToDelete.value = null;
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Suppliers Directory (Tier 1 Manufacturers)" />

        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                        <i class="pi pi-building text-blue-600"></i>
                        Tier 1: Suppliers & Manufacturers
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Procurement sources providing finished bakery goods (Chanachur, Biscuits, Toast, Cakes) to Mominul Bekari.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Button
                        label="Register New Supplier"
                        icon="pi pi-plus"
                        severity="primary"
                        @click="openCreateModal"
                    />
                </div>
            </div>

            <!-- Total Supplier Due Metric Card -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Registered Suppliers</span>
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                            <i class="pi pi-users text-lg"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-800 mt-2">
                        {{ suppliers.total || 0 }}
                    </div>
                    <div class="text-xs text-slate-400 mt-1">Active manufacturing partners</div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-500">Total Supplier Payable Due</span>
                        <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
                            <i class="pi pi-wallet text-lg"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-rose-600 mt-2">
                        ৳ {{ Number(metrics.total_supplier_due).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-xs text-slate-400 mt-1">Outstanding bills payable by Mominul Bekari</div>
                </div>
            </div>

            <!-- Search Filter Toolbar -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[260px]">
                    <div class="relative">
                        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <InputText
                            v-model="searchQuery"
                            placeholder="Search by supplier name, company, or phone..."
                            class="w-full pl-9 pr-4 py-2 text-sm"
                            @keyup.enter="filterData"
                        />
                    </div>
                </div>
                <Button
                    icon="pi pi-filter"
                    label="Search"
                    severity="secondary"
                    outlined
                    @click="filterData"
                />
            </div>

            <!-- Suppliers Table -->
            <AppDataTable
                :items="suppliers.data || []"
                :total="suppliers.total || 0"
                :perPage="suppliers.per_page || 20"
                :currentPage="suppliers.current_page || 1"
                tableTitle="Suppliers & Manufacturers Directory"
            >
                <Column field="name" header="Supplier / Manufacturer">
                    <template #body="{ data }">
                        <div>
                            <div class="font-bold text-slate-800">{{ data.name }}</div>
                            <div v-if="data.company_name" class="text-xs text-indigo-600 font-medium">
                                {{ data.company_name }}
                            </div>
                        </div>
                    </template>
                </Column>

                <Column field="phone" header="Contact Details" style="width: 180px">
                    <template #body="{ data }">
                        <div class="text-xs font-mono text-slate-700">{{ data.phone }}</div>
                        <div v-if="data.email" class="text-[11px] text-slate-400">{{ data.email }}</div>
                    </template>
                </Column>

                <Column field="purchases_count" header="Total Bills" style="width: 120px">
                    <template #body="{ data }">
                        <span class="font-semibold text-xs text-slate-600 bg-slate-100 px-2 py-1 rounded">
                            {{ data.purchases_count || 0 }} purchases
                        </span>
                    </template>
                </Column>

                <Column field="current_due" header="Outstanding Payable" style="width: 180px">
                    <template #body="{ data }">
                        <span
                            :class="[
                                'font-bold text-sm',
                                Number(data.current_due) > 0 ? 'text-rose-600' : 'text-slate-600'
                            ]"
                        >
                            ৳ {{ Number(data.current_due).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                        </span>
                    </template>
                </Column>

                <Column field="is_active" header="Status" style="width: 100px">
                    <template #body="{ data }">
                        <Tag
                            :value="data.is_active ? 'Active' : 'Inactive'"
                            :severity="data.is_active ? 'success' : 'secondary'"
                            rounded
                        />
                    </template>
                </Column>

                <Column header="Actions" style="width: 160px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1">
                            <Link :href="`/suppliers/${data.id}`">
                                <Button
                                    icon="pi pi-eye"
                                    label="Ledger"
                                    severity="info"
                                    size="small"
                                    text
                                />
                            </Link>
                            <Button
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                @click="openEditModal(data)"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                size="small"
                                @click="confirmDelete(data)"
                            />
                        </div>
                    </template>
                </Column>
            </AppDataTable>

            <!-- Supplier Create/Edit Modal -->
            <Dialog
                v-model:visible="showSupplierModal"
                :header="editingSupplier ? 'Edit Supplier Profile' : 'Register New Bakery Supplier'"
                modal
                @hide="resetSupplierForm"
                :style="{ width: '560px' }"
                class="p-fluid"
            >
                <form @submit.prevent="submitSupplier" class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Contact Person Name <span class="text-rose-500">*</span>
                            </label>
                            <InputText
                                v-model="form.name"
                                placeholder="e.g. Mahbub Alam"
                                :class="{ 'p-invalid': form.errors.name }"
                                class="w-full text-sm"
                            />
                            <small v-if="form.errors.name" class="text-rose-500 text-xs">{{ form.errors.name }}</small>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Company / Factory Name
                            </label>
                            <InputText
                                v-model="form.company_name"
                                placeholder="e.g. Bengal Food Products Ltd."
                                class="w-full text-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Phone Number <span class="text-rose-500">*</span>
                            </label>
                            <InputText
                                v-model="form.phone"
                                placeholder="e.g. 01711223344"
                                :class="{ 'p-invalid': form.errors.phone }"
                                class="w-full text-sm"
                            />
                            <small v-if="form.errors.phone" class="text-rose-500 text-xs">{{ form.errors.phone }}</small>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Email Address
                            </label>
                            <InputText
                                v-model="form.email"
                                type="email"
                                placeholder="supplier@example.com"
                                class="w-full text-sm"
                            />
                        </div>

                        <div v-if="!editingSupplier" class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Opening Due Payable (৳)
                            </label>
                            <CurrencyInput
                                v-model="form.opening_due"
                                placeholder="0.00"
                                class="w-full"
                            />
                            <small class="text-slate-400 text-xs">Initial outstanding bill amount owed by Mominul Bekari.</small>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Factory / Office Address</label>
                            <Textarea
                                v-model="form.address"
                                rows="2"
                                placeholder="Full factory location or depot address..."
                                class="w-full text-sm"
                            />
                        </div>

                        <div class="sm:col-span-2 flex items-center gap-3">
                            <ToggleSwitch v-model="form.is_active" />
                            <span class="text-sm font-medium text-slate-700">Active Supplier for Procurements</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                        <Button label="Cancel" severity="secondary" text @click="showSupplierModal = false" />
                        <Button
                            :label="editingSupplier ? 'Update Supplier' : 'Save Supplier'"
                            icon="pi pi-check"
                            type="submit"
                            :loading="form.processing"
                        />
                    </div>
                </form>
            </Dialog>

            <!-- Delete Confirmation Modal -->
            <ConfirmModal
                v-model:visible="showDeleteModal"
                title="Remove Supplier"
                :message="`Are you sure you want to remove '${supplierToDelete?.name}'? This cannot be undone.`"
                confirmLabel="Remove Supplier"
                severity="danger"
                @confirm="deleteSupplier"
            />
        </div>
    </AppLayout>
</template>
