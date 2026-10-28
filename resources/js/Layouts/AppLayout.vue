<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Avatar from 'primevue/avatar';
import Menu from 'primevue/menu';

const props = defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
});

const page = usePage();
const toast = useToast();
const sidebarOpen = ref(true);
const mobileMenuOpen = ref(false);
const userMenu = ref();

const authUser = computed(() => page.props.auth?.user || { name: 'Mominul Bekari Admin', email: 'admin@mominulbekari.com', roles: ['SuperAdmin'] });

const hasPermission = (permission) => {
    if (!permission) return true;
    if (!authUser.value) return false;
    if (authUser.value.roles?.includes('SuperAdmin')) return true;
    return Boolean(authUser.value.permissions?.includes(permission));
};

const hasRole = (role) => {
    return Boolean(authUser.value?.roles?.includes(role));
};

const toggleUserMenu = (event) => {
    userMenu.value.toggle(event);
};

const userMenuItems = computed(() => [
    {
        label: 'My Profile',
        icon: 'pi pi-user',
        command: () => router.visit(route('profile.edit')),
    },
    ...(hasPermission('settings.manage')
        ? [
              {
                  label: 'System Settings',
                  icon: 'pi pi-cog',
                  command: () => router.visit('/settings'),
              },
          ]
        : []),
    {
        separator: true,
    },
    {
        label: 'Logout',
        icon: 'pi pi-sign-out',
        class: 'text-rose-600',
        command: () => router.post('/logout'),
    },
]);

// Watch for Inertia flash messages
watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;
        if (flash.success) {
            toast.add({ severity: 'success', summary: 'Success', detail: flash.success, life: 4000 });
        }
        if (flash.error) {
            toast.add({ severity: 'error', summary: 'Error', detail: flash.error, life: 5000 });
        }
        if (flash.warning) {
            toast.add({ severity: 'warn', summary: 'Warning', detail: flash.warning, life: 4500 });
        }
        if (flash.info) {
            toast.add({ severity: 'info', summary: 'Information', detail: flash.info, life: 4000 });
        }
    },
    { deep: true, immediate: true }
);

const navSections = computed(() => {
    const rawSections = [
        {
            title: 'Core Distribution',
            items: [
                { label: 'Dashboard', route: '/dashboard', icon: 'pi pi-chart-bar' },
                { label: 'Vehicle Trips', route: '/trips', icon: 'pi pi-truck', badge: 'Active', permission: 'trips.view' },
                { label: 'Sales Memos', route: '/memos', icon: 'pi pi-file-edit', permission: 'memos.view' },
                { label: 'Damage & Returns', route: '/damage-returns', icon: 'pi pi-exclamation-triangle', permission: 'damage.view' },
            ],
        },
        {
            title: 'Network & Master Data',
            items: [
                { label: 'Roads (A, B, C)', route: '/roads', icon: 'pi pi-map', permission: 'roads.view' },
                { label: 'Retailer Shops', route: '/retailers', icon: 'pi pi-shop', permission: 'retailers.view' },
                { label: 'Central Warehouse Stock', route: '/stock', icon: 'pi pi-box', permission: 'stock.view' },
                { label: 'Stock Adjustments', route: '/stock-adjustments', icon: 'pi pi-sliders-h', permission: 'stock.adjust' },
                { label: 'Product Catalog', route: '/products', icon: 'pi pi-tags', permission: 'products.view' },
                { label: 'Suppliers (Tier 1)', route: '/suppliers', icon: 'pi pi-building', permission: 'suppliers.view' },
                { label: 'Purchase Invoices', route: '/purchases', icon: 'pi pi-shopping-cart', permission: 'purchases.view' },
            ],
        },
        {
            title: 'Finance & Governance',
            items: [
                { label: 'Road Reconciliation', route: '/reports/road-settlement', icon: 'pi pi-calculator', permission: 'reports.view' },
                { label: 'Overhead Expenses', route: '/expenses', icon: 'pi pi-wallet', permission: 'expenses.view' },
                { label: 'User Management', route: '/users', icon: 'pi pi-users', permission: 'users.view' },
                { label: 'Roles & Permissions', route: '/roles', icon: 'pi pi-shield', permission: 'roles.view' },
                { label: 'General Settings', route: '/settings', icon: 'pi pi-cog', permission: 'settings.manage' },
                { label: 'Database Backup', route: '/backup', icon: 'pi pi-database', permission: 'backup.manage' },
            ],
        },
    ];

    return rawSections
        .map((section) => ({
            ...section,
            items: section.items.filter((item) => hasPermission(item.permission)),
        }))
        .filter((section) => section.items.length > 0);
});

const isCurrentRoute = (url) => {
    return page.url === url || (url !== '/dashboard' && page.url.startsWith(url));
};
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex flex-col font-sans">
        <Toast position="top-right" />
        <ConfirmDialog />

        <div class="flex flex-1 overflow-hidden">
            <!-- Desktop Sidebar -->
            <aside
                :class="[
                    'bg-slate-900 text-slate-200 transition-all duration-300 ease-in-out hidden lg:flex flex-col z-30 shadow-xl border-r border-slate-800',
                    sidebarOpen ? 'w-64' : 'w-20',
                ]"
            >
                <!-- Brand Header -->
                <div class="h-16 flex items-center justify-between px-4 bg-slate-950/60 border-b border-slate-800/80">
                    <Link href="/dashboard" class="flex items-center gap-3 overflow-hidden">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-300 flex items-center justify-center text-slate-950 font-bold text-xl shadow-md shrink-0">
                            MB
                        </div>
                        <div v-if="sidebarOpen" class="flex flex-col">
                            <span class="font-bold text-white text-base tracking-tight leading-tight">Mominul Bekari</span>
                            <span class="text-[10px] uppercase font-semibold text-amber-400 tracking-wider">Wholesale Hub</span>
                        </div>
                    </Link>
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-slate-800 transition"
                        title="Toggle sidebar"
                    >
                        <i :class="['pi text-sm', sidebarOpen ? 'pi-chevron-left' : 'pi-chevron-right']"></i>
                    </button>
                </div>

                <!-- Navigation Links -->
                <div class="flex-1 overflow-y-auto py-4 px-3 space-y-6">
                    <div v-for="(section, sIdx) in navSections" :key="sIdx">
                        <div
                            v-if="sidebarOpen"
                            class="px-3 mb-2 text-[11px] font-semibold tracking-wider text-slate-400 uppercase"
                        >
                            {{ section.title }}
                        </div>
                        <div v-else class="h-2"></div>

                        <div class="space-y-1">
                            <Link
                                v-for="(item, iIdx) in section.items"
                                :key="iIdx"
                                :href="item.route"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group relative',
                                    isCurrentRoute(item.route)
                                        ? 'bg-amber-500 text-slate-950 font-semibold shadow-md shadow-amber-500/20'
                                        : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
                                    !sidebarOpen ? 'justify-center' : '',
                                ]"
                                :title="!sidebarOpen ? item.label : ''"
                            >
                                <i :class="[item.icon, 'text-base shrink-0', isCurrentRoute(item.route) ? 'text-slate-950' : 'text-slate-400 group-hover:text-amber-400']"></i>
                                <span v-if="sidebarOpen" class="truncate">{{ item.label }}</span>
                                <span
                                    v-if="sidebarOpen && item.badge"
                                    class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-400/20 text-amber-300"
                                >
                                    {{ item.badge }}
                                </span>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Footer Depot Status -->
                <div v-if="sidebarOpen" class="p-3 border-t border-slate-800/80 bg-slate-950/40">
                    <div class="flex items-center gap-2.5 px-2 py-2 rounded-lg bg-slate-800/50 text-xs">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <div class="flex flex-col">
                            <span class="font-medium text-slate-200">Roads Active: A, B, C</span>
                            <span class="text-[10px] text-slate-400">1 Vehicle / Road</span>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Mobile Drawer -->
            <div
                v-if="mobileMenuOpen"
                class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm lg:hidden flex"
                @click="mobileMenuOpen = false"
            >
                <div class="w-72 bg-slate-900 h-full flex flex-col p-4 text-slate-200" @click.stop>
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-amber-500 flex items-center justify-center text-slate-950 font-bold">
                                MB
                            </div>
                            <span class="font-bold text-white text-lg">Mominul Bekari</span>
                        </div>
                        <button @click="mobileMenuOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-white">
                            <i class="pi pi-times"></i>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto py-4 space-y-4">
                        <div v-for="(section, sIdx) in navSections" :key="sIdx">
                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-2">
                                {{ section.title }}
                            </div>
                            <div class="space-y-1">
                                <Link
                                    v-for="(item, iIdx) in section.items"
                                    :key="iIdx"
                                    :href="item.route"
                                    @click="mobileMenuOpen = false"
                                    :class="[
                                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition',
                                        isCurrentRoute(item.route)
                                            ? 'bg-amber-500 text-slate-950 font-semibold'
                                            : 'text-slate-300 hover:bg-slate-800',
                                    ]"
                                >
                                    <i :class="[item.icon, 'text-base']"></i>
                                    <span>{{ item.label }}</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
                <!-- Topbar -->
                <header class="h-16 bg-white border-b border-slate-200 px-4 lg:px-8 flex items-center justify-between sticky top-0 z-20 shadow-xs">
                    <div class="flex items-center gap-3">
                        <button
                            @click="mobileMenuOpen = true"
                            class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100"
                        >
                            <i class="pi pi-bars text-lg"></i>
                        </button>
                        <div>
                            <h1 class="text-lg lg:text-xl font-bold text-slate-900 flex items-center gap-2">
                                {{ title }}
                            </h1>
                        </div>
                    </div>

                    <!-- Right Controls -->
                    <div class="flex items-center gap-3">
                        <!-- Quick Link for Memo -->
                        <Link
                            v-if="hasPermission('memos.create')"
                            href="/memos?action=create"
                            class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-slate-950 font-semibold text-xs transition shadow-xs"
                        >
                            <i class="pi pi-plus-circle text-xs"></i>
                            <span>New Memo</span>
                        </Link>

                        <!-- User Profile Menu -->
                        <div class="relative">
                            <button
                                @click="toggleUserMenu"
                                class="flex items-center gap-2.5 p-1.5 rounded-xl hover:bg-slate-100 transition"
                            >
                                <Avatar
                                    :label="authUser.name ? authUser.name.charAt(0).toUpperCase() : 'A'"
                                    shape="circle"
                                    class="bg-slate-800 text-amber-400 font-bold"
                                />
                                <div class="hidden md:flex flex-col text-left">
                                    <span class="text-xs font-bold text-slate-900 leading-tight">{{ authUser.name }}</span>
                                    <span class="text-[10px] text-slate-500 font-medium leading-tight">
                                        {{ authUser.roles && authUser.roles.length ? authUser.roles[0] : 'Administrator' }}
                                    </span>
                                </div>
                                <i class="pi pi-angle-down text-xs text-slate-400 hidden md:block"></i>
                            </button>
                            <Menu ref="userMenu" :model="userMenuItems" :popup="true" />
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 p-4 lg:p-4 max-w-8xl w-full mx-auto">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
