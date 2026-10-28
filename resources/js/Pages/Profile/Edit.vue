<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const toast = useToast();

const profileForm = useForm({
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone || '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updateProfile = () => {
    profileForm.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Profile Updated',
                detail: 'Your profile information has been saved successfully.',
                life: 3500,
            });
        },
    });
};

const updatePassword = () => {
    passwordForm.put(route('profile.password'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            toast.add({
                severity: 'success',
                summary: 'Password Updated',
                detail: 'Your account password has been updated successfully.',
                life: 3500,
            });
        },
        onError: () => {
            passwordForm.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head title="Staff Profile & Security" />

    <AppLayout title="Staff Profile">
        <div class="space-y-6 max-w-4xl mx-auto">
            <!-- Header Banner -->
            <div class="bg-gradient-to-r from-amber-600 via-amber-700 to-amber-800 rounded-2xl p-6 text-white shadow-md relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 opacity-10 text-white pointer-events-none">
                    <i class="pi pi-user text-9xl"></i>
                </div>

                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 relative z-10">
                    <Avatar
                        :label="user.name ? user.name.charAt(0).toUpperCase() : 'U'"
                        shape="circle"
                        class="bg-white text-amber-800 font-black text-2xl !w-16 !h-16 shadow-lg border-2 border-amber-200 shrink-0"
                    />
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-2xl font-black tracking-tight leading-tight">
                                {{ user.name }}
                            </h1>
                            <Tag
                                :value="user.role"
                                severity="warn"
                                class="!bg-amber-400 !text-slate-950 font-bold text-xs uppercase px-2.5 py-0.5 tracking-wider"
                            />
                            <Tag
                                :value="user.is_active ? 'Active Account' : 'Inactive'"
                                :severity="user.is_active ? 'success' : 'danger'"
                                class="text-xs px-2 py-0.5"
                            />
                        </div>
                        <div class="flex flex-wrap items-center gap-4 text-xs text-amber-100 font-medium pt-1">
                            <span class="flex items-center gap-1.5">
                                <i class="pi pi-envelope text-[11px]"></i>
                                {{ user.email }}
                            </span>
                            <span v-if="user.phone" class="flex items-center gap-1.5">
                                <i class="pi pi-phone text-[11px]"></i>
                                {{ user.phone }}
                            </span>
                            <span v-if="user.created_at" class="flex items-center gap-1.5 opacity-80">
                                <i class="pi pi-calendar text-[11px]"></i>
                                Member since {{ user.created_at }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Profile Information Card -->
                <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                            <i class="pi pi-user-edit text-amber-600"></i>
                            Profile Information
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Update your staff account name, contact email, and mobile phone number.
                        </p>
                    </div>

                    <form @submit.prevent="updateProfile" class="space-y-4">
                        <div>
                            <label for="name" class="block text-xs font-bold text-slate-700 mb-1">
                                Full Name <span class="text-rose-500">*</span>
                            </label>
                            <InputText
                                id="name"
                                v-model="profileForm.name"
                                type="text"
                                class="w-full text-sm"
                                :class="{ 'p-invalid': profileForm.errors.name }"
                                required
                            />
                            <small v-if="profileForm.errors.name" class="text-rose-500 text-xs block mt-1">
                                {{ profileForm.errors.name }}
                            </small>
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-bold text-slate-700 mb-1">
                                Staff Email Address <span class="text-rose-500">*</span>
                            </label>
                            <InputText
                                id="email"
                                v-model="profileForm.email"
                                type="email"
                                class="w-full text-sm"
                                :class="{ 'p-invalid': profileForm.errors.email }"
                                required
                            />
                            <small v-if="profileForm.errors.email" class="text-rose-500 text-xs block mt-1">
                                {{ profileForm.errors.email }}
                            </small>
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-bold text-slate-700 mb-1">
                                Mobile Phone Number
                            </label>
                            <InputText
                                id="phone"
                                v-model="profileForm.phone"
                                type="text"
                                placeholder="e.g. 01711000001"
                                class="w-full text-sm"
                                :class="{ 'p-invalid': profileForm.errors.phone }"
                            />
                            <small v-if="profileForm.errors.phone" class="text-rose-500 text-xs block mt-1">
                                {{ profileForm.errors.phone }}
                            </small>
                            <span class="text-[11px] text-slate-400 block mt-0.5">
                                Can be used as your login credential instead of email.
                            </span>
                        </div>

                        <div class="flex justify-end pt-2">
                            <Button
                                type="submit"
                                label="Save Changes"
                                icon="pi pi-check"
                                severity="primary"
                                :loading="profileForm.processing"
                            />
                        </div>
                    </form>
                </div>

                <!-- Change Password Card -->
                <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                            <i class="pi pi-lock text-emerald-600"></i>
                            Security & Password
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Ensure your bakery system account is using a secure, long password.
                        </p>
                    </div>

                    <form @submit.prevent="updatePassword" class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-xs font-bold text-slate-700 mb-1">
                                Current Password <span class="text-rose-500">*</span>
                            </label>
                            <Password
                                id="current_password"
                                v-model="passwordForm.current_password"
                                :feedback="false"
                                toggleMask
                                class="w-full text-sm"
                                inputClass="w-full text-sm"
                                :class="{ 'p-invalid': passwordForm.errors.current_password }"
                                required
                            />
                            <small v-if="passwordForm.errors.current_password" class="text-rose-500 text-xs block mt-1">
                                {{ passwordForm.errors.current_password }}
                            </small>
                        </div>

                        <div>
                            <label for="password" class="block text-xs font-bold text-slate-700 mb-1">
                                New Password <span class="text-rose-500">*</span>
                            </label>
                            <Password
                                id="password"
                                v-model="passwordForm.password"
                                toggleMask
                                class="w-full text-sm"
                                inputClass="w-full text-sm"
                                :class="{ 'p-invalid': passwordForm.errors.password }"
                                required
                            />
                            <small v-if="passwordForm.errors.password" class="text-rose-500 text-xs block mt-1">
                                {{ passwordForm.errors.password }}
                            </small>
                            <span class="text-[11px] text-slate-400 block mt-0.5">
                                Minimum 8 characters required.
                            </span>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1">
                                Confirm New Password <span class="text-rose-500">*</span>
                            </label>
                            <Password
                                id="password_confirmation"
                                v-model="passwordForm.password_confirmation"
                                :feedback="false"
                                toggleMask
                                class="w-full text-sm"
                                inputClass="w-full text-sm"
                                :class="{ 'p-invalid': passwordForm.errors.password_confirmation }"
                                required
                            />
                            <small v-if="passwordForm.errors.password_confirmation" class="text-rose-500 text-xs block mt-1">
                                {{ passwordForm.errors.password_confirmation }}
                            </small>
                        </div>

                        <div class="flex justify-end pt-2">
                            <Button
                                type="submit"
                                label="Update Password"
                                icon="pi pi-shield"
                                severity="secondary"
                                :loading="passwordForm.processing"
                            />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

