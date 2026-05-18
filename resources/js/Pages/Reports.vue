<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    reports: {
        type: Array,
        default: () => [],
    },
});

/* ------------------------------------------------------------------ */
/*  Générer rapport form                                              */
/* ------------------------------------------------------------------ */

const form = useForm({});

function generer() {
    form.post(route('reports.generate'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
}

/* ------------------------------------------------------------------ */
/*  Helpers                                                            */
/* ------------------------------------------------------------------ */

function formatDate(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('fr-CA', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function formatDateShort(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('fr-CA', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatTime(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleTimeString('fr-CA', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function pdfUrl(report) {
    return route('reports.downloadPdf', { report: report.id });
}

/* ------------------------------------------------------------------ */
/*  Empty / populated states                                           */
/* ------------------------------------------------------------------ */

const hasReports = computed(() => props.reports.length > 0);

/* ------------------------------------------------------------------ */
/*  Status badge helper                                                */
/* ------------------------------------------------------------------ */

const statusInfo = computed(() => (report) => {
    // If the report was generated in the last 30 minutes, consider it recent
    const createdAt = new Date(report.created_at);
    const thirtyMinAgo = new Date(Date.now() - 30 * 60 * 1000);
    if (createdAt > thirtyMinAgo) {
        return {
            label: 'Nouveau',
            class: 'bg-emerald-100 text-emerald-700 ring-emerald-600/20',
        };
    }
    return {
        label: 'Généré',
        class: 'bg-gray-100 text-gray-600 ring-gray-500/10',
    };
});
</script>

<template>
    <Head title="Rapports" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Rapports de santé
                </h2>
                <button
                    @click="generer"
                    :disabled="form.processing"
                    class="inline-flex items-center justify-center gap-2 self-start rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <!-- Sparkle / generate icon -->
                    <svg
                        v-if="!form.processing"
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"
                        />
                    </svg>
                    <!-- Spinner -->
                    <svg
                        v-else
                        class="h-4 w-4 animate-spin"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                        />
                    </svg>
                    {{ form.processing ? 'Génération en cours…' : 'Générer un rapport' }}
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <!-- ============================================================ -->
                <!--  Empty state                                                 -->
                <!-- ============================================================ -->
                <div
                    v-if="!hasReports"
                    class="flex flex-col items-center justify-center rounded-xl bg-white px-6 py-20 text-center shadow-sm ring-1 ring-gray-200"
                >
                    <!-- Empty illustration (document icon) -->
                    <svg
                        class="mb-4 h-16 w-16 text-gray-300"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
                        />
                    </svg>

                    <h3 class="text-lg font-semibold text-gray-700">
                        Aucun rapport pour le moment
                    </h3>
                    <p class="mt-2 max-w-sm text-sm text-gray-500">
                        Cliquez sur «&nbsp;Générer un rapport&nbsp;» pour créer votre premier rapport de santé
                        complet avec toutes vos données récentes.
                    </p>

                    <button
                        @click="generer"
                        :disabled="form.processing"
                        class="mt-6 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 4.5v15m7.5-7.5h-15"
                            />
                        </svg>
                        Générer un rapport
                    </button>
                </div>

                <!-- ============================================================ -->
                <!--  Reports list (cards)                                        -->
                <!-- ============================================================ -->
                <div v-else class="space-y-4">
                    <!-- Section header -->
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500">
                            {{ reports.length }}
                            {{ reports.length === 1 ? 'rapport généré' : 'rapports générés' }}
                        </p>
                    </div>

                    <!-- Report cards -->
                    <div class="space-y-3">
                        <div
                            v-for="report in reports"
                            :key="report.id"
                            class="group flex flex-col gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 transition hover:shadow-md sm:flex-row sm:items-center sm:justify-between sm:p-5"
                        >
                            <!-- Left: report info -->
                            <div class="flex-1 space-y-1.5 min-w-0">
                                <!-- Title + status badge -->
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-base font-semibold text-gray-800 truncate">
                                        {{ report.title }}
                                    </h3>
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                        :class="statusInfo(report).class"
                                    >
                                        {{ statusInfo(report).label }}
                                    </span>
                                </div>

                                <!-- Dates -->
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                                    <!-- Period -->
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                        </svg>
                                        <span>
                                            {{ formatDateShort(report.period_start) }}
                                            →
                                            {{ formatDateShort(report.period_end) }}
                                        </span>
                                    </span>

                                    <!-- Generated at -->
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>
                                            Généré le {{ formatDate(report.created_at) }}
                                            à {{ formatTime(report.created_at) }}
                                        </span>
                                    </span>
                                </div>

                                <!-- Notes (if any) -->
                                <p
                                    v-if="report.notes"
                                    class="pt-0.5 text-sm text-gray-500 italic line-clamp-1"
                                >
                                    {{ report.notes }}
                                </p>
                            </div>

                            <!-- Right: download button -->
                            <div class="shrink-0">
                                <a
                                    :href="pdfUrl(report)"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-emerald-700 shadow-sm ring-1 ring-emerald-200 transition hover:bg-emerald-50 hover:ring-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                                >
                                    <!-- Download icon -->
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                    Télécharger PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Footer                                                       -->
                <!-- ============================================================ -->
                <p class="pt-8 pb-4 text-center text-xs text-gray-400">
                    Rapport de santé · Généré par C-3PO 🤖
                </p>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
