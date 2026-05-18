<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    insights: {
        type: Object,
        default: null,
    },
});

const refreshing = ref(false);

const grouped = computed(() => {
    const items = props.insights?.items ?? [];
    const order = ['insight', 'recommendation', 'suggestion', 'comment'];
    const labels = {
        insight: 'Constats',
        recommendation: 'Recommandations',
        suggestion: 'Suggestions',
        comment: 'Commentaires',
    };
    const icons = {
        insight: '💡',
        recommendation: '✅',
        suggestion: '💭',
        comment: '💬',
    };
    const colors = {
        insight: 'border-sky-200 bg-sky-50',
        recommendation: 'border-emerald-200 bg-emerald-50',
        suggestion: 'border-violet-200 bg-violet-50',
        comment: 'border-gray-200 bg-gray-50',
    };

    return order
        .map((type) => ({
            type,
            label: labels[type],
            icon: icons[type],
            color: colors[type],
            items: items.filter((i) => i.type === type),
        }))
        .filter((g) => g.items.length > 0);
});

const providerLabel = computed(() => {
    if (!props.insights) return '';
    return props.insights.provider === 'openai'
        ? 'Analyse IA · BMad (Mary)'
        : 'Analyse automatique (règles)';
});

function refresh(ai = true) {
    refreshing.value = true;
    router.post(
        route('insights.refresh'),
        { ai: ai ? 1 : 0 },
        {
            preserveScroll: true,
            onFinish: () => {
                refreshing.value = false;
            },
        },
    );
}
</script>

<template>
    <section
        v-if="insights"
        class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6 print:break-inside-avoid"
    >
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h3 class="flex items-center gap-2 text-base font-semibold text-gray-800 sm:text-lg">
                    <span>📊</span>
                    Analyse &amp; recommandations
                </h3>
                <p class="mt-1 text-xs text-gray-500">
                    {{ providerLabel }}
                    <span v-if="insights.generated_at_human">
                        · {{ insights.generated_at_human }}
                    </span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2 print:hidden">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 ring-1 ring-gray-200 transition hover:bg-gray-200 disabled:opacity-50"
                    :disabled="refreshing"
                    @click="refresh(false)"
                >
                    Actualiser
                </button>
                <button
                    v-if="insights.can_refresh_ai"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition hover:bg-violet-700 disabled:opacity-50"
                    :disabled="refreshing"
                    @click="refresh(true)"
                >
                    <span v-if="refreshing">Génération…</span>
                    <span v-else>Analyse IA (BMad)</span>
                </button>
            </div>
        </div>

        <p
            v-if="insights.summary"
            class="mb-5 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm leading-relaxed text-slate-700"
        >
            {{ insights.summary }}
        </p>

        <div class="space-y-6">
            <div
                v-for="group in grouped"
                :key="group.type"
            >
                <h4 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-gray-600">
                    <span>{{ group.icon }}</span>
                    {{ group.label }}
                </h4>
                <ul class="grid gap-3 sm:grid-cols-2">
                    <li
                        v-for="(item, idx) in group.items"
                        :key="`${group.type}-${idx}`"
                        class="rounded-lg border p-4"
                        :class="group.color"
                    >
                        <div class="mb-1 flex items-start justify-between gap-2">
                            <span class="font-medium text-gray-900">{{ item.title }}</span>
                            <span
                                class="mt-1 h-2 w-2 shrink-0 rounded-full"
                                :class="{
                                    'bg-red-500': item.priority === 'high',
                                    'bg-amber-500': item.priority === 'medium',
                                    'bg-gray-400': item.priority === 'low',
                                }"
                                :title="item.priority"
                            />
                        </div>
                        <p class="text-sm leading-relaxed text-gray-700">{{ item.body }}</p>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>
