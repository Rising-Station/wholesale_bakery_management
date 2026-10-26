<script setup>
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppDataTable from '@/Components/AppDataTable.vue';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';

const props = defineProps({
    products: {
        type: Object,
        default: () => ({ data: [], total: 0 }),
    },
    categories: {
        type: Array,
        default: () => [],
    },
    metrics: {
        type: Object,
        default: () => ({
            low_stock_count: 0,
            total_cost_valuation: 0,
            total_wholesale_valuation: 0,
            total_quantity: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ category_id: null, search: '', low_stock: false }),
    },
});

const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category_id || null);
const lowStockOnly = ref(Boolean(props.filters.low_stock));

const filterData = () => {
    router.get(
        '/stock',
        {
            search: searchQuery.value || undefined,
            category_id: selectedCategory.value || undefined,
            low_stock: lowStockOnly.value ? true : undefined,
        },
        { preserveState: true, replace: true }
    );
};

const toggleLowStockFilter = () => {
    lowStockOnly.value = !lowStockOnly.value;
    filterData();
};
</script>

<template>
    <AppLayout>
        <Head title="Central Warehouse Stock Engine" />

        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                        <i class="pi pi-box text-emerald-600"></i>
                        Mominul Bekari - Central Warehouse Stock
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Real-time central inventory ledger, low-stock threshold alerts, valuation totals, and immutable audit trail.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/stock/movements">
                        <Button
                            label="Stock Movement Ledger"
                            icon="pi pi-history"
                            severity="secondary"
                            outlined
                        />
                    </Link>
                    <Link href="/stock-adjustments">
                        <Button
                            label="Physical Count Audit"
                            icon="pi pi-sliders-h"
                            severity="primary"
                        />
                    </Link>
                </div>
            </div>

            <!-- Inventory Metric KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total In-Stock Units</span>
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                            <i class="pi pi-database text-lg"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-800 mt-2">
                        {{ Number(metrics.total_quantity).toLocaleString() }}
                    </div>
                    <div class="text-xs text-slate-400 mt-1">Available across central depot</div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Cost Valuation</span>
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                            <i class="pi pi-tag text-lg"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-800 mt-2">
                        ৳ {{ Number(metrics.total_cost_valuation).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-xs text-slate-400 mt-1">Supplier purchase cost basis</div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Wholesale Valuation</span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                            <i class="pi pi-wallet text-lg"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-emerald-700 mt-2">
                        ৳ {{ Number(metrics.total_wholesale_valuation).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-xs text-slate-400 mt-1">Retailer selling price potential</div>
                </div>

                <div
                    :class="[
                        'p-5 rounded-2xl shadow-sm border cursor-pointer transition',
                        lowStockOnly
                            ? 'bg-rose-50 border-rose-300 ring-2 ring-rose-400'
                            : 'bg-white border-slate-200 hover:border-rose-200'
                    ]"
                    @click="toggleLowStockFilter"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Low Stock Alerts</span>
                        <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center font-bold">
                            <i class="pi pi-exclamation-triangle text-lg"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-rose-600 mt-2">
                        {{ metrics.low_stock_count }}
                    </div>
                    <div class="text-xs text-rose-500 font-medium mt-1">
                        {{ lowStockOnly ? 'Filtered: Showing low stock' : 'Click to filter low stock items' }}
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[240px]">
                    <div class="relative">
                        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <InputText
                            v-model="searchQuery"
                            placeholder="Filter by product name or SKU..."
                            class="w-full pl-9 pr-4 py-2 text-sm"
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

                <Button
                    :label="lowStockOnly ? 'Clear Low-Stock Filter' : 'Show Low-Stock Only'"
                    :icon="lowStockOnly ? 'pi pi-times' : 'pi pi-exclamation-circle'"
                    :severity="lowStockOnly ? 'danger' : 'secondary'"
                    :outlined="!lowStockOnly"
                    @click="toggleLowStockFilter"
                />

                <Button
                    icon="pi pi-filter"
                    label="Search"
                    severity="primary"
                    outlined
                    @click="filterData"
                />
            </div>

            <!-- Stock Table -->
            <AppDataTable
                :items="products.data || []"
                :total="products.total || 0"
                :perPage="products.per_page || 25"
                :currentPage="products.current_page || 1"
                tableTitle="Central Warehouse Inventory List"
            >
                <Column field="code" header="SKU / Code" style="width: 130px">
                    <template #body="{ data }">
                        <span class="font-mono text-xs font-semibold px-2 py-1 bg-slate-100 border border-slate-200 rounded text-slate-700">
                            {{ data.code }}
                        </span>
                    </template>
                </Column>

                <Column field="name" header="Product Name">
                    <template #body="{ data }">
                        <div>
                            <span class="font-bold text-slate-800">{{ data.name }}</span>
                            <div class="text-xs text-slate-500">{{ data.category?.name || 'Uncategorized' }}</div>
                        </div>
                    </template>
                </Column>

                <Column field="packaging_type" header="Packaging" style="width: 140px">
                    <template #body="{ data }">
                        <span class="text-xs font-medium text-slate-600 bg-slate-100 px-2 py-1 rounded">
                            {{ data.packaging_type }}
                        </span>
                    </template>
                </Column>

                <Column field="warehouse_stock" header="Warehouse Stock" style="width: 160px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span
                                :class="[
                                    'text-base font-black',
                                    Number(data.warehouse_stock) <= Number(data.min_alert_quantity)
                                        ? 'text-rose-600'
                                        : 'text-slate-900'
                                ]"
                            >
                                {{ Number(data.warehouse_stock).toLocaleString() }}
                            </span>
                            <Tag
                                v-if="Number(data.warehouse_stock) <= Number(data.min_alert_quantity)"
                                value="LOW"
                                severity="danger"
                                rounded
                                class="text-[10px]"
                            />
                        </div>
                        <div class="text-[11px] text-slate-400">
                            Alert threshold: &le; {{ data.min_alert_quantity }}
                        </div>
                    </template>
                </Column>

                <Column field="cost_price" header="Cost Rate" style="width: 130px">
                    <template #body="{ data }">
                        <span class="text-xs font-medium text-slate-600">
                            ৳ {{ Number(data.cost_price).toFixed(2) }}
                        </span>
                    </template>
                </Column>

                <Column field="wholesale_price" header="Wholesale Rate" style="width: 140px">
                    <template #body="{ data }">
                        <span class="text-xs font-bold text-slate-700">
                            ৳ {{ Number(data.wholesale_price).toFixed(2) }}
                        </span>
                    </template>
                </Column>

                <Column header="Stock Valuation" style="width: 160px">
                    <template #body="{ data }">
                        <div class="text-sm font-bold text-emerald-700">
                            ৳ {{ (Number(data.warehouse_stock) * Number(data.wholesale_price)).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                        </div>
                        <div class="text-[11px] text-slate-400">
                            Cost: ৳ {{ (Number(data.warehouse_stock) * Number(data.cost_price)).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                        </div>
                    </template>
                </Column>

                <Column field="is_active" header="Status" style="width: 90px">
                    <template #body="{ data }">
                        <Tag
                            :value="data.is_active ? 'Active' : 'Inactive'"
                            :severity="data.is_active ? 'success' : 'secondary'"
                            rounded
                        />
                    </template>
                </Column>
            </AppDataTable>
        </div>
    </AppLayout>
</template>
