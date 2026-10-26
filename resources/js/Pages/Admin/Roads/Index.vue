<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    roads: {
        type: Array,
        default: () => [],
    },
    drivers: {
        type: Array,
        default: () => [],
    },
});

const showModal = ref(false);
const editingRoad = ref(null);
const showDeleteModal = ref(false);
const roadToDelete = ref(null);

const form = useForm({
    name: '',
    code: '',
    vehicle_number: '',
    driver_name: '',
    driver_phone: '',
    driver_id: null,
    is_active: true,
});

const openCreateModal = () => {
    editingRoad.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (road) => {
    editingRoad.value = road;
    form.clearErrors();
    form.name = road.name;
    form.code = road.code;
    form.vehicle_number = road.vehicle_number || '';
    form.driver_name = road.driver_name || '';
    form.driver_phone = road.driver_phone || '';
    form.driver_id = road.driver_id;
    form.is_active = road.is_active;
    showModal.value = true;
};

const onDriverSelected = (event) => {
    const driverId = event.value;
    const selectedDriver = props.drivers.find((d) => d.id === driverId);
    if (selectedDriver) {
        form.driver_name = selectedDriver.name;
        form.driver_phone = selectedDriver.phone || '';
    }
};

const resetRoadForm = () => {
    form.reset();
    form.clearErrors();
    editingRoad.value = null;
    showModal.value = false;
};

const submitRoad = () => {
    if (editingRoad.value) {
        form.put(`/roads/${editingRoad.value.id}`, {
            onSuccess: () => {
                resetRoadForm();
            },
        });
    } else {
        form.post('/roads', {
            onSuccess: () => {
                resetRoadForm();
            },
        });
    }
};

const confirmDelete = (road) => {
    roadToDelete.value = road;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (roadToDelete.value) {
        form.delete(`/roads/${roadToDelete.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false;
                roadToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <Head title="Road Routes - Mominul Bekari" />

    <AppLayout title="Road Management (Routes)">
        <div class="space-y-6">
            <!-- Header bar -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Dedicated Delivery Routes (Road A, B, C)</h2>
                    <p class="text-xs text-slate-500">Mominul Bekari dispatches 1 vehicle per road at a time to serve registered retail shop clusters</p>
                </div>
                <Button
                    type="button"
                    icon="pi pi-plus"
                    label="Add New Road Route"
                    @click="openCreateModal"
                    class="!bg-amber-500 hover:!bg-amber-600 !text-slate-950 font-bold !border-none !rounded-xl text-xs"
                />
            </div>

            <!-- Road Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div
                    v-for="road in roads"
                    :key="road.id"
                    class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col justify-between hover:border-amber-400/80 transition group"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1 rounded-xl bg-slate-900 text-amber-400 font-black text-sm">
                                {{ road.code }}
                            </span>
                            <Tag
                                :value="road.is_active ? 'Active Route' : 'Inactive'"
                                :severity="road.is_active ? 'success' : 'secondary'"
                                class="text-[10px] font-bold"
                            />
                        </div>

                        <div class="mt-4">
                            <h3 class="text-base font-bold text-slate-900 group-hover:text-amber-600 transition">
                                {{ road.name }}
                            </h3>
                            <div class="mt-4 space-y-2 text-xs text-slate-600">
                                <div class="flex items-center gap-2 p-2 rounded-xl bg-slate-50 border border-slate-100">
                                    <i class="pi pi-truck text-slate-400"></i>
                                    <span class="font-semibold text-slate-800">{{ road.vehicle_number || 'No Vehicle Assigned' }}</span>
                                </div>
                                <div class="flex items-center gap-2 p-2 rounded-xl bg-slate-50 border border-slate-100">
                                    <i class="pi pi-user text-slate-400"></i>
                                    <span>Driver: <strong>{{ road.driver_name || 'Unassigned' }}</strong></span>
                                    <span v-if="road.driver_phone" class="text-slate-400">({{ road.driver_phone }})</span>
                                </div>
                                <div class="flex items-center justify-between p-2 rounded-xl bg-amber-50/50 border border-amber-100/60 text-amber-900">
                                    <span class="font-medium">Registered Shops:</span>
                                    <span class="font-bold text-sm">{{ road.total_shops || 0 }} Shops</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <a
                            :href="`/retailers?road_id=${road.id}`"
                            class="text-xs font-semibold text-amber-600 hover:text-amber-700 flex items-center gap-1"
                        >
                            <span>View Shops</span>
                            <i class="pi pi-arrow-right text-[10px]"></i>
                        </a>

                        <div class="flex items-center gap-1">
                            <Button
                                type="button"
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                @click="openEditModal(road)"
                                title="Edit Road"
                            />
                            <Button
                                type="button"
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                size="small"
                                @click="confirmDelete(road)"
                                title="Delete Road"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create / Edit Dialog -->
            <Dialog
                v-model:visible="showModal"
                :modal="true"
                @hide="resetRoadForm"
                :header="editingRoad ? `Edit Road: ${editingRoad.name}` : 'Add New Road Route'"
                class="w-full max-w-lg"
            >
                <form @submit.prevent="submitRoad" class="space-y-4 py-2">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Road Name *</label>
                            <InputText v-model="form.name" placeholder="e.g. Road A (Main Bazaar)" class="w-full !rounded-xl" />
                            <span v-if="form.errors.name" class="text-xs text-rose-600 font-medium mt-1 block">
                                {{ form.errors.name }}
                            </span>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Code *</label>
                            <InputText v-model="form.code" placeholder="RD-A" class="w-full !rounded-xl uppercase font-bold" />
                            <span v-if="form.errors.code" class="text-xs text-rose-600 font-medium mt-1 block">
                                {{ form.errors.code }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Default Assigned Vehicle</label>
                        <InputText v-model="form.vehicle_number" placeholder="e.g. DHK-METRO-11-2034" class="w-full !rounded-xl" />
                        <span v-if="form.errors.vehicle_number" class="text-xs text-rose-600 font-medium mt-1 block">
                            {{ form.errors.vehicle_number }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Select Driver Account</label>
                            <Select
                                v-model="form.driver_id"
                                :options="drivers"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Choose Driver"
                                :showClear="true"
                                @change="onDriverSelected"
                                class="w-full !rounded-xl"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Driver Phone</label>
                            <InputText v-model="form.driver_phone" placeholder="017xxxxxxxx" class="w-full !rounded-xl" />
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <ToggleSwitch v-model="form.is_active" />
                        <label class="text-xs font-bold text-slate-700">Active Delivery Route</label>
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
                            :label="editingRoad ? 'Update Road' : 'Create Road'"
                            :loading="form.processing"
                            class="!bg-amber-500 hover:!bg-amber-600 !text-slate-950 font-bold !border-none !rounded-xl text-xs"
                        />
                    </div>
                </form>
            </Dialog>

            <!-- Confirm Delete Modal -->
            <ConfirmModal
                v-model:visible="showDeleteModal"
                title="Delete Road"
                :message="`Are you sure you want to delete road route '${roadToDelete?.name}'? Make sure no retail shops are attached to this road.`"
                :loading="form.processing"
                @confirm="executeDelete"
            />
        </div>
    </AppLayout>
</template>
