<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppDataTable from '@/Components/AppDataTable.vue';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import CurrencyInput from '@/Components/CurrencyInput.vue';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

const props = defineProps({
    products: {
        type: Object,
        default: () => ({ data: [], total: 0 }),
    },
    categories: {
        type: Array,
        default: () => [],
    },
    packagingTypes: {
        type: Array,
        default: () => [],
    },
    allPackagingTypes: {
        type: Array,
        default: () => [],
    },
    units: {
        type: Array,
        default: () => [],
    },
    allUnits: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ category_id: null, search: '', packaging_type: null }),
    },
});

const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category_id || null);
const selectedPackaging = ref(props.filters.packaging_type || null);

const showProductModal = ref(false);
const editingProduct = ref(null);
const showCategoryModal = ref(false);
const showManageCategoriesModal = ref(false);
const showPackagingTypeModal = ref(false);
const showManagePackagingTypesModal = ref(false);
const editingPackagingType = ref(null);
const showUnitModal = ref(false);
const showManageUnitsModal = ref(false);
const editingUnit = ref(null);
const showDeleteModal = ref(false);
const productToDelete = ref(null);

const form = useForm({
    category_id: null,
    unit_id: null,
    packaging_type_id: null,
    name: '',
    code: '',
    packaging_type: 'PIECES',
    units_per_cartoon: 1,
    weight_per_unit_kg: null,
    pieces_per_packet: null,
    cost_price: 0,
    wholesale_price: 0,
    warehouse_stock: 0,
    min_alert_quantity: 10,
    is_active: true,
    notes: '',
});

const categoryForm = useForm({
    name: '',
    description: '',
    is_active: true,
});

const packagingTypeForm = useForm({
    name: '',
    code: '',
    short_label: '',
    description: '',
    is_active: true,
});

const unitForm = useForm({
    name: '',
    short_code: '',
    description: '',
    is_active: true,
});

const resetProductForm = () => {
    editingProduct.value = null;
    form.reset();
    form.clearErrors();
    showProductModal.value = false;
};

const resetCategoryForm = () => {
    categoryForm.reset();
    categoryForm.clearErrors();
    showCategoryModal.value = false;
};

const resetPackagingTypeForm = () => {
    editingPackagingType.value = null;
    packagingTypeForm.reset();
    packagingTypeForm.clearErrors();
    showPackagingTypeModal.value = false;
};

const resetUnitForm = () => {
    editingUnit.value = null;
    unitForm.reset();
    unitForm.clearErrors();
    showUnitModal.value = false;
};

const filterData = () => {
    router.get(
        route('products.index'),
        {
            search: searchQuery.value || undefined,
            category_id: selectedCategory.value || undefined,
            packaging_type: selectedPackaging.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const openCreateModal = () => {
    editingProduct.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    form.packaging_type = 'PIECES';
    form.units_per_cartoon = 1;
    form.warehouse_stock = 0;
    form.min_alert_quantity = 10;
    if (props.categories.length > 0) {
        form.category_id = props.categories[0].id;
    }
    if (props.units.length > 0) {
        form.unit_id = props.units[0].id;
    }
    showProductModal.value = true;
};

const openEditModal = (product) => {
    editingProduct.value = product;
    form.clearErrors();
    form.category_id = product.category_id;
    form.unit_id = product.unit_id;
    form.packaging_type_id = product.packaging_type_id;
    form.name = product.name;
    form.code = product.code;
    form.packaging_type = product.packaging_type;
    form.units_per_cartoon = product.units_per_cartoon || 1;
    form.weight_per_unit_kg = product.weight_per_unit_kg;
    form.pieces_per_packet = product.pieces_per_packet;
    form.cost_price = Number(product.cost_price);
    form.wholesale_price = Number(product.wholesale_price);
    form.warehouse_stock = Number(product.warehouse_stock);
    form.min_alert_quantity = product.min_alert_quantity;
    form.is_active = Boolean(product.is_active);
    form.notes = product.notes || '';
    showProductModal.value = true;
};

const submitProduct = () => {
    if (editingProduct.value) {
        form.put(route('products.update', editingProduct.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                resetProductForm();
                toast.add({
                    severity: 'success',
                    summary: 'Product Updated',
                    detail: `Product '${form.name}' updated successfully.`,
                    life: 3500,
                });
            },
        });
    } else {
        form.post(route('products.store'), {
            preserveScroll: true,
            onSuccess: () => {
                resetProductForm();
                toast.add({
                    severity: 'success',
                    summary: 'Product Created',
                    detail: `Product '${form.name}' created successfully. Stock will be added upon supplier purchase.`,
                    life: 3500,
                });
            },
        });
    }
};

const openCategoryModal = () => {
    categoryForm.reset();
    categoryForm.clearErrors();
    categoryForm.is_active = true;
    showCategoryModal.value = true;
};

const submitCategory = () => {
    categoryForm.post(route('products.categories.store'), {
        preserveScroll: true,
        onSuccess: () => {
            const savedName = categoryForm.name;
            resetCategoryForm();
            toast.add({
                severity: 'success',
                summary: 'Category Created',
                detail: `Category '${savedName}' created successfully.`,
                life: 3500,
            });
        },
    });
};

const openPackagingTypeModal = () => {
    editingPackagingType.value = null;
    packagingTypeForm.reset();
    packagingTypeForm.clearErrors();
    packagingTypeForm.is_active = true;
    showPackagingTypeModal.value = true;
};

const openEditPackagingTypeModal = (pt) => {
    editingPackagingType.value = pt;
    packagingTypeForm.clearErrors();
    packagingTypeForm.name = pt.name;
    packagingTypeForm.code = pt.code;
    packagingTypeForm.short_label = pt.short_label || '';
    packagingTypeForm.description = pt.description || '';
    packagingTypeForm.is_active = Boolean(pt.is_active);
    showPackagingTypeModal.value = true;
};

const submitPackagingType = () => {
    if (editingPackagingType.value) {
        packagingTypeForm.put(route('products.packaging-types.update', editingPackagingType.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                resetPackagingTypeForm();
                toast.add({
                    severity: 'success',
                    summary: 'Packaging Type Updated',
                    detail: 'Packaging type updated successfully.',
                    life: 3500,
                });
            },
        });
    } else {
        packagingTypeForm.post(route('products.packaging-types.store'), {
            preserveScroll: true,
            onSuccess: () => {
                resetPackagingTypeForm();
                toast.add({
                    severity: 'success',
                    summary: 'Packaging Type Created',
                    detail: 'Packaging type registered successfully.',
                    life: 3500,
                });
            },
        });
    }
};

const deletePackagingType = (pt) => {
    if (confirm(`Are you sure you want to delete packaging type '${pt.name}'?`)) {
        router.delete(route('products.packaging-types.destroy', pt.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Packaging Type Deleted',
                    detail: `Packaging type '${pt.name}' deleted.`,
                    life: 3500,
                });
            },
        });
    }
};

const openUnitModal = () => {
    editingUnit.value = null;
    unitForm.reset();
    unitForm.clearErrors();
    unitForm.is_active = true;
    showUnitModal.value = true;
};

const openEditUnitModal = (unit) => {
    editingUnit.value = unit;
    unitForm.clearErrors();
    unitForm.name = unit.name;
    unitForm.short_code = unit.short_code;
    unitForm.description = unit.description || '';
    unitForm.is_active = Boolean(unit.is_active);
    showUnitModal.value = true;
};

const submitUnit = () => {
    if (editingUnit.value) {
        unitForm.put(route('products.units.update', editingUnit.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                resetUnitForm();
                toast.add({
                    severity: 'success',
                    summary: 'Unit Updated',
                    detail: 'Unit updated successfully.',
                    life: 3500,
                });
            },
        });
    } else {
        unitForm.post(route('products.units.store'), {
            preserveScroll: true,
            onSuccess: () => {
                resetUnitForm();
                toast.add({
                    severity: 'success',
                    summary: 'Unit Created',
                    detail: 'Unit registered successfully.',
                    life: 3500,
                });
            },
        });
    }
};

const deleteUnit = (unit) => {
    if (confirm(`Are you sure you want to delete unit '${unit.name}'?`)) {
        router.delete(route('products.units.destroy', unit.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Unit Deleted',
                    detail: `Unit '${unit.name}' deleted.`,
                    life: 3500,
                });
            },
        });
    }
};
            // If new category was created and product modal is open, auto-select it
            if (showProductModal.value && page.props.categories?.length > 0) {
                const matched = page.props.categories.find(
                    (c) => c.name.toLowerCase() === savedName.toLowerCase()
                );
                if (matched) {
                    form.category_id = matched.id;
                }
            }
            categoryForm.reset();
            categoryForm.clearErrors();
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Validation Error',
                detail: categoryForm.errors.name || 'Please check the category form inputs.',
                life: 4000,
            });
        },
    });
};

const deleteCategory = (category) => {
    if (!confirm(`Are you sure you want to delete category '${category.name}'?`)) return;

    router.delete(route('products.categories.destroy', category.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Category Deleted',
                detail: `Category '${category.name}' deleted successfully.`,
                life: 3000,
            });
        },
        onError: (err) => {
            toast.add({
                severity: 'error',
                summary: 'Cannot Delete Category',
                detail: err?.message || 'Category cannot be deleted because products are assigned to it.',
                life: 4500,
            });
        },
    });
};

const confirmDelete = (product) => {
    productToDelete.value = product;
    showDeleteModal.value = true;
};

const deleteProduct = () => {
    if (!productToDelete.value) return;
    const name = productToDelete.value.name;
    router.delete(route('products.destroy', productToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            productToDelete.value = null;
            toast.add({
                severity: 'success',
                summary: 'Product Deleted',
                detail: `Product '${name}' removed.`,
                life: 3500,
            });
        },
        onError: (err) => {
            toast.add({
                severity: 'error',
                summary: 'Cannot Delete Product',
                detail: err?.message || 'Product has recorded stock transactions and cannot be deleted.',
                life: 4500,
            });
        },
    });
};

const getPackagingBadge = (type) => {
    const map = {
        CARTOON: { label: 'Cartoon Pack', severity: 'info' },
        PACKET_5KG: { label: '5kg Bulk Pkt', severity: 'warn' },
        PACKET_12PCS: { label: '12 Pcs Pkt', severity: 'success' },
        PIECES: { label: 'Single Pcs', severity: 'secondary' },
        CUSTOM: { label: 'Custom Pkg', severity: 'contrast' },
    };
    return map[type] || { label: type, severity: 'secondary' };
};

const generateSku = () => {
    const prefix = form.name ? form.name.slice(0, 3).toUpperCase().replace(/[^A-Z]/g, 'PRD') : 'PRD';
    const rand = Math.floor(1000 + Math.random() * 9000);
    form.code = `${prefix}-${rand}`;
};
</script>

<template>
    <AppLayout>
        <Head title="Product Catalog & Packaging" />

        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                        <i class="pi pi-tags text-indigo-600"></i>
                        Product Catalog & Packaging
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Manage bakery products, multi-tier packaging types (Cartons, 5kg bulk, 12pcs snack packs), wholesale pricing & minimum alert thresholds.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        label="Categories"
                        icon="pi pi-folder"
                        severity="secondary"
                        outlined
                        size="small"
                        @click="showManageCategoriesModal = true"
                    />
                    <Button
                        label="Packaging Types"
                        icon="pi pi-box"
                        severity="secondary"
                        outlined
                        size="small"
                        @click="showManagePackagingTypesModal = true"
                    />
                    <Button
                        label="Units"
                        icon="pi pi-sliders-v"
                        severity="secondary"
                        outlined
                        size="small"
                        @click="showManageUnitsModal = true"
                    />
                    <Button
                        label="Add Product"
                        icon="pi pi-plus"
                        severity="primary"
                        size="small"
                        @click="openCreateModal"
                    />
                </div>
            </div>

            <!-- Filters & Search Toolbar -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[240px]">
                    <div class="relative">
                        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <InputText
                            v-model="searchQuery"
                            placeholder="Search by product name or SKU code..."
                            class="w-full pl-9 pr-4 py-2 border-slate-300 rounded-lg text-sm"
                            @keyup.enter="filterData"
                        />
                    </div>
                </div>

                <div class="w-48">
                    <Select
                        v-model="selectedCategory"
                        :options="categories"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="All Categories"
                        showClear
                        class="w-full text-sm"
                        @change="filterData"
                    />
                </div>

                <div class="w-48">
                    <Select
                        v-model="selectedPackaging"
                        :options="packagingTypes"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="All Packaging Types"
                        showClear
                        class="w-full text-sm"
                        @change="filterData"
                    />
                </div>

                <Button
                    icon="pi pi-filter"
                    label="Filter"
                    severity="secondary"
                    outlined
                    @click="filterData"
                />
            </div>

            <!-- Products Table -->
            <AppDataTable
                :items="products.data || []"
                :total="products.total || 0"
                :perPage="products.per_page || 20"
                :currentPage="products.current_page || 1"
                tableTitle="Bakery Product Catalog"
            >
                <Column field="code" header="SKU / Code" style="width: 130px">
                    <template #body="{ data }">
                        <span class="font-mono text-xs font-semibold px-2 py-1 bg-slate-100 border border-slate-200 rounded text-slate-700">
                            {{ data.code }}
                        </span>
                    </template>
                </Column>

                <Column field="name" header="Product Details">
                    <template #body="{ data }">
                        <div>
                            <div class="font-semibold text-slate-800">{{ data.name }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                {{ data.category?.name || 'Uncategorized' }}
                            </div>
                        </div>
                    </template>
                </Column>

                <Column field="packaging_type" header="Packaging Type" style="width: 180px">
                    <template #body="{ data }">
                        <Tag
                            :value="getPackagingBadge(data.packaging_type).label"
                            :severity="getPackagingBadge(data.packaging_type).severity"
                            rounded
                        />
                        <div v-if="data.units_per_cartoon > 1" class="text-[11px] text-slate-500 mt-1">
                            {{ data.units_per_cartoon }} pcs / carton
                        </div>
                        <div v-else-if="data.weight_per_unit_kg" class="text-[11px] text-slate-500 mt-1">
                            {{ data.weight_per_unit_kg }} kg / pack
                        </div>
                    </template>
                </Column>

                <Column field="cost_price" header="Cost Price" style="width: 130px">
                    <template #body="{ data }">
                        <span class="text-slate-600 text-sm font-medium">
                            ৳ {{ Number(data.cost_price).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                        </span>
                    </template>
                </Column>

                <Column field="wholesale_price" header="Wholesale Price" style="width: 140px">
                    <template #body="{ data }">
                        <span class="text-emerald-700 font-bold text-sm">
                            ৳ {{ Number(data.wholesale_price).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                        </span>
                    </template>
                </Column>

                <Column field="warehouse_stock" header="Central Stock" style="width: 150px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span
                                :class="[
                                    'font-bold text-sm',
                                    Number(data.warehouse_stock) <= Number(data.min_alert_quantity)
                                        ? 'text-rose-600'
                                        : 'text-slate-800'
                                ]"
                            >
                                {{ Number(data.warehouse_stock).toLocaleString() }}
                            </span>
                            <Tag
                                v-if="Number(data.warehouse_stock) <= Number(data.min_alert_quantity)"
                                value="Low Stock"
                                severity="danger"
                                class="text-[10px]"
                            />
                        </div>
                        <div class="text-[11px] text-slate-400">
                            Min Alert: {{ data.min_alert_quantity }}
                        </div>
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

                <Column header="Actions" style="width: 110px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1">
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

            <!-- Product Create/Edit Modal -->
            <Dialog
                v-model:visible="showProductModal"
                :header="editingProduct ? 'Edit Product' : 'Register New Bakery Product'"
                modal
                @hide="resetProductForm"
                :style="{ width: '680px' }"
                class="p-fluid"
            >
                <form @submit.prevent="submitProduct" class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Product Name -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Product Name <span class="text-rose-500">*</span>
                            </label>
                            <InputText
                                v-model="form.name"
                                placeholder="e.g. Special Chanachur 500g, Butter Toast, Cake Roll"
                                :class="{ 'p-invalid': form.errors.name }"
                                class="w-full text-sm"
                            />
                            <small v-if="form.errors.name" class="text-rose-500 text-xs">{{ form.errors.name }}</small>
                        </div>

                        <!-- SKU Code -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-semibold text-slate-700 uppercase">
                                    SKU / Product Code <span class="text-rose-500">*</span>
                                </label>
                                <button
                                    type="button"
                                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium"
                                    @click="generateSku"
                                >
                                    Auto Generate
                                </button>
                            </div>
                            <InputText
                                v-model="form.code"
                                placeholder="e.g. CHN-500G, TST-01"
                                :class="{ 'p-invalid': form.errors.code }"
                                class="w-full text-sm font-mono"
                            />
                            <small v-if="form.errors.code" class="text-rose-500 text-xs">{{ form.errors.code }}</small>
                        </div>

                        <!-- Category -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-semibold text-slate-700 uppercase">
                                    Category
                                </label>
                                <button
                                    type="button"
                                    @click="openCategoryModal"
                                    class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1 cursor-pointer transition-colors"
                                >
                                    <i class="pi pi-plus text-[10px]"></i> New Category
                                </button>
                            </div>
                            <Select
                                v-model="form.category_id"
                                :options="categories"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select Category"
                                class="w-full text-sm"
                            />
                            <small v-if="form.errors.category_id" class="text-rose-500 text-xs">{{ form.errors.category_id }}</small>
                        </div>

                        <!-- Packaging Type -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-semibold text-slate-700 uppercase">
                                    Packaging Type <span class="text-rose-500">*</span>
                                </label>
                                <button
                                    type="button"
                                    @click="openPackagingTypeModal"
                                    class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1 cursor-pointer transition-colors"
                                >
                                    <i class="pi pi-plus text-[10px]"></i> New
                                </button>
                            </div>
                            <Select
                                v-model="form.packaging_type"
                                :options="packagingTypes"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Select Packaging Type"
                                class="w-full text-sm"
                            />
                            <small v-if="form.errors.packaging_type" class="text-rose-500 text-xs">{{ form.errors.packaging_type }}</small>
                        </div>

                        <!-- Unit of Measurement -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-semibold text-slate-700 uppercase">
                                    Unit
                                </label>
                                <button
                                    type="button"
                                    @click="openUnitModal"
                                    class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1 cursor-pointer transition-colors"
                                >
                                    <i class="pi pi-plus text-[10px]"></i> New
                                </button>
                            </div>
                            <Select
                                v-model="form.unit_id"
                                :options="units"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select Unit (e.g. Pcs, Pkt, Ctn)"
                                showClear
                                class="w-full text-sm"
                            />
                            <small v-if="form.errors.unit_id" class="text-rose-500 text-xs">{{ form.errors.unit_id }}</small>
                        </div>

                        <!-- Packaging Specific Fields -->
                        <div v-if="form.packaging_type === 'CARTOON'">
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Units Per Carton (Master Box)
                            </label>
                            <InputText
                                v-model.number="form.units_per_cartoon"
                                type="number"
                                min="1"
                                placeholder="e.g. 24"
                                class="w-full text-sm"
                            />
                        </div>

                        <div v-if="['PACKET_5KG', 'CUSTOM'].includes(form.packaging_type)">
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Weight Per Unit (KG)
                            </label>
                            <InputText
                                v-model.number="form.weight_per_unit_kg"
                                type="number"
                                step="0.001"
                                min="0"
                                placeholder="e.g. 5.000"
                                class="w-full text-sm"
                            />
                        </div>

                        <div v-if="form.packaging_type === 'PACKET_12PCS'">
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Pieces Per Packet
                            </label>
                            <InputText
                                v-model.number="form.pieces_per_packet"
                                type="number"
                                min="1"
                                placeholder="e.g. 12"
                                class="w-full text-sm"
                            />
                        </div>

                        <!-- Cost Price -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Cost Price (Supplier Rate ৳) <span class="text-rose-500">*</span>
                            </label>
                            <CurrencyInput
                                v-model="form.cost_price"
                                placeholder="0.00"
                                class="w-full"
                            />
                            <small v-if="form.errors.cost_price" class="text-rose-500 text-xs">{{ form.errors.cost_price }}</small>
                        </div>

                        <!-- Wholesale Price -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Wholesale Price (Retailer Rate ৳) <span class="text-rose-500">*</span>
                            </label>
                            <CurrencyInput
                                v-model="form.wholesale_price"
                                placeholder="0.00"
                                class="w-full"
                            />
                            <small v-if="form.errors.wholesale_price" class="text-rose-500 text-xs">{{ form.errors.wholesale_price }}</small>
                        </div>

                        <!-- Min Alert Quantity -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Low Stock Alert Threshold
                            </label>
                            <InputText
                                v-model.number="form.min_alert_quantity"
                                type="number"
                                min="0"
                                placeholder="10"
                                class="w-full text-sm"
                            />
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center gap-3 pt-2">
                            <ToggleSwitch v-model="form.is_active" />
                            <span class="text-sm font-medium text-slate-700">Available for Active Sales & Dispatch</span>
                        </div>

                        <!-- Notes -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Product Specifications / Notes
                            </label>
                            <Textarea
                                v-model="form.notes"
                                rows="2"
                                placeholder="Storage instructions, shelf life, or supplier details..."
                                class="w-full text-sm"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                        <Button
                            label="Cancel"
                            severity="secondary"
                            text
                            @click="showProductModal = false"
                        />
                        <Button
                            :label="editingProduct ? 'Update Product' : 'Save Product'"
                            icon="pi pi-check"
                            type="submit"
                            :loading="form.processing"
                        />
                    </div>
                </form>
            </Dialog>

            <!-- Quick Add Category Modal -->
            <Dialog
                v-model:visible="showCategoryModal"
                header="Create New Product Category"
                modal
                @hide="resetCategoryForm"
                :style="{ width: '420px' }"
            >
                <form @submit.prevent="submitCategory" class="space-y-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Category Name <span class="text-rose-500">*</span>
                        </label>
                        <InputText
                            v-model="categoryForm.name"
                            placeholder="e.g. Chanachur, Toast, Biscuits, Cake"
                            class="w-full text-sm"
                            :class="{ 'p-invalid': categoryForm.errors.name }"
                        />
                        <small v-if="categoryForm.errors.name" class="text-rose-500 text-xs">{{ categoryForm.errors.name }}</small>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Description</label>
                        <Textarea
                            v-model="categoryForm.description"
                            rows="2"
                            placeholder="Optional category description..."
                            class="w-full text-sm"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <Button label="Cancel" severity="secondary" text @click="resetCategoryForm" />
                        <Button label="Save Category" icon="pi pi-check" type="submit" :loading="categoryForm.processing" />
                    </div>
                </form>
            </Dialog>

            <!-- Manage Categories Modal -->
            <Dialog
                v-model:visible="showManageCategoriesModal"
                header="Product Categories Directory"
                modal
                :style="{ width: '560px' }"
            >
                <div class="space-y-4 pt-2">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <p class="text-xs text-slate-500">
                            View all product categories registered in the system.
                        </p>
                        <Button
                            label="Add Category"
                            icon="pi pi-plus"
                            size="small"
                            severity="primary"
                            @click="openCategoryModal"
                        />
                    </div>

                    <div v-if="categories.length === 0" class="py-8 text-center text-slate-400">
                        <i class="pi pi-folder-open text-3xl mb-2 block"></i>
                        <p class="text-xs">No product categories created yet.</p>
                    </div>

                    <div v-else class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                        <div
                            v-for="cat in categories"
                            :key="cat.id"
                            class="py-3 flex items-center justify-between gap-3 hover:bg-slate-50/70 px-2 rounded-lg transition-colors"
                        >
                            <div>
                                <div class="font-semibold text-sm text-slate-800 flex items-center gap-2">
                                    <i class="pi pi-tag text-indigo-500 text-xs"></i>
                                    {{ cat.name }}
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5">
                                    Slug: <code class="font-mono text-[11px]">{{ cat.slug }}</code>
                                    <span v-if="cat.description" class="ml-2">| {{ cat.description }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <Button
                                    icon="pi pi-trash"
                                    severity="danger"
                                    text
                                    rounded
                                    size="small"
                                    title="Delete category"
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

            <!-- Packaging Type Create/Edit Modal -->
            <Dialog
                v-model:visible="showPackagingTypeModal"
                :header="editingPackagingType ? 'Edit Packaging Type' : 'Create Packaging Type'"
                modal
                @hide="resetPackagingTypeForm"
                :style="{ width: '450px' }"
            >
                <form @submit.prevent="submitPackagingType" class="space-y-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Packaging Name <span class="text-rose-500">*</span>
                        </label>
                        <InputText
                            v-model="packagingTypeForm.name"
                            placeholder="e.g. Master Box 48pcs, 10kg Sack"
                            class="w-full text-sm"
                            :class="{ 'p-invalid': packagingTypeForm.errors.name }"
                        />
                        <small v-if="packagingTypeForm.errors.name" class="text-rose-500 text-xs">{{ packagingTypeForm.errors.name }}</small>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Code / Key</label>
                            <InputText
                                v-model="packagingTypeForm.code"
                                placeholder="e.g. BOX_48"
                                class="w-full text-sm font-mono uppercase"
                                :class="{ 'p-invalid': packagingTypeForm.errors.code }"
                            />
                            <small v-if="packagingTypeForm.errors.code" class="text-rose-500 text-xs">{{ packagingTypeForm.errors.code }}</small>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Short Label</label>
                            <InputText
                                v-model="packagingTypeForm.short_label"
                                placeholder="e.g. 48 Box"
                                class="w-full text-sm"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Description</label>
                        <Textarea
                            v-model="packagingTypeForm.description"
                            rows="2"
                            placeholder="Optional notes regarding carton/packet structure..."
                            class="w-full text-sm"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <Button label="Cancel" severity="secondary" text @click="resetPackagingTypeForm" />
                        <Button :label="editingPackagingType ? 'Update' : 'Save Packaging Type'" icon="pi pi-check" type="submit" :loading="packagingTypeForm.processing" />
                    </div>
                </form>
            </Dialog>

            <!-- Manage Packaging Types Modal -->
            <Dialog
                v-model:visible="showManagePackagingTypesModal"
                header="Packaging Types Directory"
                modal
                :style="{ width: '580px' }"
            >
                <div class="space-y-4 pt-2">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <p class="text-xs text-slate-500">
                            Configure available packaging types and bundle specifications.
                        </p>
                        <Button
                            label="Add Packaging Type"
                            icon="pi pi-plus"
                            size="small"
                            severity="primary"
                            @click="openPackagingTypeModal"
                        />
                    </div>

                    <div v-if="(!allPackagingTypes || allPackagingTypes.length === 0)" class="py-8 text-center text-slate-400">
                        <i class="pi pi-box text-3xl mb-2 block"></i>
                        <p class="text-xs">No packaging types registered yet.</p>
                    </div>

                    <div v-else class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                        <div
                            v-for="pt in allPackagingTypes"
                            :key="pt.id"
                            class="py-3 flex items-center justify-between gap-3 hover:bg-slate-50/70 px-2 rounded-lg transition-colors"
                        >
                            <div>
                                <div class="font-semibold text-sm text-slate-800 flex items-center gap-2">
                                    <i class="pi pi-box text-emerald-600 text-xs"></i>
                                    {{ pt.name }}
                                    <span v-if="pt.short_label" class="text-xs px-2 py-0.5 rounded bg-slate-100 font-mono text-slate-600">
                                        {{ pt.short_label }}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5 font-mono">
                                    Code: <code>{{ pt.code }}</code>
                                    <span v-if="pt.description" class="ml-2 font-sans">| {{ pt.description }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <Button
                                    icon="pi pi-pencil"
                                    severity="secondary"
                                    text
                                    rounded
                                    size="small"
                                    title="Edit packaging type"
                                    @click="openEditPackagingTypeModal(pt)"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    severity="danger"
                                    text
                                    rounded
                                    size="small"
                                    title="Delete packaging type"
                                    @click="deletePackagingType(pt)"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2 border-t border-slate-100">
                        <Button label="Close" severity="secondary" text @click="showManagePackagingTypesModal = false" />
                    </div>
                </div>
            </Dialog>

            <!-- Unit Create/Edit Modal -->
            <Dialog
                v-model:visible="showUnitModal"
                :header="editingUnit ? 'Edit Unit' : 'Create Measurement Unit'"
                modal
                @hide="resetUnitForm"
                :style="{ width: '420px' }"
            >
                <form @submit.prevent="submitUnit" class="space-y-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Unit Name <span class="text-rose-500">*</span>
                        </label>
                        <InputText
                            v-model="unitForm.name"
                            placeholder="e.g. Piece, Packet, Cartoon, Kilogram"
                            class="w-full text-sm"
                            :class="{ 'p-invalid': unitForm.errors.name }"
                        />
                        <small v-if="unitForm.errors.name" class="text-rose-500 text-xs">{{ unitForm.errors.name }}</small>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Short Code / Symbol</label>
                        <InputText
                            v-model="unitForm.short_code"
                            placeholder="e.g. pcs, pkt, ctn, kg"
                            class="w-full text-sm font-mono lowercase"
                            :class="{ 'p-invalid': unitForm.errors.short_code }"
                        />
                        <small v-if="unitForm.errors.short_code" class="text-rose-500 text-xs">{{ unitForm.errors.short_code }}</small>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Description</label>
                        <Textarea
                            v-model="unitForm.description"
                            rows="2"
                            placeholder="Optional notes regarding this unit..."
                            class="w-full text-sm"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <Button label="Cancel" severity="secondary" text @click="resetUnitForm" />
                        <Button :label="editingUnit ? 'Update' : 'Save Unit'" icon="pi pi-check" type="submit" :loading="unitForm.processing" />
                    </div>
                </form>
            </Dialog>

            <!-- Manage Units Modal -->
            <Dialog
                v-model:visible="showManageUnitsModal"
                header="Units of Measurement Directory"
                modal
                :style="{ width: '540px' }"
            >
                <div class="space-y-4 pt-2">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <p class="text-xs text-slate-500">
                            Configure standard measurement units for inventory goods.
                        </p>
                        <Button
                            label="Add Unit"
                            icon="pi pi-plus"
                            size="small"
                            severity="primary"
                            @click="openUnitModal"
                        />
                    </div>

                    <div v-if="(!allUnits || allUnits.length === 0)" class="py-8 text-center text-slate-400">
                        <i class="pi pi-sliders-v text-3xl mb-2 block"></i>
                        <p class="text-xs">No measurement units registered yet.</p>
                    </div>

                    <div v-else class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                        <div
                            v-for="unit in allUnits"
                            :key="unit.id"
                            class="py-3 flex items-center justify-between gap-3 hover:bg-slate-50/70 px-2 rounded-lg transition-colors"
                        >
                            <div>
                                <div class="font-semibold text-sm text-slate-800 flex items-center gap-2">
                                    <i class="pi pi-circle-fill text-amber-500 text-[8px]"></i>
                                    {{ unit.name }}
                                    <span class="text-xs px-2 py-0.5 rounded bg-slate-100 font-mono text-slate-600">
                                        {{ unit.short_code }}
                                    </span>
                                </div>
                                <div v-if="unit.description" class="text-xs text-slate-400 mt-0.5">
                                    {{ unit.description }}
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <Button
                                    icon="pi pi-pencil"
                                    severity="secondary"
                                    text
                                    rounded
                                    size="small"
                                    title="Edit unit"
                                    @click="openEditUnitModal(unit)"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    severity="danger"
                                    text
                                    rounded
                                    size="small"
                                    title="Delete unit"
                                    @click="deleteUnit(unit)"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2 border-t border-slate-100">
                        <Button label="Close" severity="secondary" text @click="showManageUnitsModal = false" />
                    </div>
                </div>
            </Dialog>

            <!-- Confirm Delete Modal -->
            <ConfirmModal
                v-model:visible="showDeleteModal"
                title="Delete Product"
                :message="`Are you sure you want to delete '${productToDelete?.name}'? This action cannot be undone if no stock movements exist.`"
                confirmLabel="Delete Product"
                severity="danger"
                @confirm="deleteProduct"
                @hide="() => { showDeleteModal = false; productToDelete = null; }"
            />
        </div>
    </AppLayout>
</template>
