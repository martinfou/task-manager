<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
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
const leadTimeOpen = ref(true);
const dueDisciplineOpen = ref(true);

const tooltip = ref({ visible: false, x: 0, y: 0, title: '', body: '' });

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

const isBacklogGrowing = computed(() => {
    if (!insights.value) return false;
    return insights.value.netFlow < 0 || (insights.value.creationDelta > insights.value.completionDelta);
});

const focusScore = computed(() => {
    if (!insights.value) return 0;
    const { totalCreated, totalCompleted } = insights.value;
    if (totalCreated + totalCompleted === 0) return 0;
    return Math.round((totalCompleted / (totalCreated + totalCompleted)) * 100);
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
    return Math.max(0.5, (val / chartMax.value) * 120);
}

function shortDate(dateStr) {
    const d = new Date(dateStr + 'T12:00:00');
    return `${d.getMonth() + 1}/${d.getDate()}`;
}

// Weekday bar helpers
const weekdayMax = computed(() => {
    if (!weekday.value?.days) return 1;
    return Math.max(1, ...weekday.value.days.flatMap(d => [d.completed, d.created]));
});

function weekdayBarWidth(count) {
    return Math.max(1, (count / weekdayMax.value) * 100);
}

const dueDisciplineTotal = computed(() => dueDiscipline.value?.total ?? 0);

function duePct(val) {
    if (!dueDisciplineTotal.value) return 0;
    return Math.round((val / dueDisciplineTotal.value) * 100);
}

// Interactivity handlers
function updateTooltip(e, title, body) {
    tooltip.value = {
        visible: true,
        x: e.clientX,
        y: e.clientY + 10,
        title,
        body
    };
}

function hideTooltip() {
    tooltip.value.visible = false;
}

function onBarClick(date, status) {
    router.visit(route('tasks.index'), {
        data: {
            date,
            status: status === 'completed' ? 'completed' : 'needsAction'
        }
    });
}

function onWeekdayClick(dayName, status) {
    router.visit(route('tasks.index'), {
        data: {
            weekday: dayName.toLowerCase(),
            status: status === 'completed' ? 'completed' : 'needsAction'
        }
    });
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

        <div class="py-4 sm:py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-4">

                <!-- How metrics work (collapsible) -->
                <div
                    v-if="showHowMetrics"
                    class="gt-surface rounded-lg p-4 text-sm text-gt-muted leading-relaxed"
                >
                    {{ t('dashboard.howMetricsBody', { tz: userTimezone }) }}
                </div>

                <!-- Header row: subtitle + range selector -->
                <div class="gt-surface rounded-lg p-4">
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                        <div class="grow min-w-[200px]">
                            <p class="text-xs text-gt-muted">
                                {{ t('dashboard.subtitle') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-medium text-gt-ink-secondary">{{ t('dashboard.rangeLabel') }}</span>
                            <div class="inline-flex rounded-md border border-gt-border" role="radiogroup" :aria-label="t('dashboard.rangeLabel')">
                                <button
                                    v-for="r in RANGES"
                                    :key="r"
                                    type="button"
                                    role="radio"
                                    :aria-checked="rangeDays === r"
                                    class="px-2.5 py-1 text-xs font-medium transition-colors first:rounded-l-md last:rounded-r-md"
                                    :class="rangeDays === r
                                        ? 'bg-gt-accent-strong text-white'
                                        : 'bg-gt-raised text-gt-ink-secondary hover:bg-gt-field-muted'"
                                    @click="rangeDays = r"
                                >
                                    {{ t(`dashboard.range${r}`) }}
                                </button>
                            </div>
                            <span class="text-[10px] text-gt-muted whitespace-nowrap">{{ t('dashboard.tzLabel', { tz: userTimezone }) }}</span>
                        </div>
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
                    <div v-if="isCached" class="rounded-lg border border-yellow-300 bg-yellow-50 p-2 text-xs text-yellow-800 dark:border-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300">
                        {{ t('dashboard.staleDisclaimer', { date: new Date(stats.cachedAt).toLocaleString() }) }}
                    </div>

                    <!-- Insight cards -->
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
                        <!-- Period summary -->
                        <div class="gt-surface rounded-lg p-3 space-y-1 glassmorphism">
                            <h3 class="text-[10px] font-semibold uppercase tracking-wider text-gt-muted">{{ t('dashboard.insights.periodTitle') }}</h3>
                            <div class="flex items-baseline gap-3">
                                <div>
                                    <span class="text-xl font-bold text-gt-ink">{{ insights.totalCreated }}</span>
                                    <span class="ml-1 text-[10px] text-gt-muted">{{ t('dashboard.insights.created') }}</span>
                                </div>
                                <div>
                                    <span class="text-xl font-bold text-gt-ink">{{ insights.totalCompleted }}</span>
                                    <span class="ml-1 text-[10px] text-gt-muted">{{ t('dashboard.insights.completed') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Net flow -->
                        <div class="gt-surface rounded-lg p-3 space-y-1 glassmorphism">
                            <h3 class="text-[10px] font-semibold uppercase tracking-wider text-gt-muted">{{ t('dashboard.insights.netFlowTitle') }}</h3>
                            <div class="flex items-baseline justify-between overflow-hidden">
                                <p class="text-xl font-bold" :class="insights.netFlow > 0 ? 'text-green-600 dark:text-green-400' : insights.netFlow < 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gt-ink'">
                                    {{ insights.netFlow > 0 ? '+' : '' }}{{ insights.netFlow }}
                                </p>
                                <span v-if="isBacklogGrowing" class="text-[8px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 font-bold uppercase tracking-tight shrink-0 ml-2 animate-pulse">
                                    {{ t('dashboard.insights.backlogGrowing') }}
                                </span>
                            </div>
                            <p class="text-[10px] text-gt-muted leading-tight line-clamp-1">{{ netFlowLabel }}</p>
                        </div>

                        <!-- Lead Time -->
                        <div
                            v-if="leadTime && leadTime.median !== null"
                            class="gt-surface rounded-lg p-3 space-y-1 glassmorphism group relative overflow-hidden"
                        >
                            <h3 class="text-[10px] font-semibold uppercase tracking-wider text-gt-muted">{{ t('dashboard.leadTime.title') }}</h3>
                            <div class="flex items-baseline gap-2">
                                <span class="text-xl font-bold text-gt-ink">
                                    {{ leadTime.median === 0 ? t('dashboard.leadTime.lessThanDay') : leadTime.median + 'd' }}
                                </span>
                                <span class="text-[10px] font-semibold text-gt-muted uppercase">Median</span>
                            </div>
                            <div class="text-[10px] text-gt-muted">
                                p90: {{ leadTime.p90 === 0 ? t('dashboard.leadTime.lessThanDay') : leadTime.p90 + 'd' }}
                            </div>
                            <!-- Micro Hist -->
                            <div v-if="leadTime.buckets" class="flex items-end gap-0.5 h-4 opacity-50 group-hover:opacity-100 transition-opacity mt-1">
                                <div
                                    v-for="(count, bucket) in leadTime.buckets"
                                    :key="bucket"
                                    class="flex-1 bg-gt-accent/60 rounded-t-[1px]"
                                    :style="{ height: Math.max(10, (count / Math.max(1, ...Object.values(leadTime.buckets))) * 100) + '%' }"
                                ></div>
                            </div>
                        </div>

                        <!-- Focus Score -->
                        <div class="gt-surface rounded-lg p-3 space-y-1 glassmorphism relative overflow-hidden group">
                            <h3 class="text-[10px] font-semibold uppercase tracking-wider text-gt-muted">{{ t('dashboard.insights.focusScore') }}</h3>
                            <div class="flex items-baseline gap-2">
                                <span class="text-xl font-bold text-gt-ink">{{ focusScore }}%</span>
                                <span class="w-1.5 h-1.5 rounded-full" :class="focusScore >= 70 ? 'bg-green-500' : 'bg-amber-500'"></span>
                            </div>
                            <p class="text-[10px] text-gt-muted leading-tight">{{ t('dashboard.insights.focusScoreDesc') }}</p>
                            <div class="absolute bottom-0 left-0 h-1 bg-gt-accent transition-all duration-500" :style="{ width: focusScore + '%' }"></div>
                        </div>
                        <!-- Consistency -->
                        <div class="gt-surface rounded-lg p-3 space-y-1 glassmorphism">
                            <h3 class="text-[10px] font-semibold uppercase tracking-wider text-gt-muted">{{ t('dashboard.insights.consistencyTitle') }}</h3>
                            <p class="text-xl font-bold text-gt-ink tabular-nums">
                                {{ t('dashboard.insights.activeDays', { count: insights.activeDays, total: insights.totalDays }) }}
                            </p>
                            <p class="text-[10px] text-gt-muted leading-tight line-clamp-1">{{ t('dashboard.insights.activeDaysExplain') }}</p>
                        </div>

                        <!-- vs Prior -->
                        <div class="gt-surface rounded-lg p-3 space-y-1 glassmorphism">
                            <h3 class="text-[10px] font-semibold uppercase tracking-wider text-gt-muted">{{ t('dashboard.insights.priorTitle', { days: rangeDays }) }}</h3>
                            <template v-if="insights.hasPrior">
                                <div class="space-y-0.5">
                                    <div class="flex items-baseline justify-between">
                                        <span class="text-[10px] text-gt-muted">{{ t('dashboard.insights.priorCompletions') }}</span>
                                        <span
                                            class="text-[11px] font-bold"
                                            :class="(insights.completionDelta ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'"
                                        >
                                            {{ insights.completionDelta !== null
                                                ? (insights.completionDelta >= 0
                                                    ? t('dashboard.insights.deltaUp', { pct: insights.completionDelta })
                                                    : t('dashboard.insights.deltaDown', { pct: Math.abs(insights.completionDelta) }))
                                                : '—' }}
                                        </span>
                                    </div>
                                    <div class="flex items-baseline justify-between">
                                        <span class="text-[10px] text-gt-muted">{{ t('dashboard.insights.priorCreated') }}</span>
                                        <span
                                            class="text-[11px] font-bold text-gt-ink"
                                        >
                                            {{ insights.creationDelta !== null
                                                ? (insights.creationDelta >= 0
                                                    ? t('dashboard.insights.deltaUp', { pct: insights.creationDelta })
                                                    : t('dashboard.insights.deltaDown', { pct: Math.abs(insights.creationDelta) }))
                                                : '—' }}
                                        </span>
                                    </div>
                                </div>
                            </template>
                            <p v-else class="text-[10px] text-gt-muted">{{ t('dashboard.insights.noPrior') }}</p>
                        </div>
                    </div>

                    <!-- Due discipline (Promoted to top) -->
                    <div class="gt-surface rounded-lg p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gt-ink">{{ t('dashboard.dueDiscipline.title') }}</h3>
                            <div class="flex items-center gap-4 text-[10px] text-gt-muted">
                                <span class="flex items-center gap-1">
                                    <span class="inline-block h-2 w-2 rounded-full bg-green-500" aria-hidden="true"></span>
                                    {{ t('dashboard.dueDiscipline.onTime') }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="inline-block h-2 w-2 rounded-full bg-amber-500" aria-hidden="true"></span>
                                    {{ t('dashboard.dueDiscipline.overdue') }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="inline-block h-2 w-2 rounded-full bg-gray-300 dark:bg-gray-600" aria-hidden="true"></span>
                                    {{ t('dashboard.dueDiscipline.noDue') }}
                                </span>
                            </div>
                        </div>

                        <template v-if="dueDisciplineTotal > 0">
                            <!-- Stacked bar -->
                            <div class="flex h-6 w-full overflow-hidden rounded-md text-[10px] font-bold">
                                <div
                                    v-if="dueDiscipline.onTime"
                                    class="flex items-center justify-center bg-green-500 text-white border-r border-white/20 last:border-0"
                                    :style="{ width: duePct(dueDiscipline.onTime) + '%' }"
                                >
                                    {{ dueDiscipline.onTime }}
                                </div>
                                <div
                                    v-if="dueDiscipline.overdue"
                                    class="flex items-center justify-center bg-amber-500 text-white border-r border-white/20 last:border-0"
                                    :style="{ width: duePct(dueDiscipline.overdue) + '%' }"
                                >
                                    {{ dueDiscipline.overdue }}
                                </div>
                                <div
                                    v-if="dueDiscipline.noDue"
                                    class="flex items-center justify-center bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                    :style="{ width: duePct(dueDiscipline.noDue) + '%' }"
                                >
                                    {{ dueDiscipline.noDue }}
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gt-muted">
                                    {{ t('dashboard.dueDiscipline.onTime') }}: <strong>{{ duePct(dueDiscipline.onTime) }}%</strong>
                                </span>
                                <div v-if="dueDiscipline.noDue > 0" class="flex gap-2 items-center">
                                    <span class="text-gt-muted">{{ t('dashboard.dueDiscipline.noDue') }}: {{ dueDiscipline.noDue }}</span>
                                    <Link
                                        :href="route('tasks.index', { filter: 'no-due' })"
                                        class="text-gt-accent hover:underline font-medium"
                                    >
                                        {{ t('dashboard.dueDiscipline.fixNoDue') }} →
                                    </Link>
                                </div>
                            </div>
                        </template>
                        <p v-else class="text-xs text-gt-muted">{{ t('dashboard.dueDiscipline.noData') }}</p>
                    </div>

                    <!-- Throughput chart -->
                    <div class="gt-surface rounded-lg p-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gt-ink">{{ t('dashboard.chart.title') }}</h3>
                            <div class="flex items-center gap-3 text-[10px] text-gt-muted">
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
                        <div class="w-full">
                            <svg
                                width="100%"
                                height="150"
                                :viewBox="`0 0 ${daily.length * 18} 150`"
                                preserveAspectRatio="xMinYMin meet"
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
                                        class="fill-blue-400/60 hover:fill-blue-400 transition-all duration-300 cursor-pointer chart-bar"
                                        rx="1.5"
                                        @click="onBarClick(d.date, 'created')"
                                        @mouseenter="updateTooltip($event, shortDate(d.date), `${d.created} ${t('dashboard.chart.legendCreated')}`)"
                                        @mouseleave="hideTooltip"
                                    />
                                    <!-- Completed bar -->
                                    <rect
                                        :x="i * 18 + 9"
                                        :y="130 - barHeight(d.completed)"
                                        width="6"
                                        :height="barHeight(d.completed)"
                                        class="fill-green-500/60 hover:fill-green-500 transition-all duration-300 cursor-pointer chart-bar"
                                        rx="1.5"
                                        @click="onBarClick(d.date, 'completed')"
                                        @mouseenter="updateTooltip($event, shortDate(d.date), `${d.completed} ${t('dashboard.chart.legendCompleted')}`)"
                                        @mouseleave="hideTooltip"
                                    />
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

                    <!-- Secondary reports -->
                    <div class="space-y-4">
                        <!-- Weekday rhythm -->
                        <div class="gt-surface rounded-lg overflow-hidden">
                            <div
                                class="flex items-center justify-between p-4 text-sm font-semibold text-gt-ink bg-gt-field-muted/30"
                            >
                                <span>{{ t('dashboard.weekday.title') }}</span>
                                <div class="flex items-center gap-3 text-[10px] text-gt-muted font-normal">
                                    <span class="flex items-center gap-1">
                                        <span class="inline-block h-2 w-2 rounded-sm bg-blue-400"></span>
                                        {{ t('dashboard.weekday.legendCreated') }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <span class="inline-block h-2 w-2 rounded-sm bg-green-500"></span>
                                        {{ t('dashboard.weekday.legendCompleted') }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-4 space-y-3">
                                <template v-if="weekday && weekday.days">
                                    <div v-for="d in weekday.days" :key="d.day" class="space-y-1">
                                        <div class="flex items-center justify-between text-[10px] text-gt-muted">
                                            <span>{{ d.name }}</span>
                                            <span>{{ d.completed }} / {{ d.created }}</span>
                                        </div>
                                        <div class="flex flex-col gap-0.5">
                                            <div
                                                v-if="d.created > 0"
                                                class="h-1.5 rounded-r-sm bg-blue-400/70 hover:bg-blue-400 transition-all cursor-pointer"
                                                :style="{ width: weekdayBarWidth(d.created) + '%' }"
                                                @click="onWeekdayClick(d.name, 'created')"
                                                @mouseenter="updateTooltip($event, d.name, `${d.created} ${t('dashboard.weekday.legendCreated')}`)"
                                                @mouseleave="hideTooltip"
                                            ></div>
                                            <div
                                                v-if="d.completed > 0"
                                                class="h-1.5 rounded-r-sm bg-green-500/70 hover:bg-green-500 transition-all cursor-pointer"
                                                :style="{ width: weekdayBarWidth(d.completed) + '%' }"
                                                @click="onWeekdayClick(d.name, 'completed')"
                                                @mouseenter="updateTooltip($event, d.name, `${d.completed} ${t('dashboard.weekday.legendCompleted')}`)"
                                                @mouseleave="hideTooltip"
                                            ></div>
                                        </div>
                                    </div>
                                    <p v-if="weekday.peakDay" class="text-xs text-gt-muted mt-2 pt-2 border-t border-gt-border/50">
                                        {{ t('dashboard.weekday.peakCallout', { day: weekday.peakDay }) }}
                                    </p>
                                </template>
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

        <!-- Custom Tooltip -->
        <div
            v-if="tooltip.visible"
            class="fixed z-[100] pointer-events-none px-3 py-2 bg-gt-ink text-gt-canvas text-[11px] rounded-lg shadow-2xl border border-gt-canvas/10 -translate-x-1/2 -translate-y-full mb-4 transition-all duration-200"
            :style="{ left: `${tooltip.x}px`, top: `${tooltip.y}px` }"
        >
            <div class="font-bold border-b border-gt-canvas/10 pb-1 mb-1">{{ tooltip.title }}</div>
            <div>{{ tooltip.body }}</div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.glassmorphism {
    backdrop-filter: blur(8px);
    background: rgba(var(--gt-card-rgb, 255, 255, 255), 0.7) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

.dark .glassmorphism {
    --gt-card-rgb: 30, 30, 30;
    background: rgba(30,30,30, 0.7) !important;
    border: 1px solid rgba(255, 255, 255, 0.05) !important;
}

.chart-bar {
    transform-origin: bottom;
    animation: bar-grow 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes bar-grow {
    from { transform: scaleY(0); }
    to { transform: scaleY(1); }
}

.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
