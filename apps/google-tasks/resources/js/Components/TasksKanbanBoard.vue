<script setup>
import TaskNotesRichText from '@/Components/TaskNotesRichText.vue';
import { useLocaleDate } from '@/composables/useLocaleDate';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { formatDateTime } = useLocaleDate();

const props = defineProps({
    buckets: {
        type: Object,
        required: true,
    },
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
]);

const PRIOS = ['p1', 'p2', 'p3', 'p4'];

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

function onDrop(e, newPriority) {
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
        emit('drop-priority', {
            taskId: data.id,
            listId: data.listId,
            newPriority,
        });
    } catch {
        /* ignore */
    }
}

function priorityBadgeClass(priority) {
    if (priority === 'p1') {
        return 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300';
    }
    if (priority === 'p2') {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300';
    }
    if (priority === 'p4') {
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300';
    }

    return 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300';
}
</script>

<template>
    <div
        class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
        role="region"
        :aria-label="t('tasks.kanbanRegion')"
    >
        <div
            v-for="prio in PRIOS"
            :key="prio"
            class="flex min-h-0 min-w-0 flex-col rounded-lg border border-gray-200 bg-gray-50/80 dark:border-slate-700 dark:bg-slate-900/50"
            @dragover="onDragOver"
            @drop="onDrop($event, prio)"
        >
            <div
                class="shrink-0 border-b border-gray-200 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:border-slate-700 dark:text-slate-400"
            >
                {{
                    t(`tasks.priorityBadge.${prio}`)
                }}
            </div>
            <div
                class="max-h-[70vh] min-h-[8rem] flex-1 space-y-2 overflow-y-auto p-2"
            >
                <div
                    v-for="task in buckets[prio] ?? []"
                    :key="taskKey(task)"
                    draggable="true"
                    :data-task-id="task.id"
                    class="cursor-grab rounded-md border border-gray-200 bg-white p-2 shadow-sm active:cursor-grabbing dark:border-slate-600 dark:bg-slate-950"
                    @dragstart="onDragStart($event, task)"
                    @click="emit('task-click', task, $event)"
                >
                    <div class="flex items-start gap-2">
                        <input
                            type="checkbox"
                            class="mt-0.5 rounded border-gray-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-950"
                            :checked="isTaskSelected(task)"
                            :disabled="task._optimistic"
                            :aria-label="t('tasks.bulkSelectTask')"
                            @click.prevent="emit('selection-click', task)"
                        />
                        <input
                            type="checkbox"
                            class="mt-0.5 rounded border-gray-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-950"
                            :checked="task.status === 'completed'"
                            :disabled="task._optimistic"
                            @click.stop
                            @change="emit('toggle-complete', task)"
                        />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1">
                                <span
                                    class="rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase"
                                    :class="
                                        priorityBadgeClass(
                                            task.priority ?? 'p3',
                                        )
                                    "
                                >
                                    {{
                                        t(
                                            `tasks.priorityBadge.${(task.priority ?? 'p3').toLowerCase()}`,
                                        )
                                    }}
                                </span>
                                <span
                                    v-if="
                                        navMode === 'today' &&
                                        task._taskListTitle
                                    "
                                    class="truncate rounded bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-600 dark:bg-slate-800 dark:text-slate-300"
                                >
                                    {{ task._taskListTitle }}
                                </span>
                            </div>
                            <p
                                :class="[
                                    'mt-0.5 text-sm font-medium text-gray-900 dark:text-slate-100',
                                    task.status === 'completed'
                                        ? 'line-through text-gray-400 dark:text-slate-500'
                                        : '',
                                ]"
                            >
                                {{ task.title }}
                            </p>
                            <TaskNotesRichText
                                v-if="task.notes"
                                class="mt-1 line-clamp-2 text-xs text-gray-600 dark:text-slate-400"
                                :text="task.notes"
                            />
                            <p
                                v-if="task.due"
                                class="mt-1 text-[10px] text-gray-500 dark:text-slate-500"
                            >
                                {{
                                    t('tasks.dueLabel', {
                                        date: formatDateTime(task.due),
                                    })
                                }}
                            </p>
                        </div>
                    </div>
                </div>
                <p
                    v-if="(buckets[prio] ?? []).length === 0"
                    class="px-1 py-4 text-center text-xs text-gray-400 dark:text-slate-500"
                >
                    {{ t('tasks.kanbanEmptyColumn') }}
                </p>
            </div>
        </div>
    </div>
</template>
