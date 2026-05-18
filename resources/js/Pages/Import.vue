<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';

const form = useForm({
    file: null,
    type: 'fatsecret',
});

const isDragging = ref(false);
const fileInputRef = ref(null);

/* ------------------------------------------------------------------ */
/*  Flash messages from shared Inertia props                           */
/* ------------------------------------------------------------------ */
const page = usePage();
const flash = computed(() => page.props.flash ?? {});

/* ------------------------------------------------------------------ */
/*  Drag & drop handlers                                               */
/* ------------------------------------------------------------------ */
function onDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    isDragging.value = true;
}

function onDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    isDragging.value = false;
}

function onDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    isDragging.value = false;
    const files = e.dataTransfer.files;
    if (files.length) {
        form.file = files[0];
    }
}

function onFileSelect(e) {
    const files = e.target.files;
    if (files.length) {
        form.file = files[0];
    }
}

function removeFile() {
    form.file = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

/* ------------------------------------------------------------------ */
/*  Form submission                                                    */
/* ------------------------------------------------------------------ */
function submit() {
    form.post(route('import.csv'), {
        preserveScroll: true,
        onSuccess: () => {
            removeFile();
        },
    });
}

/* ------------------------------------------------------------------ */
/*  Helpers                                                            */
/* ------------------------------------------------------------------ */
function formatFileSize(bytes) {
    if (!bytes) return '';
    if (bytes < 1024) return bytes + ' octets';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko';
    return (bytes / (1024 * 1024)).toFixed(1) + ' Mo';
}

/* ------------------------------------------------------------------ */
/*  Example format reference                                           */
/* ------------------------------------------------------------------ */
const exampleData = computed(() => {
    const examples = {
        fatsecret: {
            title: 'FatSecret — Journal alimentaire',
            description:
                'Exportez votre journal alimentaire depuis FatSecret → Mes aliments → Journal → Exporter au format CSV. Le fichier contient les calories, lipides, glucides et protéines pour chaque jour.',
            columns: ['Date', 'Calories', 'Lipides (g)', 'Glucides (g)', 'Protéines (g)'],
            sample: '"Friday, February 20, 2026",1744,62.7,210.26,76.23',
        },
        garmin: {
            title: 'Garmin Connect — Activités',
            description:
                'Depuis Garmin Connect, exportez vos résumés mensuels d\'activités. Le fichier doit lister le mois, le type d\'activité et le nombre de séances.',
            columns: ['Mois', "Type d'activité", 'Séances'],
            sample: 'Jan 2026, Gym & Fitness Equipment,6',
        },
    };
    return examples[form.type] || examples.fatsecret;
});
</script>

<template>
    <Head title="Importer des données" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('dashboard')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm ring-1 ring-gray-300 transition hover:bg-gray-50 hover:text-gray-800"
                    >
                        <!-- Arrow left -->
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Tableau de bord
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Importer des données
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">

                <!-- ============================================================ -->
                <!--  Flash: Success                                               -->
                <!-- ============================================================ -->
                <div
                    v-if="flash.success"
                    class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm"
                >
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 text-xl">✅</span>
                        <div>
                            <h3 class="font-semibold text-emerald-800">Importation réussie</h3>
                            <p class="mt-1 text-sm text-emerald-700">{{ flash.success }}</p>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Flash: Error                                                 -->
                <!-- ============================================================ -->
                <div
                    v-if="flash.error"
                    class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm"
                >
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 text-xl">❌</span>
                        <div>
                            <h3 class="font-semibold text-red-800">Erreur</h3>
                            <p class="mt-1 text-sm text-red-700">{{ flash.error }}</p>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Main Import Card                                             -->
                <!-- ============================================================ -->
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6">
                    <h3 class="mb-1 text-base font-semibold text-gray-800 sm:text-lg">
                        📥 Importation CSV
                    </h3>
                    <p class="mb-6 text-sm text-gray-500">
                        Sélectionnez la source de données et téléversez votre fichier CSV
                        pour importer vos mesures dans le tableau de bord.
                    </p>

                    <form @submit.prevent="submit" enctype="multipart/form-data">

                        <!-- ================================================== -->
                        <!--  Source type (radio cards)                          -->
                        <!-- ================================================== -->
                        <div class="mb-6">
                            <label class="mb-3 block text-sm font-medium text-gray-700">
                                Source des données
                            </label>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <!-- FatSecret -->
                                <label
                                    class="relative flex cursor-pointer items-center gap-3 rounded-xl border-2 p-4 transition"
                                    :class="
                                        form.type === 'fatsecret'
                                            ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-200'
                                            : 'border-gray-200 bg-white hover:border-indigo-300'
                                    "
                                >
                                    <input
                                        type="radio"
                                        name="type"
                                        value="fatsecret"
                                        v-model="form.type"
                                        class="absolute opacity-0"
                                    />
                                    <span class="text-2xl">🥗</span>
                                    <div>
                                        <span class="block text-sm font-semibold text-gray-800">FatSecret</span>
                                        <span class="block text-xs text-gray-500">Journal alimentaire</span>
                                    </div>
                                    <span
                                        v-if="form.type === 'fatsecret'"
                                        class="ml-auto flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-500 text-xs text-white"
                                    >
                                        ✓
                                    </span>
                                </label>

                                <!-- Garmin -->
                                <label
                                    class="relative flex cursor-pointer items-center gap-3 rounded-xl border-2 p-4 transition"
                                    :class="
                                        form.type === 'garmin'
                                            ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-200'
                                            : 'border-gray-200 bg-white hover:border-indigo-300'
                                    "
                                >
                                    <input
                                        type="radio"
                                        name="type"
                                        value="garmin"
                                        v-model="form.type"
                                        class="absolute opacity-0"
                                    />
                                    <span class="text-2xl">⌚</span>
                                    <div>
                                        <span class="block text-sm font-semibold text-gray-800">Garmin Connect</span>
                                        <span class="block text-xs text-gray-500">Activités</span>
                                    </div>
                                    <span
                                        v-if="form.type === 'garmin'"
                                        class="ml-auto flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-500 text-xs text-white"
                                    >
                                        ✓
                                    </span>
                                </label>
                            </div>

                            <InputError :message="form.errors.type" class="mt-2" />
                        </div>

                        <!-- ================================================== -->
                        <!--  File upload — drag & drop or browse               -->
                        <!-- ================================================== -->
                        <div class="mb-6">
                            <label class="mb-3 block text-sm font-medium text-gray-700">
                                Fichier CSV
                            </label>

                            <!-- Drop zone (visible when no file selected) -->
                            <div
                                v-if="!form.file"
                                @dragover="onDragOver"
                                @dragleave="onDragLeave"
                                @drop="onDrop"
                                class="relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed p-8 text-center transition"
                                :class="
                                    isDragging
                                        ? 'border-indigo-400 bg-indigo-50'
                                        : 'border-gray-300 bg-gray-50 hover:border-gray-400 hover:bg-gray-100'
                                "
                            >
                                <span class="mb-3 text-4xl">📄</span>
                                <p class="text-sm font-medium text-gray-700">
                                    Glissez-déposez votre fichier CSV ici
                                </p>
                                <p class="mt-1 text-xs text-gray-500">ou</p>

                                <label
                                    class="mt-3 inline-flex cursor-pointer items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 transition hover:bg-gray-50"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    Parcourir…
                                    <input
                                        ref="fileInputRef"
                                        type="file"
                                        accept=".csv,.txt"
                                        class="hidden"
                                        @input="onFileSelect"
                                    />
                                </label>

                                <p class="mt-3 text-xs text-gray-400">CSV seulement · Max 2 Mo</p>
                            </div>

                            <!-- File preview (visible when file selected) -->
                            <div
                                v-else
                                class="flex items-center gap-4 rounded-xl border-2 border-emerald-200 bg-emerald-50 p-4"
                            >
                                <span class="text-2xl">📎</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-800">
                                        {{ form.file.name }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ formatFileSize(form.file.size) }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="removeFile"
                                    class="inline-flex shrink-0 items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-100"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Retirer
                                </button>
                            </div>

                            <InputError :message="form.errors.file" class="mt-2" />
                        </div>

                        <!-- ================================================== -->
                        <!--  Submit button                                     -->
                        <!-- ================================================== -->
                        <div class="flex items-center gap-4">
                            <button
                                type="submit"
                                :disabled="!form.file || form.processing"
                                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <!-- Spinner -->
                                <svg
                                    v-if="form.processing"
                                    class="h-4 w-4 animate-spin"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                </svg>
                                <!-- Upload icon -->
                                <svg
                                    v-else
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                {{ form.processing ? 'Importation en cours…' : 'Importer le fichier' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ============================================================ -->
                <!--  Example formats                                              -->
                <!-- ============================================================ -->
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-base font-semibold text-gray-800 sm:text-lg">
                            📋 Format attendu
                        </h3>
                        <!-- Quick-type switcher -->
                        <div class="flex gap-2">
                            <button
                                @click="form.type = 'fatsecret'"
                                class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                                :class="
                                    form.type === 'fatsecret'
                                        ? 'bg-indigo-100 text-indigo-700'
                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                "
                            >
                                🥗 FatSecret
                            </button>
                            <button
                                @click="form.type = 'garmin'"
                                class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                                :class="
                                    form.type === 'garmin'
                                        ? 'bg-indigo-100 text-indigo-700'
                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                "
                            >
                                ⌚ Garmin
                            </button>
                        </div>
                    </div>

                    <p class="mb-4 text-sm text-gray-600">
                        {{ exampleData.description }}
                    </p>

                    <!-- Columns (numbered list) -->
                    <div class="mb-2 overflow-hidden rounded-lg border border-gray-200">
                        <div class="bg-gray-50 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Colonnes attendues
                        </div>
                        <div class="divide-y divide-gray-100 px-4 py-2">
                            <div
                                v-for="(col, i) in exampleData.columns"
                                :key="i"
                                class="flex items-center gap-2 py-1.5 text-sm"
                            >
                                <span
                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-medium text-indigo-600"
                                >
                                    {{ i + 1 }}
                                </span>
                                <code class="font-mono text-gray-700">{{ col }}</code>
                            </div>
                        </div>
                    </div>

                    <!-- Sample line -->
                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <div class="bg-gray-50 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Exemple de ligne
                        </div>
                        <div class="px-4 py-3">
                            <code class="block break-all rounded bg-amber-50 px-3 py-2 font-mono text-xs text-amber-800">
                                {{ exampleData.sample }}
                            </code>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Tips card                                                   -->
                <!-- ============================================================ -->
                <div class="rounded-xl bg-gradient-to-br from-indigo-50 to-blue-50 p-4 shadow-sm ring-1 ring-indigo-100 sm:p-6">
                    <h3 class="mb-3 text-base font-semibold text-indigo-800 sm:text-lg">
                        💡 Conseils
                    </h3>
                    <ul class="space-y-2 text-sm text-indigo-700">
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5">📌</span>
                            <span>Assurez-vous que votre fichier est au format CSV (séparateur : virgule).</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5">📌</span>
                            <span>Les exportations FatSecret et Garmin sont automatiquement reconnues.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5">📌</span>
                            <span>Les doublons (même date) sont mis à jour, pas dupliqués.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5">📌</span>
                            <span>Taille maximale du fichier : 2 Mo.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5">📌</span>
                            <span>Après l&rsquo;import, les données apparaissent immédiatement dans le tableau de bord.</span>
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
