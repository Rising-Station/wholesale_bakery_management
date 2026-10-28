<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Message from 'primevue/message';

const props = defineProps({
    status: {
        type: String,
        default: null,
    },
    demoLoginEnabled: {
        type: Boolean,
        default: true,
    },
});

const showDemoRoles = ref(true);

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};

const demoRoles = [
    {
        name: 'Super Admin',
        role: 'Proprietor',
        email: 'admin@mominulbekari.com',
        phone: '01711000001',
        icon: 'pi pi-shield',
        color: 'bg-amber-100 text-amber-800 border-amber-300 hover:bg-amber-200',
    },
    {
        name: 'Depot Manager',
        role: 'BranchManager',
        email: 'manager@mominulbekari.com',
        phone: '01711000002',
        icon: 'pi pi-briefcase',
        color: 'bg-blue-100 text-blue-800 border-blue-300 hover:bg-blue-200',
    },
    {
        name: 'Accountant',
        role: 'Accountant',
        email: 'accounts@mominulbekari.com',
        phone: '01711000003',
        icon: 'pi pi-calculator',
        color: 'bg-emerald-100 text-emerald-800 border-emerald-300 hover:bg-emerald-200',
    },
    {
        name: 'Sales Driver',
        role: 'SalesDriver',
        email: 'driver.a@mominulbekari.com',
        phone: '01711000004',
        icon: 'pi pi-truck',
        color: 'bg-purple-100 text-purple-800 border-purple-300 hover:bg-purple-200',
    },
    {
        name: 'Storekeeper',
        role: 'InventoryStorekeeper',
        email: 'store@mominulbekari.com',
        phone: '01711000005',
        icon: 'pi pi-box',
        color: 'bg-orange-100 text-orange-800 border-orange-300 hover:bg-orange-200',
    },
];

const fillDemoCredentials = (role) => {
    form.login = role.email;
    form.password = 'password123';
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-amber-50 via-slate-50 to-orange-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans">
        <Head title="Staff Login - Mominul Bekari ERP" />

        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <Link href="/" class="inline-flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-600 to-amber-700 flex items-center justify-center text-white shadow-lg shadow-amber-600/30">
                    <i class="pi pi-shop text-2xl"></i>
                </div>
                <div class="text-left">
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight leading-tight">
                        Mominul Bekari
                    </h1>
                    <span class="text-xs uppercase tracking-widest font-bold text-amber-700">
                        Wholesale Distribution ERP
                    </span>
                </div>
            </Link>
            <p class="mt-3 text-sm text-slate-600 font-medium">
                Sign in with your Staff Email or Registered Phone number
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-6 shadow-xl shadow-slate-200/60 rounded-3xl border border-slate-100 sm:px-10">
                <!-- Status Alert -->
                <div v-if="status" class="mb-5 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold flex items-center gap-2">
                    <i class="pi pi-check-circle text-base"></i>
                    <span>{{ status }}</span>
                </div>

                <!-- Global Error Message -->
                <div v-if="form.errors.login" class="mb-5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center gap-2">
                    <i class="pi pi-exclamation-triangle text-base flex-shrink-0"></i>
                    <span>{{ form.errors.login }}</span>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Login Identifier (Email or Phone) -->
                    <div>
                        <label for="login" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Staff Email or Phone Number <span class="text-rose-500">*</span>
                        </label>
                        <div class="p-input-icon-left w-full">
                            <InputText
                                id="login"
                                v-model="form.login"
                                type="text"
                                placeholder="e.g. admin@mominulbekari.com or 01711000001"
                                class="w-full text-sm py-2.5 px-3 rounded-xl"
                                :class="{ 'p-invalid': form.errors.login }"
                                required
                                autofocus
                            />
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Account Password <span class="text-rose-500">*</span>
                        </label>
                        <Password
                            id="password"
                            v-model="form.password"
                            :feedback="false"
                            toggleMask
                            placeholder="Enter password (default: password123)"
                            class="w-full text-sm"
                            inputClass="w-full text-sm py-2.5 px-3 rounded-xl"
                            :class="{ 'p-invalid': form.errors.password }"
                            required
                        />
                        <small v-if="form.errors.password" class="text-rose-500 text-xs mt-1 block">
                            {{ form.errors.password }}
                        </small>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <Checkbox v-model="form.remember" binary inputId="remember" />
                            <label for="remember" class="text-slate-600 font-medium cursor-pointer">Remember session</label>
                        </div>
                        <span class="text-slate-400 font-medium">Default: password123</span>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <Button
                            type="submit"
                            label="Sign In to Dashboard"
                            icon="pi pi-sign-in"
                            class="w-full py-3 text-sm font-bold bg-amber-600 hover:bg-amber-700 text-white rounded-xl shadow-md shadow-amber-600/30 transition-all border-none"
                            :loading="form.processing"
                        />
                    </div>
                </form>

                <!-- Quick Demo Login Section -->
                <div v-if="demoLoginEnabled" class="mt-6 pt-6 border-t border-slate-100">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            ⚡ Quick Demo Login (Click to Auto-fill)
                        </span>
                        <button
                            type="button"
                            @click="showDemoRoles = !showDemoRoles"
                            class="text-xs font-semibold text-amber-600 hover:text-amber-700 flex items-center gap-1 transition-colors px-2 py-0.5 rounded-lg hover:bg-amber-50"
                            :title="showDemoRoles ? 'Hide demo login shortcuts' : 'Show demo login shortcuts'"
                        >
                            <span>{{ showDemoRoles ? 'Hide' : 'Show' }}</span>
                            <i :class="showDemoRoles ? 'pi pi-chevron-up' : 'pi pi-chevron-down'" class="text-[10px]"></i>
                        </button>
                    </div>

                    <div v-show="showDemoRoles" class="grid grid-cols-1 sm:grid-cols-2 gap-2 transition-all">
                        <button
                            v-for="role in demoRoles"
                            :key="role.role"
                            type="button"
                            class="flex items-center gap-2 p-2 rounded-xl border text-xs font-semibold transition-all text-left"
                            :class="role.color"
                            @click="fillDemoCredentials(role)"
                        >
                            <i :class="role.icon" class="text-sm"></i>
                            <div class="truncate">
                                <div class="font-bold leading-tight">{{ role.name }}</div>
                                <div class="text-[10px] opacity-75 truncate">{{ role.email }}</div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Footer Back Link -->
                <div class="mt-6 text-center">
                    <Link href="/" class="text-xs font-semibold text-amber-700 hover:text-amber-800 flex items-center justify-center gap-1">
                        <i class="pi pi-arrow-left text-[10px]"></i>
                        Back to Public Catalog & Homepage
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
