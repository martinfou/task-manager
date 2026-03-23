<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    hasGoogleTasksConnection: { type: Boolean, default: false },
    userTimezone: { type: String, default: 'America/Toronto' },
});

const { t } = useI18n();

// --- State ---
const rangeDays = ref(30);
const loading = ref(false);
const stats = ref(null);
const error = ref(null);
const showHowMetrics = ref(false);
const showDataTable = ref(false);
const weekdayOpen = ref(false);
const leadTimeOpen = ref(false);
const dueDisciplineOpen = ref(false);

const RANGES = [7, 14, 30, 90];

// --- Fetch ---
async function fetchStats() {
    loading.value = true;
    error.value = null;
    try {
        const res = await fetch(route('dashboard.stats', { range: rangeDays.value }));
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        stats.value = await res.json();
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    fetchStats();
});

watch(rangeDays, () => {
    fetchStats();
});

// --- Computed ---
const isEmpty = computed(() => {
    if (!stats.value) return true;
    return stats.value.empty === true;
});

const isCached = computed(() => stats.value?.cached === true);

const insights = computed(() => stats.value?.insights ?? null);

const daily = computed(() => stats.value?.daily ?? []);

const weekday = computed(() => stats.value?.weekday ?? null);

const leadTime = computed(() => stats.value?.leadTime ?? null);

const dueDiscipline = computed(() => stats.value?.dueDiscipline ?? null);

const netFlowLabel = computed(() => {
    if (!insights.value) return '';
    const nf = insights.value.netFlow;
    if (nf > 0) return t('dashboard.insights.netFlowPositive');
    if (nf < 0) return t('dashboard.insights.netFlowNegative');
    return t('dashboard.insights.netFlowNeutral');
});

// Chart helpers
const chartMax = computed(() => {
    if (!daily.value.length) return 1;
    let max = 0;
    for (const d of daily.value) {
        max = Math.max(max, d.created, d.completed);
    }
    return max || 1;
});

function barHeight(val) {
    return Math.max(1, (val / chartMax.value) * 120);
}

function shortDate(dateStr) {
    const d = new Date(dateStr + 'T12:00:00');
    return `${d.getMonth() + 1}/${d.getDate()}`;
}

// Weekday bar helpers
const weekdayMax = computed(() => {
    if (!weekday.value?.days) return 1;
    return Math.max(1, ...weekday.value.days.map(d => d.count));
});

function weekdayBarWidth(count) {
    return Math.max(2, (count / weekdayMax.value) * 100);
}

const dueDisciplineTotal = computed(() => dueDiscipline.value?.total ?? 0);

function duePct(val) {
    if (!dueDisciplineTotal.value) return 0;
    return Math.round((val / dueDisciplineTotal.value) * 100);
}

const primaryLinkClass =
    'inline-flex items-center rounded-md border border-transparent bg-gt-accent-strong px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gt-accent-strong-hover focus:outline-none focus:ring-2 focus:ring-gt-accent-ring focus:ring-offset-2 focus:ring-offset-gt-raised active:opacity-90';

const secondaryLinkClass =
    'inline-flex items-center rounded-md border border-gt-border bg-gt-raised px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gt-ink-secondary shadow-sm transition duration-150 ease-in-out hover:bg-gt-field-muted focus:outline-none focus:ring-2 focus:ring-gt-accent-ring focus:ring-offset-2 focus:ring-offset-gt-raised dark:bg-gt-field-muted dark:text-gt-ink dark:hover:bg-gt-field';
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="gt-page-title">
                    {{ t('dashboard.title') }}
                </h2>
                <button
                    type="button"
                    class="text-xs text-gt-accent underline decoration-gt-accent/40 underline-offset-2 hover:text-gt-accent-hover"
                    @click="showHowMetrics = !showHowMetrics"
                >
                    {{ t('dashboard.howMetrics') }}
                </button>
            </div>
        </template>

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- How metrics work (collapsible) -->
                <div
                    v-if="showHowMetrics"
                    class="gt-surface rounded-lg p-4 text-sm text-gt-muted leading-relaxed"
                >
                    {{ t('dashboard.howMetricsBody', { tz: userTimezone }) }}
                </div>

                <!-- Header row: subtitle + range selector -->
                <div class="gt-surface rounded-lg p-4">
                    <p class="text-sm text-gt-muted mb-3">
                        {{ t('dashboard.subtitle') }}
                    </p>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-xs font-medium text-gt-ink-secondary">{{ t('dashboard.rangeLabel') }}</span>
                        <div class="inline-flex rounded-md border border-gt-border" role="radiogroup" :aria-label="t('dashboard.rangeLabel')">
                            <button
                                v-for="r in RANGES"
                                :key="r"
                                type="button"
                                role="radio"
                                :aria-checked="rangeDays === r"
                                class="px-3 py-1.5 text-xs font-medium transition-colors first:rounded-l-md last:rounded-r-md"
                                :class="rangeDays === r
                                    ? 'bg-gt-accent-strong text-white'
                                    : 'bg-gt-raised text-gt-ink-secondary hover:bg-gt-field-muted'"
                                @click="rangeDays = r"
                            >
                                {{ t(`dashboard.range${r}`) }}
                            </button>
                        </div>
                        <span class="ml-auto text-xs text-gt-muted">{{ t('dashboard.tzLabel', { tz: userTimezone }) }}</span>
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="gt-surface rounded-lg p-8 text-center text-gt-muted">
                    <p>{{ t('dashboard.loading') }}</p>
                </div>

                <!-- Error -->
                <div v-else-if="error" class="gt-surface rounded-lg p-8 text-center text-red-600 dark:text-red-400">
                    <p>{{ error }}</p>
                    <button class="mt-2 text-sm underline" @click="fetchStats">{{ t('tasks.retry') }}</button>
                </div>

                <!-- Empty states -->
                <div v-else-if="isEmpty" class="gt-surface rounded-lg p-8 text-center space-y-4">
                    <p class="text-gt-muted">
                        {{ !hasGoogleTasksConnection ? t('dashboard.emptyNoConnection') : t('dashboard.emptyNoData') }}
                    </p>
                    <div class="flex flex-wrap justify-center gap-3">
                        <Link
                            v-if="!hasGoogleTasksConnection"
                            :href="route('google.redirect')"
                            :class="primaryLinkClass"
                        >
                            {{ t('tasks.connectGoogle') }}
                        </Link>
                        <Link :href="route('tasks.index')" :class="secondaryLinkClass">
                            {{ t('dashboard.openTasks') }}
                        </Link>
                    </div>
                </div>

                <!-- Dashboard content -->
                <template v-else-if="stats && !isEmpty">

                    <!-- Stale disclaimer -->
                    <div v-if="isCached" class="rounded-lg border border-yellow-300 bg-yellow-50 p-3 text-xs text-yellow-800 dark:border-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300">
                        {{ t('dashboard.staleDisclaimer', { date: new Date(stats.cachedAt).toLocaleString() }) }}
                    </div>

                    <!-- Insight cards -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Period summary -->
                        <div class="gt-surface rounded-lg p-4 space-y-2">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gt-muted">{{ t('dashboard.insights.periodTitle') }}</h3>
                            <div class="flex items-baseline gap-4">
                                <div>
                                    <span class="text-2xl font-bold text-gt-ink">{{ insights.totalCreated }}</span>
                                    <span class="ml-1 text-xs text-gt-muted">{{ t('dashboard.insights.created') }}</span>
                                </div>
                                <div>
                                    <span class="text-2xl font-bold text-gt-ink">{{ insights.totalCompleted }}</span>
                                    <span class="ml-1 text-xs text-gt-muted">{{ t('dashboard.insights.completed') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Net flow -->
                        <div class="gt-surface rounded-lg p-4 space-y-2">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gt-muted">{{ t('dashboard.insights.netFlowTitle') }}</h3>
                            <p class="text-2xl font-bold" :class="insights.netFlow > 0 ? 'text-green-600 dark:text-green-400' : insights.netFlow < 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gt-ink'">
                                {{ insights.netFlow > 0 ? '+' : '' }}{{ insights.netFlow }}
                            </p>
                            <p class="text-xs text-gt-muted">{{ netFlowLabel }}</p>
                        </div>

                        <!-- Consistency -->
                        <div class="gt-surface rounded-lg p-4 space-y-2">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gt-muted">{{ t('dashboard.insights.consistencyTitle') }}</h3>
                            <p class="text-2xl font-bold text-gt-ink">
                                {{ t('dashboard.insights.activeDays', { count: insights.activeDays, total: insights.totalDays }) }}
                            </p>
                            <p class="text-xs text-gt-muted">{{ t('dashboard.insights.activeDaysExplain') }}</p>
                        </div>

                        <!-- vs Prior -->
                        <div class="gt-surface rounded-lg p-4 space-y-2">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gt-muted">{{ t('dashboard.insights.priorTitle', { days: rangeDays }) }}</h3>
                            <template v-if="insights.hasPrior">
                                <div class="space-y-1">
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-xs text-gt-muted">{{ t('dashboard.insights.priorCompletions') }}</span>
                                        <span
                                            class="text-sm font-semibold"
                                            :class="(insights.completionDelta ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'"
                                        >
                                            {{ insights.completionDelta !== null
                                                ? (insights.completionDelta >= 0
                                                    ? t('dashboard.insights.deltaUp', { pct: insights.completionDelta })
                                                    : t('dashboard.insights.deltaDown', { pct: insights.completionDelta }))
                                                : '—' }}
                                        </span>
                                    </div>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-xs text-gt-muted">{{ t('dashboard.insights.priorCreated') }}</span>
                                        <span
                                            class="text-sm font-semibold text-gt-ink"
                                        >
                                            {{ insights.creationDelta !== null
                                                ? (insights.creationDelta >= 0
                                                    ? t('dashboard.insights.deltaUp', { pct: insights.creationDelta })
                                                    : t('dashboard.insights.deltaDown', { pct: insights.creationDelta }))
                                                : '—' }}
                                        </span>
                                    </div>
                                </div>
                            </template>
                            <p v-else class="text-xs text-gt-muted">{{ t('dashboard.insights.noPrior') }}</p>
                        </div>
                    </div>

                    <!-- Throughput chart -->
                    <div class="gt-surface rounded-lg p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gt-ink">{{ t('dashboard.chart.title') }}</h3>
                            <div class="flex items-center gap-4 text-xs text-gt-muted">
                                <span class="flex items-center gap-1">
                                    <span class="inline-block h-2.5 w-2.5 rounded-sm bg-blue-400" aria-hidden="true"></span>
                                    {{ t('dashboard.chart.legendCreated') }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="inline-block h-2.5 w-2.5 rounded-sm bg-green-500" aria-hidden="true"></span>
                                    {{ t('dashboard.chart.legendCompleted') }}
                                </span>
                            </div>
                        </div>

                        <!-- SR summary -->
                        <p class="sr-only">
                            {{ t('dashboard.chart.srSummary', {
                                created: insights?.totalCreated ?? 0,
                                completed: insights?.totalCompleted ?? 0,
                                days: daily.length,
                            }) }}
                        </p>

                        <!-- SVG bar chart -->
                        <div class="overflow-x-auto -mx-4 px-4">
                            <svg
                                :width="Math.max(daily.length * 18, 300)"
                                height="150"
                                role="img"
                                class="block"
                                aria-hidden="true"
                            >
                                <g v-for="(d, i) in daily" :key="d.date">
                                    <!-- Created bar -->
                                    <rect
                                        :x="i * 18 + 2"
                                        :y="130 - barHeight(d.created)"
                                        width="6"
                                        :height="barHeight(d.created)"
                                        class="fill-blue-400"
                                        rx="1"
                                    >
                                        <title>{{ d.date }}: {{ d.created }} {{ t('dashboard.chart.legendCreated') }}</title>
                                    </rect>
                                    <!-- Completed bar -->
                                    <rect
                                        :x="i * 18 + 9"
                                        :y="130 - barHeight(d.completed)"
                                        width="6"
                                        :height="barHeight(d.completed)"
                                        class="fill-green-500"
                                        rx="1"
                                    >
                                        <title>{{ d.date }}: {{ d.completed }} {{ t('dashboard.chart.legendCompleted') }}</title>
                                    </rect>
                                    <!-- Date label (every Nth) -->
                                    <text
                                        v-if="daily.length <= 14 || i % Math.ceil(daily.length / 10) === 0"
                                        :x="i * 18 + 8"
                                        y="145"
                                        text-anchor="middle"
                                        class="fill-gt-muted text-[8px]"
                                    >
                                        {{ shortDate(d.date) }}
                                    </text>
                                </g>
                            </svg>
                        </div>

                        <!-- Data table toggle -->
                        <button
                            type="button"
                            class="text-xs text-gt-accent underline underline-offset-2 hover:text-gt-accent-hover"
                            @click="showDataTable = !showDataTable"
                        >
                            {{ showDataTable ? t('dashboard.chart.hideTable') : t('dashboard.chart.showTable') }}
                        </button>

                        <div v-if="showDataTable" class="max-h-48 overflow-auto">
                            <table class="w-full text-xs text-gt-ink">
                                <thead>
                                    <tr class="border-b border-gt-border">
                                        <th class="py-1 text-left font-medium">{{ t('dashboard.chart.colDate') }}</th>
                                        <th class="py-1 text-right font-medium">{{ t('dashboard.chart.colCreated') }}</th>
                                        <th class="py-1 text-right font-medium">{{ t('dashboard.chart.colCompleted') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="d in daily" :key="d.date" class="border-b border-gt-border/50">
                                        <td class="py-0.5">{{ d.date }}</td>
                                        <td class="py-0.5 text-right">{{ d.created }}</td>
                                        <td class="py-0.5 text-right">{{ d.completed }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Secondary reports (accordions) -->
                    <div class="space-y-3">

                        <!-- Weekday rhythm -->
                        <div class="gt-surface rounded-lg">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between p-4 text-left text-sm font-semibold text-gt-ink"
                                :aria-expanded="weekdayOpen"
                                @click="weekdayOpen = !weekdayOpen"
                            >
                                <span>{{ t('dashboard.weekday.title') }}</span>
                                <span class="text-xs text-gt-muted" aria-hidden="true">{{ weekdayOpen ? '▲' : '▼' }}</span>
                            </button>
                            <div v-if="weekdayOpen" class="px-4 pb-4 space-y-2">
                                <template v-if="weekday && weekday.days">
                                    <div v-for="d in weekday.days" :key="d.day" class="flex items-center gap-2 text-xs">
                                        <span class="w-8 text-gt-muted">{{ d.name }}</span>
                                        <div class="h-3 rounded-sm bg-green-500/80" :style="{ width: weekdayBarWidth(d.count) + '%' }"></div>
                                        <span class="text-gt-ink-secondary">{{ d.count }}</span>
                                    </div>
                                    <p v-if="weekday.peakDay" class="text-xs text-gt-muted mt-1">
                                        {{ t('dashboard.weekday.peakCallout', { day: weekday.peakDay }) }}
                                    </p>
                                    <p v-else class="text-xs text-gt-muted mt-1">
                                        {{ t('dashboard.weekday.noPeak') }}
                                    </p>
                                </template>
                            </div>
                        </div>

                        <!-- Lead time -->
                        <div class="gt-surface rounded-lg">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between p-4 text-left text-sm font-semibold text-gt-ink"
                                :aria-expanded="leadTimeOpen"
                                @click="leadTimeOpen = !leadTimeOpen"
                            >
                                <span>{{ t('dashboard.leadTime.title') }}</span>
                                <span class="text-xs text-gt-muted" aria-hidden="true">{{ leadTimeOpen ? '▲' : '▼' }}</span>
                            </button>
                            <div v-if="leadTimeOpen" class="px-4 pb-4">
                                <template v-if="leadTime && leadTime.median !== null">
                                    <div class="flex items-baseline gap-6">
                                        <div>
                                            <span class="text-xs text-gt-muted">{{ t('dashboard.leadTime.median') }}</span>
                                            <span class="ml-1 text-lg font-bold text-gt-ink">{{ t('dashboard.leadTime.days', { n: leadTime.median }) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gt-muted">{{ t('dashboard.leadTime.p90') }}</span>
                                            <span class="ml-1 text-lg font-bold text-gt-ink">{{ t('dashboard.leadTime.days', { n: leadTime.p90 }) }}</span>
                                        </div>
                                    </div>
                                    <p v-if="leadTime.longTail > 0" class="text-xs text-gt-muted mt-1">
                                        {{ t('dashboard.leadTime.longTail', { count: leadTime.longTail }) }}
                                    </p>
                                </template>
                                <p v-else class="text-xs text-gt-muted">{{ t('dashboard.leadTime.noData') }}</p>
                            </div>
                        </div>

                        <!-- Due discipline -->
                        <div class="gt-surface rounded-lg">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between p-4 text-left text-sm font-semibold text-gt-ink"
                                :aria-expanded="dueDisciplineOpen"
                                @click="dueDisciplineOpen = !dueDisciplineOpen"
                            >
                                <span>{{ t('dashboard.dueDiscipline.title') }}</span>
                                <span class="text-xs text-gt-muted" aria-hidden="true">{{ dueDisciplineOpen ? '▲' : '▼' }}</span>
                            </button>
                            <div v-if="dueDisciplineOpen" class="px-4 pb-4">
                                <template v-if="dueDisciplineTotal > 0">
                                    <!-- Stacked bar -->
                                    <div class="flex h-5 w-full overflow-hidden rounded-full text-[10px] font-medium">
                                        <div
                                            v-if="dueDiscipline.onTime"
                                            class="flex items-center justify-center bg-green-500 text-white"
                                            :style="{ width: duePct(dueDiscipline.onTime) + '%' }"
                                        >
                                            {{ duePct(dueDiscipline.onTime) }}%
                                        </div>
                                        <div
                                            v-if="dueDiscipline.overdue"
                                            class="flex items-center justify-center bg-amber-500 text-white"
                                            :style="{ width: duePct(dueDiscipline.overdue) + '%' }"
                                        >
                                            {{ duePct(dueDiscipline.overdue) }}%
                                        </div>
                                        <div
                                            v-if="dueDiscipline.noDue"
                                            class="flex items-center justify-center bg-gray-300 text-gray-700 dark:bg-gray-600 dark:text-gray-200"
                                            :style="{ width: duePct(dueDiscipline.noDue) + '%' }"
                                        >
                                            {{ duePct(dueDiscipline.noDue) }}%
                                        </div>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-4 text-xs text-gt-muted">
                                        <span class="flex items-center gap-1">
                                            <span class="inline-block h-2 w-2 rounded-full bg-green-500" aria-hidden="true"></span>
                                            {{ t('dashboard.dueDiscipline.onTime') }} ({{ dueDiscipline.onTime }})
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <span class="inline-block h-2 w-2 rounded-full bg-amber-500" aria-hidden="true"></span>
                                            {{ t('dashboard.dueDiscipline.overdue') }} ({{ dueDiscipline.overdue }})
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <span class="inline-block h-2 w-2 rounded-full bg-gray-300 dark:bg-gray-600" aria-hidden="true"></span>
                                            {{ t('dashboard.dueDiscipline.noDue') }} ({{ dueDiscipline.noDue }})
                                        </span>
                                    </div>
                                </template>
                                <p v-else class="text-xs text-gt-muted">{{ t('dashboard.dueDiscipline.noData') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick links -->
                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('tasks.index')" :class="primaryLinkClass">
                            {{ t('dashboard.openTasks') }}
                        </Link>
                        <Link
                            :href="route('profile.edit')"
                            class="text-sm font-medium text-gt-accent underline decoration-gt-accent/40 underline-offset-2 hover:text-gt-accent-hover"
                        >
                            {{ t('dashboard.manageConnection') }}
                        </Link>
                    </div>

                    <!-- Trust strip -->
                    <div class="rounded-lg border border-gt-border bg-gt-field-muted px-4 py-2 text-xs text-gt-muted">
                        {{ t('dashboard.trustStrip') }}
                    </div>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
