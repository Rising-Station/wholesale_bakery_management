<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppDataTable from '@/Components/AppDataTable.vue';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import Avatar from 'primevue/avatar';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', role: null }),
    },
});

const searchQuery = ref(props.filters.search || '');
const selectedRoleFilter = ref(props.filters.role || null);

const showModal = ref(false);
const editingUser = ref(null);
const showDeleteModal = ref(false);
const userToDelete = ref(null);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    role: '',
    is_active: true,
});

const filterData = () => {
    router.get(
        '/users',
        {
            search: searchQuery.value || undefined,
            role: selectedRoleFilter.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const openCreateModal = () => {
    editingUser.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    if (props.roles.length > 0) {
        form.role = props.roles[0];
    }
    showModal.value = true;
};

const openEditModal = (user) => {
    editingUser.value = user;
    form.clearErrors();
    form.name = user.name;
    form.email = user.email;
    form.phone = user.phone || '';
    form.password = '';
    form.role = user.role;
    form.is_active = user.is_active;
    showModal.value = true;
};

const resetUserForm = () => {
    form.reset();
    form.clearErrors();
    editingUser.value = null;
    showModal.value = false;
};

const submitUser = () => {
    if (editingUser.value) {
        form.put(`/users/${editingUser.value.id}`, {
            onSuccess: () => {
                resetUserForm();
            },
        });
    } else {
        form.post('/users', {
            onSuccess: () => {
                resetUserForm();
            },
        });
    }
};

const toggleUserStatus = (user) => {
    router.patch(`/users/${user.id}/toggle-status`, {}, { preserveScroll: true });
};

const confirmDelete = (user) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (userToDelete.value) {
        form.delete(`/users/${userToDelete.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false;
                userToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <Head title="User Management - Mominul Bekari" />

    <AppLayout title="User Management">
        <div class="space-y-6">
            <!-- Filter Bar -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                    <div class="relative w-full sm:w-72">
                        <i class="pi pi-search absolute left-3 top-3 text-slate-400 text-xs"></i>
                        <InputText
                            v-model="searchQuery"
                            placeholder="Search by name, email, phone..."
                            @keydown.enter="filterData"
                            class="!pl-9 !text-xs !rounded-xl w-full"
                        />
                    </div>

                    <Select
                        v-model="selectedRoleFilter"
                        :options="roles"
                        placeholder="All Roles"
                        :showClear="true"
                        @change="filterData"
                        class="w-full sm:w-48 !text-xs !rounded-xl"
                    />

                    <Button
                        type="button"
                        label="Filter"
                        severity="secondary"
                        size="small"
                        @click="filterData"
                        class="!rounded-xl text-xs w-full sm:w-auto"
                    />
                </div>

                <Button
                    type="button"
                    icon="pi pi-user-plus"
                    label="Add New User"
                    @click="openCreateModal"
                    class="!bg-amber-500 hover:!bg-amber-600 !text-slate-950 font-bold !border-none !rounded-xl text-xs w-full sm:w-auto"
                />
            </div>

            <!-- Users DataTable -->
            <AppDataTable :value="users" title="Staff & Accounts">
                <Column header="User Profile">
                    <template #body="{ data }">
                        <div class="flex items-center gap-3 py-1">
                            <Avatar
                                :label="data.name.charAt(0).toUpperCase()"
                                shape="circle"
                                class="bg-slate-800 text-amber-400 font-bold shrink-0"
                            />
                            <div>
                                <div class="font-bold text-slate-900 text-sm">{{ data.name }}</div>
                                <div class="text-xs text-slate-500">{{ data.email }}</div>
                            </div>
                        </div>
                    </template>
                </Column>

                <Column field="phone" header="Contact Phone">
                    <template #body="{ data }">
                        <span class="text-xs font-medium text-slate-700">
                            {{ data.phone || '—' }}
                        </span>
                    </template>
                </Column>

                <Column field="role" header="Role Assigned">
                    <template #body="{ data }">
                        <Tag
                            :value="data.role || 'No Role'"
                            severity="info"
                            class="text-[10px] font-bold"
                        />
                    </template>
                </Column>

                <Column field="is_active" header="Status">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <Tag
                                :value="data.is_active ? 'Active' : 'Inactive'"
                                :severity="data.is_active ? 'success' : 'danger'"
                                class="text-[10px] font-bold"
                            />
                            <button
                                @click="toggleUserStatus(data)"
                                :title="data.is_active ? 'Deactivate user' : 'Activate user'"
                                class="text-slate-400 hover:text-slate-600 p-1"
                            >
                                <i :class="['pi text-xs', data.is_active ? 'pi-lock text-rose-500' : 'pi-unlock text-emerald-500']"></i>
                            </button>
                        </div>
                    </template>
                </Column>

                <Column field="created_at" header="Registered Date" style="width: 140px">
                    <template #body="{ data }">
                        <span class="text-xs text-slate-700 font-medium">{{ formatDate(data.created_at) }}</span>
                    </template>
                </Column>

                <Column header="Actions" :exportable="false" style="min-width: 8rem; text-align: right;">
                    <template #body="{ data }">
                        <div class="flex items-center justify-end gap-1">
                            <Button
                                type="button"
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                @click="openEditModal(data)"
                                title="Edit User"
                            />
                            <Button
                                type="button"
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                size="small"
                                @click="confirmDelete(data)"
                                title="Delete User"
                            />
                        </div>
                    </template>
                </Column>
            </AppDataTable>

            <!-- User Create / Edit Dialog -->
            <Dialog
                v-model:visible="showModal"
                :modal="true"
                @hide="resetUserForm"
                :header="editingUser ? `Edit User: ${editingUser.name}` : 'Create New User'"
                class="w-full max-w-lg"
            >
                <form @submit.prevent="submitUser" class="space-y-4 py-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Full Name *</label>
                        <InputText v-model="form.name" placeholder="e.g. Tariqul Islam" class="w-full !rounded-xl" />
                        <span v-if="form.errors.name" class="text-xs text-rose-600 font-medium mt-1 block">
                            {{ form.errors.name }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Email Address *</label>
                            <InputText v-model="form.email" type="email" placeholder="user@mominulbekari.com" class="w-full !rounded-xl" />
                            <span v-if="form.errors.email" class="text-xs text-rose-600 font-medium mt-1 block">
                                {{ form.errors.email }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Phone Number</label>
                            <InputText v-model="form.phone" placeholder="017xxxxxxxx" class="w-full !rounded-xl" />
                            <span v-if="form.errors.phone" class="text-xs text-rose-600 font-medium mt-1 block">
                                {{ form.errors.phone }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Password {{ editingUser ? '(Leave blank to keep)' : '*' }}
                            </label>
                            <Password
                                v-model="form.password"
                                :feedback="false"
                                toggleMask
                                class="w-full"
                                inputClass="w-full !rounded-xl"
                            />
                            <span v-if="form.errors.password" class="text-xs text-rose-600 font-medium mt-1 block">
                                {{ form.errors.password }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">System Role *</label>
                            <Select
                                v-model="form.role"
                                :options="roles"
                                placeholder="Select Role"
                                class="w-full !rounded-xl"
                            />
                            <span v-if="form.errors.role" class="text-xs text-rose-600 font-medium mt-1 block">
                                {{ form.errors.role }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <ToggleSwitch v-model="form.is_active" />
                        <label class="text-xs font-bold text-slate-700">Account Active (Permit login access)</label>
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
                            :label="editingUser ? 'Update User' : 'Create User'"
                            :loading="form.processing"
                            class="!bg-amber-500 hover:!bg-amber-600 !text-slate-950 font-bold !border-none !rounded-xl text-xs"
                        />
                    </div>
                </form>
            </Dialog>

            <!-- Confirm Delete Modal -->
            <ConfirmModal
                v-model:visible="showDeleteModal"
                title="Delete User"
                :message="`Are you sure you want to delete user '${userToDelete?.name}'?`"
                :loading="form.processing"
                @confirm="executeDelete"
            />
        </div>
    </AppLayout>
</template>
