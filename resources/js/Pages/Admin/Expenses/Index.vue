<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppDataTable from '@/Components/AppDataTable.vue';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    expenses: {
        type: Object,
        default: () => ({ data: [], total: 0 }),
    },
    categories: {
        type: Array,
        default: () => [],
    },
    allCategories: {
        type: Array,
        default: () => [],
    },
    roads: {
        type: Array,
        default: () => [],
    },
    paymentMethods: {
        type: Array,
        default: () => [],
    },
    totalAmount: {
        type: Number,
        default: 0,
    },
    filters: {
        type: Object,
        default: () => ({ category_id: null, road_id: null, date_from: '', date_to: '' }),
    },
});

const selectedCategory = ref(props.filters.category_id || null);
const selectedRoad = ref(props.filters.road_id || null);
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const showExpenseModal = ref(false);
const showManageCategoriesModal = ref(false);
const showCategoryModal = ref(false);
const editingCategory = ref(null);
const categorySearch = ref('');

const form = useForm({
    expense_category_id: null,
    road_id: null,
    vehicle_number: '',
    expense_date: new Date().toISOString().split('T')[0],
    amount: null,
    payment_method: 'CASH',
    receipt_number: '',
    receipt_attachment: null,
    notes: '',
});

const categoryForm = useForm({
    name: '',
    code: '',
    description: '',
    is_active: true,
});

const openCreateCategoryModal = () => {
    editingCategory.value = null;
    categoryForm.reset();
    categoryForm.clearErrors();
    categoryForm.is_active = true;
    showCategoryModal.value = true;
};

const openEditCategoryModal = (cat) => {
    editingCategory.value = cat;
    categoryForm.clearErrors();
    categoryForm.name = cat.name;
    categoryForm.code = cat.code || '';
    categoryForm.description = cat.description || '';
    categoryForm.is_active = Boolean(cat.is_active);
    showCategoryModal.value = true;
};

const resetCategoryForm = () => {
    categoryForm.reset();
    categoryForm.clearErrors();
    editingCategory.value = null;
    showCategoryModal.value = false;
};

const submitCategory = () => {
    if (editingCategory.value) {
        categoryForm.put(route('expenses.categories.update', editingCategory.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                resetCategoryForm();
            },
        });
    } else {
        categoryForm.post(route('expenses.categories.store'), {
            preserveScroll: true,
            onSuccess: () => {
                resetCategoryForm();
            },
        });
    }
};

const deleteCategory = (cat) => {
    if (Number(cat.expenses_count || 0) > 0) {
        alert(`Category '${cat.name}' has ${cat.expenses_count} recorded expenses and cannot be deleted.`);
        return;
    }
    if (confirm(`Are you sure you want to delete expense category '${cat.name}'?`)) {
        router.delete(route('expenses.categories.destroy', cat.id), {
            preserveScroll: true,
        });
    }
};

const filteredCategories = computed(() => {
    const list = props.allCategories?.length ? props.allCategories : props.categories;
    if (!categorySearch.value) return list;
    const q = categorySearch.value.toLowerCase();
    return list.filter((c) =>
        c.name.toLowerCase().includes(q) || (c.code && c.code.toLowerCase().includes(q))
    );
});

const onRoadChange = (roadId) => {
    const road = props.roads.find((r) => r.id === roadId);
    if (road && road.vehicle_number) {
        form.vehicle_number = road.vehicle_number;
    }
};

const handleFileUpload = (event) => {
    form.receipt_attachment = event.target.files[0];
};

const filterData = () => {
    router.get(
        route('expenses.index'),
        {
            category_id: selectedCategory.value,
            road_id: selectedRoad.value,
            date_from: dateFrom.value,
            date_to: dateTo.value,
        },
        { preserveState: true }
    );
};

const resetExpenseForm = () => {
    form.reset();
    form.clearErrors();
    form.expense_date = new Date().toISOString().split('T')[0];
    form.payment_method = 'CASH';
    showExpenseModal.value = false;
};

const submitExpense = () => {
    form.post(route('expenses.store'), {
        onSuccess: () => {
            resetExpenseForm();
        },
    });
};

const deleteExpense = (id) => {
    if (confirm('Are you sure you want to delete this expense record?')) {
        router.delete(route('expenses.destroy', id));
    }
};
</script>

<template>
    <Head title="Operational Expenses" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                        Operational & Overhead Expenses
                    </h1>
                    <p class="text-sm text-slate-500">
                        Record and audit vehicle fuel, route maintenance, factory rent, staff wages, and depot overheads.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="bg-white px-3.5 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 shadow-sm">
                        Total Incurred: <span class="font-bold text-rose-700 font-mono text-sm">৳{{ Number(totalAmount).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                    </div>

                    <Button
                        icon="pi pi-tags"
                        label="Manage Categories"
                        severity="secondary"
                        outlined
                        size="small"
                        @click="showManageCategoriesModal = true"
                    />

                    <Button
                        icon="pi pi-plus"
                        label="Record Expense"
                        severity="primary"
                        size="small"
                        @click="showExpenseModal = true"
                    />
                </div>
            </div>

            <!-- Filter Ribbon -->
            <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm flex flex-wrap items-center gap-3">
                <div class="w-56">
                    <Select
                        v-model="selectedCategory"
                        :options="categories"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="All Categories"
                        filter
                        showClear
                        class="w-full text-xs"
                        @change="filterData"
                    />
                </div>

                <div class="w-48">
                    <Select
                        v-model="selectedRoad"
                        :options="roads"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="All Routes / Depot"
                        filter
                        showClear
                        class="w-full text-xs"
                        @change="filterData"
                    />
                </div>

                <div class="w-36">
                    <InputText v-model="dateFrom" type="date" class="w-full text-xs py-1.5 px-2" @change="filterData" />
                </div>
                <div class="w-36">
                    <InputText v-model="dateTo" type="date" class="w-full text-xs py-1.5 px-2" @change="filterData" />
                </div>

                <Button icon="pi pi-filter" label="Filter" severity="secondary" size="small" outlined @click="filterData" />
            </div>

            <!-- Table -->
            <AppDataTable
                :items="expenses.data || []"
                :total="expenses.total || 0"
                :perPage="expenses.per_page || 20"
                :currentPage="expenses.current_page || 1"
                tableTitle="Expense Ledger"
            >
                <Column field="expense_date" header="Date" style="width: 125px">
                    <template #body="{ data }">
                        <span class="text-xs text-slate-700 font-medium">
                            {{ formatDate(data.expense_date) }}
                        </span>
                    </template>
                </Column>

                <Column field="category" header="Category" style="width: 220px">
                    <template #body="{ data }">
                        <span class="font-semibold text-xs text-slate-800 bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200">
                            {{ data.category?.name }}
                        </span>
                    </template>
                </Column>

                <Column header="Route / Vehicle">
                    <template #body="{ data }">
                        <div v-if="data.road" class="font-bold text-xs text-slate-800 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            {{ data.road?.name }}
                        </div>
                        <div v-else class="text-xs text-slate-400">General Central Depot</div>
                        <div v-if="data.vehicle_number" class="text-xs font-mono text-slate-500 mt-0.5">
                            Vehicle: {{ data.vehicle_number }}
                        </div>
                    </template>
                </Column>

                <Column header="Receipt & Payment" style="width: 180px">
                    <template #body="{ data }">
                        <div class="font-mono text-xs text-slate-700">
                            {{ data.receipt_number || 'No receipt' }}
                        </div>
                        <div class="text-[10px] text-slate-500 mt-0.5">
                            Method: <strong>{{ data.payment_method }}</strong>
                        </div>
                    </template>
                </Column>

                <Column field="amount" header="Amount" style="width: 140px">
                    <template #body="{ data }">
                        <div class="font-mono font-bold text-slate-900 text-sm">
                            ৳{{ Number(data.amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                        </div>
                    </template>
                </Column>

                <Column field="creator" header="Recorded By" style="width: 140px">
                    <template #body="{ data }">
                        <span class="text-xs text-slate-600">{{ data.creator?.name || 'System' }}</span>
                    </template>
                </Column>

                <Column header="Actions" style="width: 80px">
                    <template #body="{ data }">
                        <Button
                            icon="pi pi-trash"
                            severity="danger"
                            size="small"
                            text
                            rounded
                            @click="deleteExpense(data.id)"
                        />
                    </template>
                </Column>
            </AppDataTable>

            <!-- Expense Dialog Modal -->
            <Dialog
                v-model:visible="showExpenseModal"
                header="Record Overhead Expense"
                :modal="true"
                @hide="resetExpenseForm"
                class="w-full max-w-lg"
            >
                <form @submit.prevent="submitExpense" class="space-y-4 pt-2">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700">Expense Category *</label>
                            <button
                                type="button"
                                @click="openCreateCategoryModal"
                                class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1 cursor-pointer transition-colors"
                            >
                                <i class="pi pi-plus text-[10px]"></i> New Category
                            </button>
                        </div>
                        <Select
                            v-model="form.expense_category_id"
                            :options="categories"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select Expense Category"
                            filter
                            class="w-full text-sm"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Route / Road (Optional)</label>
                            <Select
                                v-model="form.road_id"
                                :options="roads"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Route Incurred (Road A/B/C)"
                                filter
                                showClear
                                class="w-full text-sm"
                                @change="onRoadChange(form.road_id)"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Vehicle # (Optional)</label>
                            <InputText
                                v-model="form.vehicle_number"
                                placeholder="e.g. DHK-METRO-11-2034"
                                class="w-full text-sm"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Expense Date *</label>
                            <InputText
                                v-model="form.expense_date"
                                type="date"
                                class="w-full text-sm"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Amount (৳) *</label>
                            <InputNumber
                                v-model="form.amount"
                                :min="0.01"
                                :maxFractionDigits="2"
                                placeholder="0.00"
                                class="w-full font-mono font-bold"
                                required
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Payment Method *</label>
                            <Select
                                v-model="form.payment_method"
                                :options="paymentMethods"
                                optionLabel="label"
                                optionValue="value"
                                filter
                                class="w-full text-sm"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Receipt / Voucher #</label>
                            <InputText
                                v-model="form.receipt_number"
                                placeholder="Voucher / Slip No"
                                class="w-full text-sm"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Receipt Document / Voucher Attachment</label>
                        <input
                            type="file"
                            accept="image/*,.pdf"
                            class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200"
                            @change="handleFileUpload"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Remarks & Details</label>
                        <Textarea
                            v-model="form.notes"
                            rows="2"
                            placeholder="Add brief details about the expense..."
                            class="w-full text-sm"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <Button
                            type="button"
                            label="Cancel"
                            severity="secondary"
                            text
                            @click="showExpenseModal = false"
                        />
                        <Button
                            type="submit"
                            label="Record Expense"
                            severity="primary"
                            :loading="form.processing"
                        />
                    </div>
                </form>
            </Dialog>
            <!-- Manage Categories Modal -->
            <Dialog
                v-model:visible="showManageCategoriesModal"
                header="Expense Categories Directory"
                modal
                :style="{ width: '640px' }"
            >
                <div class="space-y-4 pt-2">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                        <div class="relative flex-1">
                            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <InputText
                                v-model="categorySearch"
                                placeholder="Search category name or code..."
                                class="w-full pl-8 pr-3 py-1.5 text-xs border-slate-300 rounded-lg"
                            />
                        </div>
                        <Button
                            label="Add Category"
                            icon="pi pi-plus"
                            size="small"
                            severity="primary"
                            @click="openCreateCategoryModal"
                        />
                    </div>

                    <div v-if="filteredCategories.length === 0" class="py-8 text-center text-slate-400">
                        <i class="pi pi-folder-open text-3xl mb-2 block"></i>
                        <p class="text-xs">No expense categories found.</p>
                    </div>

                    <div v-else class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                        <div
                            v-for="cat in filteredCategories"
                            :key="cat.id"
                            class="py-3 flex items-center justify-between gap-3 hover:bg-slate-50/70 px-2 rounded-lg transition-colors"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-sm text-slate-800 flex items-center gap-2 flex-wrap">
                                    <i class="pi pi-tag text-amber-500 text-xs"></i>
                                    <span>{{ cat.name }}</span>
                                    <span v-if="cat.code" class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ cat.code }}
                                    </span>
                                    <Tag
                                        :value="cat.is_active ? 'Active' : 'Inactive'"
                                        :severity="cat.is_active ? 'success' : 'secondary'"
                                        class="!text-[10px] !py-0.5 !px-1.5"
                                    />
                                    <span class="text-[11px] text-slate-400">
                                        ({{ cat.expenses_count ?? 0 }} {{ (cat.expenses_count ?? 0) === 1 ? 'expense' : 'expenses' }})
                                    </span>
                                </div>
                                <div v-if="cat.description" class="text-xs text-slate-500 mt-1 line-clamp-1">
                                    {{ cat.description }}
                                </div>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <Button
                                    icon="pi pi-pencil"
                                    severity="secondary"
                                    text
                                    rounded
                                    size="small"
                                    title="Edit category"
                                    @click="openEditCategoryModal(cat)"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    severity="danger"
                                    text
                                    rounded
                                    size="small"
                                    title="Delete category"
                                    :disabled="Number(cat.expenses_count || 0) > 0"
                                    @click="deleteCategory(cat)"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2 border-t border-slate-100">
                        <Button label="Close" severity="secondary" text @click="showManageCategoriesModal = false" />
                    </div>
                </div>
            </Dialog>

            <!-- Create / Edit Category Modal -->
            <Dialog
                v-model:visible="showCategoryModal"
                :header="editingCategory ? 'Edit Expense Category' : 'Create New Expense Category'"
                modal
                @hide="resetCategoryForm"
                :style="{ width: '450px' }"
            >
                <form @submit.prevent="submitCategory" class="space-y-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Category Name <span class="text-rose-500">*</span>
                        </label>
                        <InputText
                            v-model="categoryForm.name"
                            placeholder="e.g. Vehicle Fuel, Factory Utilities, Staff Refreshment"
                            class="w-full text-sm"
                            :class="{ 'p-invalid': categoryForm.errors.name }"
                            required
                        />
                        <small v-if="categoryForm.errors.name" class="text-rose-500 text-xs">{{ categoryForm.errors.name }}</small>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Code / Identifier
                        </label>
                        <InputText
                            v-model="categoryForm.code"
                            placeholder="e.g. FUEL, UTILITIES, SALARY"
                            class="w-full text-sm font-mono uppercase"
                            :class="{ 'p-invalid': categoryForm.errors.code }"
                        />
                        <small v-if="categoryForm.errors.code" class="text-rose-500 text-xs">{{ categoryForm.errors.code }}</small>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Leave blank to generate automatically from name.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Description</label>
                        <Textarea
                            v-model="categoryForm.description"
                            rows="2"
                            placeholder="Optional operational details..."
                            class="w-full text-sm"
                        />
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <ToggleSwitch v-model="categoryForm.is_active" />
                        <label class="text-xs font-semibold text-slate-700">Category Active Status</label>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <Button label="Cancel" severity="secondary" text @click="resetCategoryForm" />
                        <Button
                            :label="editingCategory ? 'Update Category' : 'Save Category'"
                            icon="pi pi-check"
                            type="submit"
                            :loading="categoryForm.processing"
                        />
                    </div>
                </form>
            </Dialog>
        </div>
    </AppLayout>
</template>
