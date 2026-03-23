<script setup>
import TaskDeferMenu from '@/Components/TaskDeferMenu.vue';
import TaskNotesRichText from '@/Components/TaskNotesRichText.vue';
import TaskPriorityDueMeta from '@/Components/TaskPriorityDueMeta.vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    buckets: {
        type: Object,
        required: true,
    },
    /** 'priority' (P1–P4) or 'due' (Overdue / Today / This week / Later) */
    groupMode: { type: String, default: 'priority' },
    navMode: { type: String, required: true },
    isTaskSelected: { type: Function, required: true },
    taskKey: { type: Function, required: true },
    listIdForTask: { type: Function, required: true },
});

const emit = defineEmits([
    'task-click',
    'selection-click',
    'toggle-complete',
    'drop-priority',
    'drop-due',
    'inspect-task',
    'card-dblclick',
    'defer-preset',
]);

const PRIOS = ['p1', 'p2', 'p3', 'p4'];
const DUE_LANES = ['overdue', 'today', 'thisWeek', 'laterNoDate'];

const columns = computed(() =>
    props.groupMode === 'due' ? DUE_LANES : PRIOS,
);

function columnLabel(col) {
    if (props.groupMode === 'due') {
        return t(`tasks.kanbanDueLane.${col}`);
    }
    return t(`tasks.priorityBadge.${col}`);
}

function onDragStart(e, task) {
    const payload = {
        id: task.id,
        listId: props.listIdForTask(task),
    };
    e.dataTransfer.setData('application/json', JSON.stringify(payload));
    e.dataTransfer.effectAllowed = 'move';
}

function onDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function onDrop(e, col) {
    e.preventDefault();
    let raw = e.dataTransfer.getData('application/json');
    if (!raw) {
        return;
    }
    try {
        const data = JSON.parse(raw);
        if (!data?.id || !data?.listId) {
            return;
        }
        if (props.groupMode === 'due') {
            emit('drop-due', {
                taskId: data.id,
                listId: data.listId,
                newLane: col,
            });
        } else {
            emit('drop-priority', {
                taskId: data.id,
                listId: data.listId,
                newPriority: col,
            });
        }
    } catch {
        /* ignore */
    }
}

</script>

<template>
    <div
        class="grid touch-manipulation gap-3 sm:grid-cols-2 lg:grid-cols-4"
        role="region"
        :aria-label="t('tasks.kanbanRegion')"
    >
        <div
            v-for="col in columns"
            :key="col"
            class="flex min-h-0 min-w-0 flex-col rounded-lg border border-gt-border bg-gt-field-muted/80 dark:bg-gt-raised/50"
            @dragover="onDragOver"
            @drop="onDrop($event, col)"
        >
            <div
                class="shrink-0 border-b border-gt-border px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gt-muted"
            >
                {{ columnLabel(col) }}
            </div>
            <div
                class="max-h-[70vh] min-h-[8rem] flex-1 space-y-2 overflow-y-auto p-2"
            >
                <div
                    v-for="task in buckets[col] ?? []"
                    :key="taskKey(task)"
                    draggable="true"
                    :data-task-id="task.id"
                    class="cursor-grab rounded-md border border-gt-border bg-gt-raised shadow-sm active:cursor-grabbing dark:bg-gt-field"
                    @dragstart="onDragStart($event, task)"
                    @click="emit('task-click', task, $event)"
                    @dblclick="emit('card-dblclick', task, $event)"
                >
                    <div class="flex items-start gap-2 p-3">
                        <input
                            type="checkbox"
                            class="mt-0.5 rounded border-gt-border-strong text-gt-accent focus:ring-gt-accent-ring dark:bg-gt-field"
                            :checked="isTaskSelected(task)"
                            :disabled="task._optimistic"
                            :aria-label="t('tasks.bulkSelectTask')"
                            :title="t('tasks.bulkSelectTask')"
                            @click.prevent="emit('selection-click', task)"
                        />
                        <input
                            type="checkbox"
                            class="mt-0.5 rounded border-gt-border-strong text-gt-accent focus:ring-gt-accent-ring dark:bg-gt-field"
                            :checked="task.status === 'completed'"
                            :disabled="task._optimistic"
                            :aria-label="
                                task.status === 'completed'
                                    ? t('tasks.markTaskIncomplete')
                                    : t('tasks.markTaskComplete')
                            "
                            :title="
                                task.status === 'completed'
                                    ? t('tasks.markTaskIncomplete')
                                    : t('tasks.markTaskComplete')
                            "
                            @click.stop
                            @change="emit('toggle-complete', task)"
                        />
                        <div class="min-w-0 flex-1">
                            <div
                                class="flex flex-wrap items-center gap-x-1 gap-y-0.5"
                            >
                                <TaskPriorityDueMeta
                                    :task="task"
                                    variant="kanban"
                                />
                                <span
                                    v-if="
                                        (navMode === 'today' ||
                                            navMode === 'all') &&
                                        task._taskListTitle
                                    "
                                    class="truncate rounded bg-gt-field-muted px-1.5 py-0.5 text-[10px] text-gt-muted"
                                >
                                    {{ task._taskListTitle }}
                                </span>
                            </div>
                            <p
                                :class="[
                                    'mt-0.5 text-sm font-medium text-gt-ink',
                                    task.status === 'completed'
                                        ? 'line-through text-gt-subtle'
                                        : '',
                                ]"
                            >
                                {{ task.title }}
                            </p>
                            <TaskNotesRichText
                                v-if="task.notes"
                                class="mt-1 line-clamp-2 text-xs text-gt-muted"
                                :text="task.notes"
                            />
                            <div
                                class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1"
                            >
                                <TaskDeferMenu
                                    compact
                                    :disabled="task._optimistic"
                                    @pick="
                                        emit('defer-preset', {
                                            task,
                                            preset: $event,
                                        })
                                    "
                                />
                                <button
                                    type="button"
                                    class="text-[10px] font-medium text-gt-accent hover:underline"
                                    :disabled="task._optimistic"
                                    @click.stop="emit('inspect-task', task)"
                                >
                                    {{ t('tasks.taskDetails') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <slot
                        name="task-detail"
                        :task="task"
                    />
                </div>
                <p
                    v-if="(buckets[col] ?? []).length === 0"
                    class="px-1 py-4 text-center text-xs text-gt-subtle"
                >
                    {{ t('tasks.kanbanEmptyColumn') }}
                </p>
            </div>
        </div>
    </div>
</template>
