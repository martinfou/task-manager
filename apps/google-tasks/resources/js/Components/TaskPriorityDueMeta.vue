<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useLocaleDate } from '@/composables/useLocaleDate';
import { isTaskDueToday, isTaskOverdue } from '@/utils/taskFilters';
import { isPriorityExplicit } from '@/utils/taskPriorityMeta';
import { priorityBadgeClass } from '@/utils/taskPriorityBadge';

const props = defineProps({
    task: { type: Object, required: true },
    /** `list` = main task rows; `search` / `kanban` = smaller metadata text */
    variant: {
        type: String,
        default: 'list',
        validator: (v) => ['list', 'search', 'kanban'].includes(v),
    },
});

const { t } = useI18n();
const { formatDueDate } = useLocaleDate();

const explicit = computed(() => isPriorityExplicit(props.task));

const badgeWrap = computed(() => {
    if (props.variant === 'list') {
        return 'rounded px-2 py-0.5 text-xs font-semibold uppercase tracking-wide';
    }
    return 'rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase';
});

const dueSize = computed(() => {
    if (props.variant === 'list') {
        return 'text-xs';
    }
    return 'text-[10px] leading-tight';
});

const mutedItalic = computed(() => {
    if (props.variant === 'list') {
        return 'text-xs italic text-gt-muted';
    }
    return 'text-[10px] italic text-gt-muted';
});

const iconSize = computed(() =>
    props.variant === 'list' ? 'h-3.5 w-3.5' : 'h-3 w-3',
);

const dueTone = computed(() => {
    const task = props.task;
    if (props.variant === 'search') {
        return 'text-gt-muted';
    }
    if (task.status === 'completed') {
        return 'text-gt-muted';
    }
    if (isTaskOverdue(task)) {
        return 'font-medium text-red-600 dark:text-red-400';
    }
    if (isTaskDueToday(task)) {
        return 'font-medium text-gt-accent';
    }
    return 'text-gt-muted';
});

const prio = computed(() => (props.task.priority ?? 'p3').toLowerCase());
</script>

<template>
    <span
        v-if="explicit"
        class="rounded font-semibold uppercase"
        :class="[badgeWrap, priorityBadgeClass(prio)]"
    >
        {{ t(`tasks.priorityBadge.${prio}`) }}
    </span>
    <template v-else>
        <span class="gt-meta-priority-comfortable" :class="mutedItalic">
            {{ t('tasks.meta.noPriority') }}
        </span>
        <span
            class="gt-meta-priority-compact inline-flex items-center gap-0.5 text-gt-muted"
            role="img"
            :aria-label="t('tasks.meta.noPriority')"
            :title="t('tasks.meta.noPriority')"
        >
            <svg
                :class="iconSize"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                aria-hidden="true"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3 3v18M9 3h10l-4 4 4 4H9"
                />
            </svg>
            <span aria-hidden="true" class="font-normal tabular-nums">—</span>
        </span>
    </template>

    <span
        v-if="task.due"
        :class="[dueSize, dueTone]"
    >
        {{
            t('tasks.dueLabel', {
                date: formatDueDate(task.due),
            })
        }}
    </span>
    <template v-else>
        <span class="gt-meta-due-comfortable" :class="mutedItalic">
            {{ t('tasks.meta.noDue') }}
        </span>
        <span
            class="gt-meta-due-compact inline-flex items-center gap-0.5 text-gt-muted"
            role="img"
            :aria-label="t('tasks.meta.noDue')"
            :title="t('tasks.meta.noDue')"
        >
            <svg
                :class="iconSize"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                aria-hidden="true"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5"
                />
            </svg>
            <span aria-hidden="true" class="font-normal tabular-nums">—</span>
        </span>
    </template>
</template>
