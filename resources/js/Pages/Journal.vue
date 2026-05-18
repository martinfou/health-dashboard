<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';

/* ------------------------------------------------------------------ */
/*  Props                                                              */
/* ------------------------------------------------------------------ */

const props = defineProps({
    entries: {
        type: Array,
        default: () => [],
    },
    today: {
        type: Object,
        default: null,
    },
});

/* ------------------------------------------------------------------ */
/*  Form                                                               */
/* ------------------------------------------------------------------ */

const form = useForm({
    energy_level: props.today?.energy_level ?? 5,
    sleep_quality: props.today?.sleep_quality ?? 5,
    mood: props.today?.mood ?? 5,
    gratitude: props.today?.gratitude ?? '',
    intention: props.today?.intention ?? '',
    notes: props.today?.notes ?? '',
    stoic_reflection: props.today?.stoic_reflection ?? '',
});

function submit() {
    form.post(route('journal.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('gratitude', 'intention', 'notes', 'stoic_reflection');
        },
    });
}

const submitLabel = computed(() =>
    props.today ? 'Mettre à jour' : 'Enregistrer',
);

/* ------------------------------------------------------------------ */
/*  Delete entry                                                       */
/* ------------------------------------------------------------------ */

const entryToDelete = ref(null);
const deleteForm = useForm({});
const showDeleteModal = ref(false);

function askDelete(entry) {
    entryToDelete.value = entry;
    showDeleteModal.value = true;
}

function confirmDelete() {
    if (!entryToDelete.value) return;
    deleteForm.delete(route('journal.destroy', { entry: entryToDelete.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            entryToDelete.value = null;
        },
    });
}

function cancelDelete() {
    showDeleteModal.value = false;
    entryToDelete.value = null;
}

/* ------------------------------------------------------------------ */
/*  Past entries expandable                                            */
/* ------------------------------------------------------------------ */

const expandedIds = ref(new Set());

function toggleExpand(id) {
    if (expandedIds.value.has(id)) {
        expandedIds.value.delete(id);
    } else {
        expandedIds.value.add(id);
    }
}

/* ------------------------------------------------------------------ */
/*  Helpers                                                            */
/* ------------------------------------------------------------------ */

function formatDate(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('fr-CA', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function formatDateShort(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('fr-CA', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
}

/* ------------------------------------------------------------------ */
/*  Emoji helpers for ratings                                          */
/* ------------------------------------------------------------------ */

function energyEmoji(val) {
    if (val <= 3) return '😴';
    if (val <= 6) return '🙂';
    if (val <= 9) return '💪';
    return '⚡';
}

function sleepEmoji(val) {
    if (val <= 3) return '🫨';
    if (val <= 6) return '😐';
    return '😴';
}

function moodEmoji(val) {
    if (val <= 2) return '😢';
    if (val <= 4) return '😐';
    if (val <= 6) return '🙂';
    if (val <= 8) return '😊';
    return '🥰';
}

/* ------------------------------------------------------------------ */
/*  Rating bar: clickable 1-10 buttons                                 */
/* ------------------------------------------------------------------ */

const ratingLabels = [
    null, '1', '2', '3', '4', '5', '6', '7', '8', '9', '10',
];

function ratingClass(field, val) {
    const v = form[field];
    if (v === val) return 'bg-indigo-500 text-white shadow-md scale-110';
    if (v >= val) return 'bg-indigo-100 text-indigo-700';
    return 'bg-gray-100 text-gray-500 hover:bg-gray-200';
}

/* ------------------------------------------------------------------ */
/*  Has any journal content?                                           */
/* ------------------------------------------------------------------ */

const hasEntries = computed(() => props.entries.length > 0);

const todayExists = computed(() => props.today !== null);
</script>

<template>
    <Head title="Journal de bien-être" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Journal de bien-être
                </h2>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">

                <!-- ============================================================ -->
                <!--  Today's Entry Form                                           -->
                <!-- ============================================================ -->
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6">
                    <div class="mb-1 flex items-center gap-2">
                        <span class="text-xl">📖</span>
                        <h3 class="text-base font-semibold text-gray-800 sm:text-lg">
                            {{ todayExists ? "Modifier l'entrée d'aujourd'hui" : "Nouvelle entrée du jour" }}
                        </h3>
                    </div>
                    <p class="mb-6 text-sm text-gray-400">
                        {{ formatDate(new Date().toISOString()) }}
                    </p>

                    <form @submit.prevent="submit">

                        <!-- ================================================== -->
                        <!--  Énergie (1-10)                                    -->
                        <!-- ================================================== -->
                        <div class="mb-5">
                            <label class="mb-2 block text-sm font-medium text-gray-700">
                                Niveau d'énergie
                            </label>
                            <div class="flex items-center justify-between rounded-xl bg-gradient-to-r from-blue-50 via-indigo-50 to-purple-50 p-1.5">
                                <span class="ml-1 mr-1 text-sm">😴</span>
                                <div class="flex flex-1 items-center justify-evenly gap-0.5">
                                    <button
                                        v-for="n in 10"
                                        :key="'energy-' + n"
                                        type="button"
                                        @click="form.energy_level = n"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-xs font-semibold transition-all duration-150"
                                        :class="ratingClass('energy_level', n)"
                                    >
                                        {{ n }}
                                    </button>
                                </div>
                                <span class="ml-1 mr-1 text-sm">⚡</span>
                            </div>
                            <div class="mt-1.5 flex items-center justify-between px-2">
                                <span class="text-xs text-blue-500">Faible</span>
                                <span class="text-xs font-medium text-indigo-600">
                                    {{ form.energy_level }}/10 {{ energyEmoji(form.energy_level) }}
                                </span>
                                <span class="text-xs text-purple-500">Élevé</span>
                            </div>
                            <InputError :message="form.errors.energy_level" class="mt-1" />
                        </div>

                        <!-- ================================================== -->
                        <!--  Sommeil (1-10)                                    -->
                        <!-- ================================================== -->
                        <div class="mb-5">
                            <label class="mb-2 block text-sm font-medium text-gray-700">
                                Qualité du sommeil
                            </label>
                            <div class="flex items-center justify-between rounded-xl bg-gradient-to-r from-slate-100 via-blue-50 to-indigo-50 p-1.5">
                                <span class="ml-1 mr-1 text-sm">🫨</span>
                                <div class="flex flex-1 items-center justify-evenly gap-0.5">
                                    <button
                                        v-for="n in 10"
                                        :key="'sleep-' + n"
                                        type="button"
                                        @click="form.sleep_quality = n"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-xs font-semibold transition-all duration-150"
                                        :class="ratingClass('sleep_quality', n)"
                                    >
                                        {{ n }}
                                    </button>
                                </div>
                                <span class="ml-1 mr-1 text-sm">😴</span>
                            </div>
                            <div class="mt-1.5 flex items-center justify-between px-2">
                                <span class="text-xs text-slate-500">Agité</span>
                                <span class="text-xs font-medium text-blue-600">
                                    {{ form.sleep_quality }}/10 {{ sleepEmoji(form.sleep_quality) }}
                                </span>
                                <span class="text-xs text-indigo-500">Réparateur</span>
                            </div>
                            <InputError :message="form.errors.sleep_quality" class="mt-1" />
                        </div>

                        <!-- ================================================== -->
                        <!--  Humeur (1-10)                                     -->
                        <!-- ================================================== -->
                        <div class="mb-5">
                            <label class="mb-2 block text-sm font-medium text-gray-700">
                                Humeur
                            </label>
                            <div class="flex items-center justify-between rounded-xl bg-gradient-to-r from-red-50 via-amber-50 to-emerald-50 p-1.5">
                                <span class="ml-1 mr-1 text-sm">😢</span>
                                <div class="flex flex-1 items-center justify-evenly gap-0.5">
                                    <button
                                        v-for="n in 10"
                                        :key="'mood-' + n"
                                        type="button"
                                        @click="form.mood = n"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-xs font-semibold transition-all duration-150"
                                        :class="ratingClass('mood', n)"
                                    >
                                        {{ n }}
                                    </button>
                                </div>
                                <span class="ml-1 mr-1 text-sm">😊</span>
                            </div>
                            <div class="mt-1.5 flex items-center justify-between px-2">
                                <span class="text-xs text-red-500">Triste</span>
                                <span class="text-xs font-medium text-amber-600">
                                    {{ form.mood }}/10 {{ moodEmoji(form.mood) }}
                                </span>
                                <span class="text-xs text-emerald-500">Heureux</span>
                            </div>
                            <InputError :message="form.errors.mood" class="mt-1" />
                        </div>

                        <!-- ================================================== -->
                        <!--  Gratitude                                        -->
                        <!-- ================================================== -->
                        <div class="mb-5">
                            <label
                                for="gratitude"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                🙏 De quoi suis-je reconnaissant ?
                            </label>
                            <textarea
                                id="gratitude"
                                v-model="form.gratitude"
                                rows="3"
                                placeholder="Prenez un moment pour noter ce qui vous rend reconnaissant aujourd'hui…"
                                class="block w-full resize-y rounded-xl border-gray-200 bg-gray-50 shadow-sm transition placeholder:text-gray-400 focus:border-indigo-400 focus:bg-white focus:ring focus:ring-indigo-200"
                            ></textarea>
                            <InputError :message="form.errors.gratitude" class="mt-1" />
                        </div>

                        <!-- ================================================== -->
                        <!--  Intention                                         -->
                        <!-- ================================================== -->
                        <div class="mb-5">
                            <label
                                for="intention"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                🎯 Mon intention pour aujourd'hui
                            </label>
                            <textarea
                                id="intention"
                                v-model="form.intention"
                                rows="2"
                                placeholder="Quelle est votre intention pour cette journée ?"
                                class="block w-full resize-y rounded-xl border-gray-200 bg-gray-50 shadow-sm transition placeholder:text-gray-400 focus:border-indigo-400 focus:bg-white focus:ring focus:ring-indigo-200"
                            ></textarea>
                            <InputError :message="form.errors.intention" class="mt-1" />
                        </div>

                        <!-- ================================================== -->
                        <!--  Stoic Reflection (optional)                       -->
                        <!-- ================================================== -->
                        <div class="mb-5">
                            <label
                                for="stoic_reflection"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                🏛️ Réflexion stoïcienne
                                <span class="ml-1 text-xs font-normal text-gray-400">(optionnel)</span>
                            </label>
                            <textarea
                                id="stoic_reflection"
                                v-model="form.stoic_reflection"
                                rows="2"
                                placeholder="Une pensée stoïcienne qui vous accompagne aujourd'hui…"
                                class="block w-full resize-y rounded-xl border-gray-200 bg-gray-50 shadow-sm transition placeholder:text-gray-400 focus:border-indigo-400 focus:bg-white focus:ring focus:ring-indigo-200"
                            ></textarea>
                            <InputError :message="form.errors.stoic_reflection" class="mt-1" />
                        </div>

                        <!-- ================================================== -->
                        <!--  Notes libres                                      -->
                        <!-- ================================================== -->
                        <div class="mb-6">
                            <label
                                for="notes"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                📝 Notes libres
                            </label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                placeholder="Tout ce que vous souhaitez noter… vos pensées, vos émotions, votre journée."
                                class="block w-full resize-y rounded-xl border-gray-200 bg-gray-50 shadow-sm transition placeholder:text-gray-400 focus:border-indigo-400 focus:bg-white focus:ring focus:ring-indigo-200"
                            ></textarea>
                            <InputError :message="form.errors.notes" class="mt-1" />
                        </div>

                        <!-- ================================================== -->
                        <!--  Submit                                            -->
                        <!-- ================================================== -->
                        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
                            <button
                                type="submit"
                                :disabled="form.processing"
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
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V4a4 4 0 00-4 4H4z" />
                                </svg>
                                <!-- Save icon -->
                                <svg
                                    v-else
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.processing ? 'Enregistrement…' : submitLabel }}
                            </button>

                            <button
                                v-if="todayExists"
                                type="button"
                                @click="form.reset('energy_level', 'sleep_quality', 'mood', 'gratitude', 'intention', 'notes', 'stoic_reflection')"
                                class="rounded-lg px-4 py-2.5 text-sm font-medium text-gray-500 transition hover:bg-gray-100"
                            >
                                Réinitialiser
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ============================================================ -->
                <!--  Past Entries                                                 -->
                <!-- ============================================================ -->
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6">
                    <div class="mb-1 flex items-center gap-2">
                        <span class="text-xl">📚</span>
                        <h3 class="text-base font-semibold text-gray-800 sm:text-lg">
                            Entrées passées
                        </h3>
                    </div>

                    <!-- Empty state -->
                    <div
                        v-if="!hasEntries"
                        class="flex flex-col items-center py-14 text-center"
                    >
                        <span class="mb-3 text-5xl opacity-40">📓</span>
                        <p class="text-sm font-medium text-gray-500">
                            Aucune entrée pour le moment
                        </p>
                        <p class="mt-1 text-xs text-gray-400">
                            Commencez par écrire votre première entrée ci-dessus.
                        </p>
                    </div>

                    <!-- Entries list -->
                    <div v-else class="divide-y divide-gray-100">
                        <div
                            v-for="entry in entries"
                            :key="entry.id"
                            class="group py-3 first:pt-0 last:pb-0"
                        >
                            <!-- Entry header — always visible -->
                            <div
                                class="flex cursor-pointer items-center gap-3 rounded-xl p-3 transition"
                                :class="
                                    expandedIds.has(entry.id)
                                        ? 'bg-indigo-50'
                                        : 'hover:bg-gray-50'
                                "
                                @click="toggleExpand(entry.id)"
                            >
                                <!-- Expand/collapse chevron -->
                                <svg
                                    class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200"
                                    :class="{ 'rotate-90': expandedIds.has(entry.id) }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>

                                <!-- Date -->
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-800">
                                        {{ formatDateShort(entry.entry_date) }}
                                    </p>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                        <span v-if="entry.energy_level">⚡ {{ entry.energy_level }}/10</span>
                                        <span v-if="entry.sleep_quality">😴 {{ entry.sleep_quality }}/10</span>
                                        <span v-if="entry.mood">
                                            {{ moodEmoji(entry.mood) }} {{ entry.mood }}/10
                                        </span>
                                    </div>
                                </div>

                                <!-- Delete button -->
                                <button
                                    type="button"
                                    @click.stop="askDelete(entry)"
                                    class="shrink-0 rounded-lg p-2 text-gray-400 opacity-0 transition hover:bg-red-50 hover:text-red-500 group-hover:opacity-100 focus:opacity-100 focus:outline-none"
                                    title="Supprimer cette entrée"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Expanded details -->
                            <div
                                v-if="expandedIds.has(entry.id)"
                                class="overflow-hidden transition-all"
                            >
                                <div class="ml-7 space-y-3 pb-2 pl-1 pr-3">
                                    <!-- Ratings recap -->
                                    <div class="mt-2 grid grid-cols-3 gap-2">
                                        <div class="rounded-lg bg-blue-50 p-2.5 text-center">
                                            <span class="block text-xs text-blue-500">Énergie</span>
                                            <span class="block text-lg font-semibold text-blue-700">
                                                {{ entry.energy_level }}/10
                                            </span>
                                            <span class="block text-xs text-blue-400">{{ energyEmoji(entry.energy_level) }}</span>
                                        </div>
                                        <div class="rounded-lg bg-indigo-50 p-2.5 text-center">
                                            <span class="block text-xs text-indigo-500">Sommeil</span>
                                            <span class="block text-lg font-semibold text-indigo-700">
                                                {{ entry.sleep_quality }}/10
                                            </span>
                                            <span class="block text-xs text-indigo-400">{{ sleepEmoji(entry.sleep_quality) }}</span>
                                        </div>
                                        <div class="rounded-lg bg-amber-50 p-2.5 text-center">
                                            <span class="block text-xs text-amber-500">Humeur</span>
                                            <span class="block text-lg font-semibold text-amber-700">
                                                {{ entry.mood }}/10
                                            </span>
                                            <span class="block text-xs text-amber-400">{{ moodEmoji(entry.mood) }}</span>
                                        </div>
                                    </div>

                                    <!-- Gratitude -->
                                    <div v-if="entry.gratitude" class="rounded-lg bg-emerald-50 p-3">
                                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-emerald-600">
                                            🙏 Reconnaissance
                                        </p>
                                        <p class="text-sm text-emerald-800 whitespace-pre-line">{{ entry.gratitude }}</p>
                                    </div>

                                    <!-- Intention -->
                                    <div v-if="entry.intention" class="rounded-lg bg-sky-50 p-3">
                                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-sky-600">
                                            🎯 Intention
                                        </p>
                                        <p class="text-sm text-sky-800 whitespace-pre-line">{{ entry.intention }}</p>
                                    </div>

                                    <!-- Stoic Reflection -->
                                    <div v-if="entry.stoic_reflection" class="rounded-lg bg-violet-50 p-3">
                                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-violet-600">
                                            🏛️ Réflexion stoïcienne
                                        </p>
                                        <p class="text-sm text-violet-800 italic whitespace-pre-line">{{ entry.stoic_reflection }}</p>
                                    </div>

                                    <!-- Notes -->
                                    <div v-if="entry.notes" class="rounded-lg bg-gray-50 p-3">
                                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            📝 Notes
                                        </p>
                                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ entry.notes }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Footer                                                       -->
                <!-- ============================================================ -->
                <p class="pb-4 text-center text-xs text-gray-400">
                    Journal de bien-être · C-3PO 🤖
                </p>

            </div>
        </div>
    </AuthenticatedLayout>

    <!-- ================================================================ -->
    <!--  Delete Confirmation Modal                                       -->
    <!-- ================================================================ -->
    <Modal :show="showDeleteModal" :closeable="true" @close="cancelDelete">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Supprimer l'entrée
                    </h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Êtes-vous sûr de vouloir supprimer l'entrée du
                        <strong class="font-medium text-gray-800">
                            {{ entryToDelete ? formatDate(entryToDelete.entry_date) : '' }}
                        </strong>
                        ?
                    </p>
                    <p class="mt-1 text-xs text-gray-400">
                        Cette action est irréversible.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                <button
                    type="button"
                    @click="cancelDelete"
                    class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 transition hover:bg-gray-50"
                >
                    Annuler
                </button>
                <button
                    type="button"
                    @click="confirmDelete"
                    :disabled="deleteForm.processing"
                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <svg
                        v-if="deleteForm.processing"
                        class="h-4 w-4 animate-spin"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V4a4 4 0 00-4 4H4z" />
                    </svg>
                    <svg
                        v-else
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                    {{ deleteForm.processing ? 'Suppression…' : 'Supprimer' }}
                </button>
            </div>
        </div>
    </Modal>
</template>
