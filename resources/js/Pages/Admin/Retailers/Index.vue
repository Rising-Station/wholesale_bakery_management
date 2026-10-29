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
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import CurrencyInput from '@/Components/CurrencyInput.vue';

const props = defineProps({
    retailers: {
        type: Object,
        default: () => ({ data: [], total: 0 }),
    },
    roads: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ road_id: null, search: '' }),
    },
});

const searchQuery = ref(props.filters.search || '');
const selectedRoadFilter = ref(props.filters.road_id || null);

const showModal = ref(false);
const editingShop = ref(null);
const showDeleteModal = ref(false);
const shopToDelete = ref(null);

const form = useForm({
    road_id: null,
    shop_name: '',
    owner_name: '',
    phone: '',
    alt_phone: '',
    address: '',
    credit_limit: 0,
    opening_due: 0,
    is_active: true,
});

const filterData = () => {
    router.get(
        '/retailers',
        {
            search: searchQuery.value || undefined,
            road_id: selectedRoadFilter.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const filterByRoad = (roadId) => {
    selectedRoadFilter.value = roadId;
    filterData();
};

const openCreateModal = () => {
    editingShop.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    if (selectedRoadFilter.value) {
        form.road_id = selectedRoadFilter.value;
    } else if (props.roads.length > 0) {
        form.road_id = props.roads[0].id;
    }
    showModal.value = true;
};

const openEditModal = (shop) => {
    editingShop.value = shop;
    form.clearErrors();
    form.road_id = shop.road_id;
    form.shop_name = shop.shop_name;
    form.owner_name = shop.owner_name;
    form.phone = shop.phone;
    form.alt_phone = shop.alt_phone || '';
    form.address = shop.address || '';
    form.credit_limit = Number(shop.credit_limit || 0);
    form.opening_due = Number(shop.opening_due || 0);
    form.is_active = shop.is_active;
    showModal.value = true;
};

const resetRetailerForm = () => {
    form.reset();
    form.clearErrors();
    editingShop.value = null;
    showModal.value = false;
};

const submitShop = () => {
    if (editingShop.value) {
        form.put(`/retailers/${editingShop.value.id}`, {
            onSuccess: () => {
                resetRetailerForm();
            },
        });
    } else {
        form.post('/retailers', {
            onSuccess: () => {
                resetRetailerForm();
            },
        });
    }
};

const confirmDelete = (shop) => {
    shopToDelete.value = shop;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (shopToDelete.value) {
        form.delete(`/retailers/${shopToDelete.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false;
                shopToDelete.value = null;
            },
        });
    }
};

const formatMoney = (val) => {
    return '৳ ' + Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <Head title="Retailer Shops - Mominul Bekari" />

    <AppLayout title="Retailer Directory (Tier 3)">
        <div class="space-y-6">
            <!-- Road Filter Tabs -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                <button
                    @click="filterByRoad(null)"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap',
                        selectedRoadFilter === null
                            ? 'bg-slate-900 text-white shadow-md'
                            : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200',
                    ]"
                >
                    All Roads
                </button>
                <button
                    v-for="road in roads"
                    :key="road.id"
                    @click="filterByRoad(road.id)"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap',
                        selectedRoadFilter === road.id
                            ? 'bg-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20'
                            : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200',
                    ]"
                >
                    <span class="px-1.5 py-0.5 rounded bg-slate-900/10 text-[10px] font-black">{{ road.code }}</span>
                    <span>{{ road.name }}</span>
                </button>
            </div>

            <!-- Controls bar -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                    <div class="relative w-full sm:w-80">
                        <i class="pi pi-search absolute left-3 top-3 text-slate-400 text-xs"></i>
                        <InputText
                            v-model="searchQuery"
                            placeholder="Search shop, owner, phone, address..."
                            @keydown.enter="filterData"
                            class="!pl-9 !text-xs !rounded-xl w-full"
                        />
                    </div>
                    <Button
                        type="button"
                        label="Search"
                        severity="secondary"
                        size="small"
                        @click="filterData"
                        class="!rounded-xl text-xs w-full sm:w-auto"
                    />
                </div>

                <Button
                    type="button"
                    icon="pi pi-plus"
                    label="Register Retailer Shop"
                    @click="openCreateModal"
                    class="!bg-amber-500 hover:!bg-amber-600 !text-slate-950 font-bold !border-none !rounded-xl text-xs w-full sm:w-auto"
                />
            </div>

            <!-- Retailers DataTable -->
            <AppDataTable :value="retailers.data || []" title="Registered Retailer Shops">
                <Column header="Shop & Address">
                    <template #body="{ data }">
                        <div>
                            <Link
                                :href="`/retailers/${data.id}`"
                                class="font-bold text-slate-900 text-sm hover:text-amber-600 transition"
                            >
                                {{ data.shop_name }}
                            </Link>
                            <div class="text-xs text-slate-500 line-clamp-1 mt-0.5">
                                {{ data.address || 'Address not recorded' }}
                            </div>
                        </div>
                    </template>
                </Column>

                <Column field="road.name" header="Road Route">
                    <template #body="{ data }">
                        <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-800 text-xs font-bold border border-slate-200">
                            {{ data.road?.code || 'RD' }} - {{ data.road?.name }}
                        </span>
                    </template>
                </Column>

                <Column header="Proprietor / Contact">
                    <template #body="{ data }">
                        <div>
                            <div class="text-xs font-semibold text-slate-800">{{ data.owner_name }}</div>
                            <div class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                                <i class="pi pi-phone text-[10px]"></i>
                                <span>{{ data.phone }}</span>
                            </div>
                        </div>
                    </template>
                </Column>

                <Column field="current_due" header="Current Due">
                    <template #body="{ data }">
                        <div
                            :class="[
                                'font-black text-sm',
                                Number(data.current_due) > 0 ? 'text-rose-600' : 'text-emerald-600',
                            ]"
                        >
                            {{ formatMoney(data.current_due) }}
                        </div>
                        <div v-if="Number(data.credit_limit) > 0" class="text-[10px] text-slate-400">
                            Limit: {{ formatMoney(data.credit_limit) }}
                        </div>
                    </template>
                </Column>

                <Column field="is_active" header="Status">
                    <template #body="{ data }">
                        <Tag
                            :value="data.is_active ? 'Active' : 'Inactive'"
                            :severity="data.is_active ? 'success' : 'secondary'"
                            class="text-[10px] font-bold"
                        />
                    </template>
                </Column>

                <Column header="Actions" :exportable="false" style="min-width: 8rem; text-align: right;">
                    <template #body="{ data }">
                        <div class="flex items-center justify-end gap-1">
                            <Link
                                :href="`/retailers/${data.id}`"
                                class="p-2 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-slate-100 transition"
                                title="View Ledger"
                            >
                                <i class="pi pi-eye text-xs"></i>
                            </Link>
                            <Button
                                type="button"
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                @click="openEditModal(data)"
                                title="Edit Shop"
                            />
                            <Button
                                type="button"
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                size="small"
                                @click="confirmDelete(data)"
                                title="Delete Shop"
                            />
                        </div>
                    </template>
                </Column>
            </AppDataTable>

            <!-- Create / Edit Dialog -->
            <Dialog
                v-model:visible="showModal"
                :modal="true"
                @hide="resetRetailerForm"
                :header="editingShop ? `Edit Retailer: ${editingShop.shop_name}` : 'Register Retailer Shop'"
                class="w-full max-w-lg"
            >
                <form @submit.prevent="submitShop" class="space-y-4 py-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Assigned Delivery Road *</label>
                        <Select
                            v-model="form.road_id"
                            :options="roads"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select Delivery Road (A, B, C)"
                            filter
                            class="w-full !rounded-xl"
                        />
                        <span v-if="form.errors.road_id" class="text-xs text-rose-600 font-medium mt-1 block">
                            {{ form.errors.road_id }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Shop Name *</label>
                            <InputText v-model="form.shop_name" placeholder="e.g. Al-Madina Store" class="w-full !rounded-xl" />
                            <span v-if="form.errors.shop_name" class="text-xs text-rose-600 font-medium mt-1 block">
                                {{ form.errors.shop_name }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Proprietor Name *</label>
                            <InputText v-model="form.owner_name" placeholder="e.g. Md. Shahidul Islam" class="w-full !rounded-xl" />
                            <span v-if="form.errors.owner_name" class="text-xs text-rose-600 font-medium mt-1 block">
                                {{ form.errors.owner_name }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Contact Phone *</label>
                            <InputText v-model="form.phone" placeholder="017xxxxxxxx" class="w-full !rounded-xl" />
                            <span v-if="form.errors.phone" class="text-xs text-rose-600 font-medium mt-1 block">
                                {{ form.errors.phone }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Alternative Phone</label>
                            <InputText v-model="form.alt_phone" placeholder="Optional phone" class="w-full !rounded-xl" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Physical Address / Landmark</label>
                        <Textarea v-model="form.address" rows="2" placeholder="Market, Shop #, Road landmark..." class="w-full !rounded-xl text-xs" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Credit Limit</label>
                            <CurrencyInput v-model="form.credit_limit" placeholder="0.00" />
                        </div>

                        <div v-if="!editingShop">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Opening Due Balance</label>
                            <CurrencyInput v-model="form.opening_due" placeholder="0.00" />
                            <span class="text-[10px] text-slate-400">Previous balance before ERP migration</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <ToggleSwitch v-model="form.is_active" />
                        <label class="text-xs font-bold text-slate-700">Active Retailer Status</label>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <Button
                            type="button"
                            label="Cancel"
                            severity="secondary"
                            outlined
                            size="small"
                            @click="showModal = false"
                            class="!rounded-xl text-xs"
                        />
                        <Button
                            type="submit"
                            :label="editingShop ? 'Update Retailer' : 'Register Retailer'"
                            :loading="form.processing"
                            class="!bg-amber-500 hover:!bg-amber-600 !text-slate-950 font-bold !border-none !rounded-xl text-xs"
                        />
                    </div>
                </form>
            </Dialog>

            <!-- Confirm Delete Modal -->
            <ConfirmModal
                v-model:visible="showDeleteModal"
                title="Delete Retailer Shop"
                :message="`Are you sure you want to remove retailer shop '${shopToDelete?.shop_name}'?`"
                :loading="form.processing"
                @confirm="executeDelete"
            />
        </div>
    </AppLayout>
</template>
