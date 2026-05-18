<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import HealthInsightsPanel from '@/Components/HealthInsightsPanel.vue';
import { Line, Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    Filler,
);

const props = defineProps({
    healthInsights: Object,
    kpis: Object,
    weightReadings: Array,
    bodyMeasurements: Array,
    nutritionLogs: Array,
    activityMonthly: Array,
    nutritionMonthly: Array,
});

/* ------------------------------------------------------------------ */
/*  Helpers                                                            */
/* ------------------------------------------------------------------ */

function fmtDate(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('fr-CA', { month: 'short', day: 'numeric' });
}

function fmtMonth(ym) {
    if (!ym) return '';
    const [y, m] = ym.split('-');
    const months = [
        'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin',
        'Juill', 'Août', 'Sep', 'Oct', 'Nov', 'Déc',
    ];
    return `${months[parseInt(m, 10) - 1]} ${y}`;
}

/* ------------------------------------------------------------------ */
/*  KPI data                                                           */
/* ------------------------------------------------------------------ */

const kpiCards = computed(() => [
    {
        label: 'Poids actuel',
        value: props.kpis?.current_weight ? `${props.kpis.current_weight} lb` : '—',
        emoji: '⚖️',
        color: 'text-emerald-600',
        bg: 'bg-emerald-50',
    },
    {
        label: 'Perte totale',
        value: props.kpis?.total_weight_loss
            ? `${props.kpis.total_weight_loss > 0 ? '-' : ''}${Math.abs(props.kpis.total_weight_loss)} lb`
            : '—',
        emoji: '📉',
        color: props.kpis?.total_weight_loss > 0 ? 'text-emerald-600' : 'text-amber-600',
        bg: 'bg-emerald-50',
    },
    {
        label: 'Rapport T/H',
        value: props.kpis?.current_whr ? props.kpis.current_whr.toFixed(2) : '—',
        emoji: '📏',
        color: 'text-blue-600',
        bg: 'bg-blue-50',
    },
    {
        label: 'Tour de taille perdu',
        value: props.kpis?.waist_loss_cm
            ? `${props.kpis.waist_loss_cm > 0 ? '-' : ''}${Math.abs(props.kpis.waist_loss_cm)} cm`
            : '—',
        emoji: '📐',
        color: 'text-blue-600',
        bg: 'bg-blue-50',
    },
    {
        label: 'Séances gym',
        value: props.kpis?.total_gym_sessions ?? '—',
        emoji: '💪',
        color: 'text-violet-600',
        bg: 'bg-violet-50',
    },
    {
        label: 'Moy. calories/jour',
        value: props.kpis?.avg_calories ? `${props.kpis.avg_calories} kcal` : '—',
        emoji: '🍽️',
        color: 'text-orange-600',
        bg: 'bg-orange-50',
    },
]);

/* ------------------------------------------------------------------ */
/*  Chart options / data                                               */
/* ------------------------------------------------------------------ */

function defaultOpts(title, unit, color = '#0ea5e9') {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } },
            tooltip: {
                backgroundColor: 'rgba(15,23,42,0.9)',
                titleColor: '#f8fafc',
                bodyColor: '#e2e8f0',
                padding: 12,
                cornerRadius: 8,
            },
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { maxTicksLimit: 10, font: { size: 11 } },
            },
            y: {
                beginAtZero: false,
                grid: { color: 'rgba(0,0,0,0.05)' },
                ticks: { font: { size: 11 } },
                title: {
                    display: !!unit,
                    text: unit,
                    font: { size: 11 },
                },
            },
        },
    };
}

/* ---------- Weight chart ---------- */

const weightChartData = computed(() => ({
    labels: props.weightReadings.map((r) => fmtDate(r.recorded_at)),
    datasets: [
        {
            label: 'Poids (lb)',
            data: props.weightReadings.map((r) => r.weight_lb),
            borderColor: '#059669',
            backgroundColor: 'rgba(5,150,105,0.12)',
            fill: true,
            tension: 0.35,
            pointRadius: 3,
            pointHoverRadius: 6,
        },
    ],
}));

const weightChartOpts = computed(() => ({
    ...defaultOpts('Évolution du poids', 'lb', '#059669'),
    scales: {
        ...defaultOpts('', 'lb', '#059669').scales,
        y: {
            ...defaultOpts('', 'lb', '#059669').scales.y,
            beginAtZero: false,
        },
    },
}));

/* ---------- Body measurements chart ---------- */

const measurementsChartData = computed(() => ({
    labels: props.bodyMeasurements.map((r) => fmtDate(r.measured_at)),
    datasets: [
        {
            label: 'Tour de taille (cm)',
            data: props.bodyMeasurements.map((r) => r.waist_cm),
            borderColor: '#0ea5e9',
            backgroundColor: 'rgba(14,165,233,0.10)',
            fill: false,
            tension: 0.35,
            pointRadius: 2,
            pointHoverRadius: 5,
        },
        {
            label: 'Hanches (cm)',
            data: props.bodyMeasurements.map((r) => r.hips_cm),
            borderColor: '#8b5cf6',
            backgroundColor: 'rgba(139,92,246,0.10)',
            fill: false,
            tension: 0.35,
            pointRadius: 2,
            pointHoverRadius: 5,
        },
        {
            label: 'Abdomen (cm)',
            data: props.bodyMeasurements.map((r) => r.abdomen_cm),
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245,158,11,0.10)',
            fill: false,
            tension: 0.35,
            pointRadius: 2,
            pointHoverRadius: 5,
        },
    ],
}));

/* ---------- WHR chart + reference line ---------- */

const whrChartData = computed(() => ({
    labels: props.bodyMeasurements.map((r) => fmtDate(r.measured_at)),
    datasets: [
        {
            label: 'Rapport Taille/Hanches',
            data: props.bodyMeasurements.map((r) => r.whr),
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239,68,68,0.10)',
            fill: false,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 7,
            borderWidth: 2,
        },
        {
            label: 'Seuil de risque (0.90)',
            data: props.bodyMeasurements.map(() => 0.9),
            borderColor: 'rgba(239,68,68,0.35)',
            borderDash: [6, 4],
            borderWidth: 1.5,
            pointRadius: 0,
            pointHoverRadius: 0,
            fill: false,
        },
    ],
}));

const whrChartOpts = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } },
        tooltip: {
            backgroundColor: 'rgba(15,23,42,0.9)',
            titleColor: '#f8fafc',
            bodyColor: '#e2e8f0',
            padding: 12,
            cornerRadius: 8,
            callbacks: {
                label(ctx) {
                    if (ctx.dataset.label === 'Seuil de risque (0.90)') return 'Seuil de risque 0.90';
                    return `${ctx.dataset.label}: ${ctx.parsed.y.toFixed(2)}`;
                },
            },
        },
    },
    scales: {
        x: { grid: { display: false }, ticks: { maxTicksLimit: 10, font: { size: 11 } } },
        y: {
            min: 0.75,
            max: 1.05,
            grid: { color: 'rgba(0,0,0,0.05)' },
            ticks: { font: { size: 11 } },
        },
    },
}));

/* ---------- Monthly nutrition bar chart ---------- */

const nutritionMonthlyChartData = computed(() => ({
    labels: props.nutritionMonthly.map((r) => fmtMonth(r.month)),
    datasets: [
        {
            label: 'Calories',
            data: props.nutritionMonthly.map((r) => Math.round(r.avg_calories)),
            backgroundColor: 'rgba(249,115,22,0.75)',
            borderColor: '#f97316',
            borderWidth: 1,
            borderRadius: 4,
        },
        {
            label: 'Protéines (g)',
            data: props.nutritionMonthly.map((r) => Math.round(r.avg_protein)),
            backgroundColor: 'rgba(59,130,246,0.75)',
            borderColor: '#3b82f6',
            borderWidth: 1,
            borderRadius: 4,
        },
        {
            label: 'Lipides (g)',
            data: props.nutritionMonthly.map((r) => Math.round(r.avg_fat)),
            backgroundColor: 'rgba(234,179,8,0.75)',
            borderColor: '#eab308',
            borderWidth: 1,
            borderRadius: 4,
        },
        {
            label: 'Glucides (g)',
            data: props.nutritionMonthly.map((r) => Math.round(r.avg_carbs)),
            backgroundColor: 'rgba(34,197,94,0.75)',
            borderColor: '#22c55e',
            borderWidth: 1,
            borderRadius: 4,
        },
    ],
}));

const nutritionMonthlyOpts = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } },
        tooltip: {
            backgroundColor: 'rgba(15,23,42,0.9)',
            titleColor: '#f8fafc',
            bodyColor: '#e2e8f0',
            padding: 12,
            cornerRadius: 8,
        },
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 45 } },
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(0,0,0,0.05)' },
            ticks: { font: { size: 11 } },
        },
    },
}));

/* ---------- Daily nutrition bar chart (last 30 days) ---------- */

const dailyNutritionLogs = computed(() =>
    props.nutritionLogs
        .filter((r) => new Date(r.logged_at) >= new Date(Date.now() - 30 * 86400000))
        .slice(-30),
);

const dailyNutritionChartData = computed(() => ({
    labels: dailyNutritionLogs.value.map((r) => fmtDate(r.logged_at)),
    datasets: [
        {
            label: 'Calories',
            data: dailyNutritionLogs.value.map((r) => r.calories),
            backgroundColor: 'rgba(249,115,22,0.65)',
            borderColor: '#f97316',
            borderWidth: 1,
            borderRadius: 2,
        },
    ],
}));

const dailyNutritionOpts = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(15,23,42,0.9)',
            titleColor: '#f8fafc',
            bodyColor: '#e2e8f0',
            padding: 12,
            cornerRadius: 8,
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { maxTicksLimit: 8, font: { size: 10 } },
        },
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(0,0,0,0.05)' },
            ticks: { font: { size: 11 } },
            title: {
                display: true,
                text: 'kcal',
                font: { size: 11 },
            },
        },
    },
}));

/* ---------- Activity charts ---------- */

const activityChartData = computed(() => ({
    labels: props.activityMonthly.map((r) => fmtMonth(r.month)),
    datasets: [
        {
            label: 'Séances de gym',
            data: props.activityMonthly.map((r) => Number(r.total_gym) || 0),
            backgroundColor: 'rgba(139,92,246,0.7)',
            borderColor: '#8b5cf6',
            borderWidth: 1,
            borderRadius: 4,
        },
    ],
}));

const activityChartOpts = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(15,23,42,0.9)',
            titleColor: '#f8fafc',
            bodyColor: '#e2e8f0',
            padding: 12,
            cornerRadius: 8,
        },
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        y: {
            beginAtZero: true,
            ticks: { stepSize: 1, font: { size: 11 } },
            grid: { color: 'rgba(0,0,0,0.05)' },
        },
    },
}));

const stepsChartData = computed(() => ({
    labels: props.activityMonthly.map((r) => fmtMonth(r.month)),
    datasets: [
        {
            label: 'Pas',
            data: props.activityMonthly.map((r) => Number(r.total_steps) || 0),
            borderColor: '#0ea5e9',
            backgroundColor: 'rgba(14,165,233,0.12)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 7,
        },
    ],
}));

const stepsChartOpts = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(15,23,42,0.9)',
            titleColor: '#f8fafc',
            bodyColor: '#e2e8f0',
            padding: 12,
            cornerRadius: 8,
            callbacks: {
                label: (ctx) => `${Number(ctx.parsed.y).toLocaleString('fr-CA')} pas`,
            },
        },
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(0,0,0,0.05)' },
            ticks: {
                font: { size: 11 },
                callback: (v) => (v >= 1000 ? `${(v / 1000).toFixed(0)}k` : v),
            },
        },
    },
}));

/* ---------- Print ---------- */

function imprimer() {
    window.print();
}
</script>

<template>
    <Head title="Tableau de bord" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Tableau de bord santé
                </h2>
                <button
                    @click="imprimer"
                    class="inline-flex items-center gap-2 self-start rounded-lg bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 transition hover:bg-gray-50 hover:ring-gray-400 print:hidden"
                >
                    <!-- Download icon -->
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Télécharger en PDF
                </button>
            </div>
        </template>

        <div class="py-6 print:py-0">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

                <!-- ============================================================ -->
                <!--  KPI Cards                                                    -->
                <!-- ============================================================ -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <div
                        v-for="k in kpiCards"
                        :key="k.label"
                        class="flex flex-col items-center gap-1 rounded-xl p-4 text-center shadow-sm ring-1 ring-gray-200 transition hover:shadow-md"
                        :class="k.bg"
                    >
                        <span class="text-2xl">{{ k.emoji }}</span>
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ k.label }}</span>
                        <span class="text-lg font-bold" :class="k.color">{{ k.value }}</span>
                    </div>
                </div>

                <HealthInsightsPanel :insights="healthInsights" />

                <!-- ============================================================ -->
                <!--  Weight Evolution                                             -->
                <!-- ============================================================ -->
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6">
                    <h3 class="mb-1 text-base font-semibold text-gray-800 sm:text-lg">
                        ⚖️ Évolution du poids
                    </h3>
                    <p class="mb-4 text-xs text-gray-500">Relevés de poids quotidiens</p>
                    <div class="h-64 sm:h-72">
                        <Line v-if="weightReadings.length" :data="weightChartData" :options="weightChartOpts" />
                        <p v-else class="pt-16 text-center text-sm text-gray-400">Aucune donnée de poids pour le moment.</p>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Body Measurements (2 cols on large)                          -->
                <!-- ============================================================ -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6">
                        <h3 class="mb-1 text-base font-semibold text-gray-800 sm:text-lg">
                            📏 Mensurations corporelles
                        </h3>
                        <p class="mb-4 text-xs text-gray-500">Taille, hanches et abdomen</p>
                        <div class="h-64 sm:h-72">
                            <Line v-if="bodyMeasurements.length" :data="measurementsChartData" :options="defaultOpts('', 'cm')" />
                            <p v-else class="pt-16 text-center text-sm text-gray-400">Aucune mesure pour le moment.</p>
                        </div>
                    </div>

                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6">
                        <h3 class="mb-1 text-base font-semibold text-gray-800 sm:text-lg">
                            📊 Rapport Taille / Hanches
                        </h3>
                        <p class="mb-4 text-xs text-gray-500">WHR — la ligne rouge indique le seuil de risque à 0.90</p>
                        <div class="h-64 sm:h-72">
                            <Line v-if="bodyMeasurements.length" :data="whrChartData" :options="whrChartOpts" />
                            <p v-else class="pt-16 text-center text-sm text-gray-400">Aucune mesure pour le moment.</p>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Nutrition (2 cols on large)                                  -->
                <!-- ============================================================ -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6">
                        <h3 class="mb-1 text-base font-semibold text-gray-800 sm:text-lg">
                            🍽️ Nutrition mensuelle
                        </h3>
                        <p class="mb-4 text-xs text-gray-500">Moyennes mensuelles (calories, protéines, lipides, glucides)</p>
                        <div class="h-64 sm:h-72">
                            <Bar v-if="nutritionMonthly.length" :data="nutritionMonthlyChartData" :options="nutritionMonthlyOpts" />
                            <p v-else class="pt-16 text-center text-sm text-gray-400">Aucune donnée nutritionnelle mensuelle.</p>
                        </div>
                    </div>

                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6">
                        <h3 class="mb-1 text-base font-semibold text-gray-800 sm:text-lg">
                            📅 Calories par jour (30 derniers jours)
                        </h3>
                        <p class="mb-4 text-xs text-gray-500">Apport calorique quotidien</p>
                        <div class="h-64 sm:h-72">
                            <Bar v-if="dailyNutritionLogs.length" :data="dailyNutritionChartData" :options="dailyNutritionOpts" />
                            <p v-else class="pt-16 text-center text-sm text-gray-400">Aucune donnée pour les 30 derniers jours.</p>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Activity (2 cols on large)                                   -->
                <!-- ============================================================ -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6">
                        <h3 class="mb-1 text-base font-semibold text-gray-800 sm:text-lg">
                            💪 Séances de gym
                        </h3>
                        <p class="mb-4 text-xs text-gray-500">Nombre de séances par mois</p>
                        <div class="h-64 sm:h-72">
                            <Bar v-if="activityMonthly.length" :data="activityChartData" :options="activityChartOpts" />
                            <p v-else class="pt-16 text-center text-sm text-gray-400">Aucune activité enregistrée.</p>
                        </div>
                    </div>

                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6">
                        <h3 class="mb-1 text-base font-semibold text-gray-800 sm:text-lg">
                            🚶 Pas par mois
                        </h3>
                        <p class="mb-4 text-xs text-gray-500">Nombre total de pas mensuel</p>
                        <div class="h-64 sm:h-72">
                            <Line v-if="activityMonthly.length" :data="stepsChartData" :options="stepsChartOpts" />
                            <p v-else class="pt-16 text-center text-sm text-gray-400">Aucune donnée de pas.</p>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Technologies                                                  -->
                <!-- ============================================================ -->
                <div class="rounded-xl bg-gradient-to-br from-slate-50 to-gray-100 p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="mb-4 text-center text-base font-semibold text-gray-800 sm:text-lg">
                        🔗 Technologies et sources de données
                    </h3>
                    <div class="flex flex-wrap justify-center gap-4 sm:gap-6">
                        <div class="flex flex-col items-center gap-1">
                            <span class="text-2xl">🌿</span>
                            <span class="text-xs font-medium text-gray-600">Arboleaf</span>
                        </div>
                        <div class="flex flex-col items-center gap-1">
                            <span class="text-2xl">📏</span>
                            <span class="text-xs font-medium text-gray-600">Smart Tape</span>
                        </div>
                        <div class="flex flex-col items-center gap-1">
                            <span class="text-2xl">⌚</span>
                            <span class="text-xs font-medium text-gray-600">Garmin</span>
                        </div>
                        <div class="flex flex-col items-center gap-1">
                            <span class="text-2xl">🥗</span>
                            <span class="text-xs font-medium text-gray-600">FatSecret</span>
                        </div>
                        <div class="flex flex-col items-center gap-1">
                            <span class="text-2xl">🤖</span>
                            <span class="text-xs font-medium text-gray-600">C-3PO</span>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Footer                                                       -->
                <!-- ============================================================ -->
                <p class="pb-4 text-center text-xs text-gray-400 print:pb-0">
                    Généré par C-3PO · Tableau de bord santé · {{ new Date().toLocaleDateString('fr-CA') }}
                </p>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    body {
        background: white !important;
    }
    .print\:hidden {
        display: none !important;
    }
    .print\:py-0 {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }
    .print\:pb-0 {
        padding-bottom: 0 !important;
    }
    nav,
    header {
        display: none !important;
    }
    [class*='shadow'] {
        box-shadow: none !important;
    }
    [class*='ring'] {
        --tw-ring-offset-shadow: none !important;
        --tw-ring-shadow: none !important;
        box-shadow: none !important;
    }
}
</style>
