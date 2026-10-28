<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Message from 'primevue/message';
import { formatDateTime } from '@/Utils/date';

const props = defineProps({
    backups: {
        type: Array,
        default: () => [],
    },
});

const createBackup = () => {
    router.post(route('backup.store'));
};

const deleteBackup = (id) => {
    if (confirm('Are you sure you want to permanently delete this backup snapshot?')) {
        router.delete(route('backup.destroy', id));
    }
};
</script>

<template>
    <Head title="Database Backup & Recovery" />

    <AppLayout>
        <div class="space-y-6 max-w-4xl">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                        Database Snapshots & Data Backups
                    </h1>
                    <p class="text-sm text-slate-500">
                        Generate and download point-in-time SQLite database snapshots for disaster recovery and offsite security.
                    </p>
                </div>

                <div>
                    <Button
                        icon="pi pi-database"
                        label="Create Instant Backup"
                        severity="primary"
                        @click="createBackup"
                    />
                </div>
            </div>

            <!-- Informational Message -->
            <Message severity="info" :closable="false">
                <div class="text-xs text-slate-700 leading-relaxed">
                    <strong>Disaster Recovery Note:</strong> Database snapshots capture all central warehouse stock movements, supplier invoices, road dispatches, sales memos, and retailer due balances at the exact time of creation. It is recommended to download a copy daily.
                </div>
            </Message>

            <!-- Backups Table -->
            <div class="bg-white rounded-xl border border-slate-200/80 overflow-hidden shadow-sm">
                <div class="p-4 bg-slate-50/70 border-b border-slate-200">
                    <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider">
                        Available Backup Snapshots
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-slate-100/60 border-b text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                <th class="p-3.5">Filename</th>
                                <th class="p-3.5">Created At</th>
                                <th class="p-3.5">File Size</th>
                                <th class="p-3.5 text-center">Storage State</th>
                                <th class="p-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="backup in backups" :key="backup.id" class="hover:bg-slate-50/80">
                                <td class="p-3.5 font-mono text-xs font-semibold text-slate-800">
                                    <div class="flex items-center gap-2">
                                        <i class="pi pi-file text-slate-400"></i>
                                        {{ backup.filename }}
                                    </div>
                                </td>

                                <td class="p-3.5 text-xs text-slate-600">
                                    {{ formatDateTime(backup.created_at) }}
                                </td>

                                <td class="p-3.5 text-xs font-mono font-bold text-slate-800">
                                    {{ backup.file_size }}
                                </td>

                                <td class="p-3.5 text-center text-xs">
                                    <span
                                        v-if="backup.exists"
                                        class="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded text-[11px] font-medium"
                                    >
                                        <i class="pi pi-check text-[9px]"></i> Available
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 text-rose-700 bg-rose-50 px-2 py-0.5 rounded text-[11px] font-medium"
                                    >
                                        <i class="pi pi-times text-[9px]"></i> Missing
                                    </span>
                                </td>

                                <td class="p-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a
                                            :href="route('backup.download', backup.id)"
                                            target="_blank"
                                            class="inline-flex"
                                        >
                                            <Button
                                                icon="pi pi-download"
                                                label="Download"
                                                size="small"
                                                severity="secondary"
                                                text
                                            />
                                        </a>

                                        <Button
                                            icon="pi pi-trash"
                                            size="small"
                                            severity="danger"
                                            text
                                            rounded
                                            @click="deleteBackup(backup.id)"
                                        />
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!backups.length">
                                <td colspan="5" class="p-6 text-center text-xs text-slate-400">
                                    No database backup snapshots created yet. Click "Create Instant Backup" above to generate your first snapshot.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
