<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    roles: {
        type: Array,
        default: () => [],
    },
    permissionsByGroup: {
        type: Array,
        default: () => [],
    },
});

const showModal = ref(false);
const editingRole = ref(null);
const showDeleteModal = ref(false);
const roleToDelete = ref(null);

const form = useForm({
    name: '',
    permissions: [],
});

const openCreateModal = () => {
    editingRole.value = null;
    form.reset();
    form.clearErrors();
    form.permissions = [];
    showModal.value = true;
};

const openEditModal = (role) => {
    editingRole.value = role;
    form.clearErrors();
    form.name = role.name;
    form.permissions = [...role.permissions];
    showModal.value = true;
};

const toggleGroup = (groupPermissions) => {
    const permNames = groupPermissions.map((p) => p.name);
    const allSelected = permNames.every((p) => form.permissions.includes(p));

    if (allSelected) {
        form.permissions = form.permissions.filter((p) => !permNames.includes(p));
    } else {
        const set = new Set([...form.permissions, ...permNames]);
        form.permissions = Array.from(set);
    }
};

const isGroupSelected = (groupPermissions) => {
    const permNames = groupPermissions.map((p) => p.name);
    return permNames.every((p) => form.permissions.includes(p));
};

const resetRoleForm = () => {
    form.reset();
    form.clearErrors();
    form.permissions = [];
    editingRole.value = null;
    showModal.value = false;
};

const submitRole = () => {
    if (editingRole.value) {
        form.put(`/roles/${editingRole.value.id}`, {
            onSuccess: () => {
                resetRoleForm();
            },
        });
    } else {
        form.post('/roles', {
            onSuccess: () => {
                resetRoleForm();
            },
        });
    }
};

const confirmDelete = (role) => {
    roleToDelete.value = role;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (roleToDelete.value) {
        form.delete(`/roles/${roleToDelete.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false;
                roleToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <Head title="Roles & Permissions - Mominul Bekari" />

    <AppLayout title="Roles & Access Control">
        <div class="space-y-6">
            <!-- Header bar -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">User Roles & Access Permissions</h2>
                    <p class="text-xs text-slate-500">Configure operational privileges across warehouse, accounting, drivers, and management</p>
                </div>
                <Button
                    type="button"
                    icon="pi pi-plus"
                    label="Create New Role"
                    @click="openCreateModal"
                    class="!bg-amber-500 hover:!bg-amber-600 !text-slate-950 font-bold !border-none !rounded-xl text-xs"
                />
            </div>

            <!-- Role Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="role in roles"
                    :key="role.id"
                    class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col justify-between hover:border-amber-400/80 transition"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-base font-black text-slate-900">{{ role.name }}</span>
                            <Tag
                                :value="`${role.users_count} Users`"
                                severity="info"
                                class="text-[10px] font-bold"
                            />
                        </div>

                        <div class="mt-3">
                            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-2">
                                Assigned Privileges ({{ role.permissions.length }})
                            </span>
                            <div class="flex flex-wrap gap-1.5 max-h-32 overflow-y-auto p-1 bg-slate-50 rounded-xl border border-slate-100">
                                <span
                                    v-for="(perm, pIdx) in role.permissions.slice(0, 10)"
                                    :key="pIdx"
                                    class="text-[10px] px-2 py-0.5 rounded-md bg-white border border-slate-200 text-slate-700 font-medium"
                                >
                                    {{ perm }}
                                </span>
                                <span
                                    v-if="role.permissions.length > 10"
                                    class="text-[10px] px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 font-bold"
                                >
                                    +{{ role.permissions.length - 10 }} more
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <Button
                            type="button"
                            icon="pi pi-pencil"
                            label="Edit Permissions"
                            severity="secondary"
                            outlined
                            size="small"
                            :disabled="role.name === 'SuperAdmin'"
                            @click="openEditModal(role)"
                            class="!rounded-xl text-xs"
                        />
                        <Button
                            v-if="!role.is_protected"
                            type="button"
                            icon="pi pi-trash"
                            severity="danger"
                            outlined
                            size="small"
                            @click="confirmDelete(role)"
                            class="!rounded-xl text-xs"
                        />
                    </div>
                </div>
            </div>

            <!-- Role Modal with Permission Matrix -->
            <Dialog
                v-model:visible="showModal"
                :modal="true"
                @hide="resetRoleForm"
                :header="editingRole ? `Edit Role: ${editingRole.name}` : 'Create New Role'"
                class="w-full max-w-3xl"
            >
                <form @submit.prevent="submitRole" class="space-y-5 py-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Role Name *</label>
                        <InputText
                            v-model="form.name"
                            placeholder="e.g. AreaSupervisor"
                            class="w-full"
                            :disabled="editingRole && editingRole.name === 'SuperAdmin'"
                        />
                        <span v-if="form.errors.name" class="text-xs text-rose-600 font-medium mt-1 block">
                            {{ form.errors.name }}
                        </span>
                    </div>

                    <!-- Permission Matrix -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Permission Matrix (Select Modules)
                            </label>
                            <span class="text-xs font-semibold text-amber-600">
                                {{ form.permissions.length }} Selected
                            </span>
                        </div>

                        <div class="max-h-96 overflow-y-auto space-y-4 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                            <div
                                v-for="(groupObj, gIdx) in permissionsByGroup"
                                :key="gIdx"
                                class="bg-white p-3 rounded-xl border border-slate-200"
                            >
                                <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100">
                                    <span class="text-xs font-black text-slate-900 uppercase tracking-wide">
                                        {{ groupObj.group }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="toggleGroup(groupObj.permissions)"
                                        class="text-[11px] font-semibold text-amber-600 hover:text-amber-700"
                                    >
                                        {{ isGroupSelected(groupObj.permissions) ? 'Deselect All' : 'Select All' }}
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div
                                        v-for="perm in groupObj.permissions"
                                        :key="perm.id"
                                        class="flex items-center gap-2"
                                    >
                                        <Checkbox
                                            v-model="form.permissions"
                                            :inputId="`perm-${perm.id}`"
                                            :value="perm.name"
                                        />
                                        <label
                                            :for="`perm-${perm.id}`"
                                            class="text-xs text-slate-700 cursor-pointer font-medium select-none"
                                        >
                                            {{ perm.label }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
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
                            :label="editingRole ? 'Update Role' : 'Save Role'"
                            :loading="form.processing"
                            class="!bg-amber-500 hover:!bg-amber-600 !text-slate-950 font-bold !border-none !rounded-xl text-xs"
                        />
                    </div>
                </form>
            </Dialog>

            <!-- Delete Confirmation Modal -->
            <ConfirmModal
                v-model:visible="showDeleteModal"
                title="Delete Role"
                :message="`Are you sure you want to delete role '${roleToDelete?.name}'? This action cannot be reversed.`"
                :loading="form.processing"
                @confirm="executeDelete"
            />
        </div>
    </AppLayout>
</template>
