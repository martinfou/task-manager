<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TaskNotesRichText from '@/Components/TaskNotesRichText.vue';
import TasksKeyboardShortcutsHelp from '@/Components/TasksKeyboardShortcutsHelp.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useLocaleDate } from '@/composables/useLocaleDate';
import { useTasksKeyboardShortcuts } from '@/composables/useTasksKeyboardShortcuts';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import {
    computed,
    nextTick,
    onMounted,
    onUnmounted,
    ref,
    toRef,
    watch,
} from 'vue';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();
const { formatDateTime } = useLocaleDate();

const props = defineProps({
    connected: { type: Boolean, default: false },
    pollIntervalMs: { type: Number, default: 5000 },
    maxBackoffMs: { type: Number, default: 120000 },
});

/** @type {import('vue').Ref<'today'|'inbox'|'list'>} */
const navMode = ref('today');
const taskLists = ref([]);
const selectedListId = ref(null);
const tasks = ref([]);
const loadError = ref('');
const newTitle = ref('');
const newDue = ref('');
const newRecurrence = ref('');
const newPriority = ref('p3');
const newNotes = ref('');
const formError = ref('');
const pollBackoffMs = ref(props.pollIntervalMs);
const showListDrawer = ref(false);
const searchQuery = ref('');
const searchResults = ref([]);
const searchLoading = ref(false);
const searchError = ref('');
const searchTruncated = ref(false);
const showKeyboardHelp = ref(false);
const searchInputRef = ref(null);
const newTaskTitleRef = ref(null);
const focusedTaskIndex = ref(-1);
/** @type {import('vue').Ref<Record<string, true>>} */
const selectedKeys = ref({});
const selectionAnchorIndex = ref(-1);
const bulkWorking = ref(false);
const showBulkDeleteConfirm = ref(false);
const showBulkMoveModal = ref(false);
/** @type {import('vue').Ref<string|null>} */
const bulkMoveDestination = ref(null);
const showBulkResultModal = ref(false);
/** @type {import('vue').Ref<{ title: string; message: string }[]>} */
const bulkFailureLines = ref([]);
let pollTimer = null;
let searchDebounce = null;

const selectedListTitle = computed(() => {
    const list = taskLists.value.find((l) => l.id === selectedListId.value);
    return list?.title ?? '';
});

const defaultListId = computed(() => {
    const my = taskLists.value.find((l) => l.title === 'My Tasks');
    return my?.id ?? taskLists.value[0]?.id ?? null;
});

const effectiveListId = computed(() => {
    if (navMode.value === 'list') {
        return selectedListId.value;
    }

    return defaultListId.value;
});

const pageTitle = computed(() => {
    locale.value;
    if (navMode.value === 'today') {
        return t('tasks.titleToday');
    }
    if (navMode.value === 'inbox') {
        return t('tasks.titleInbox');
    }

    return selectedListTitle.value || t('tasks.titleTasks');
});

const timeZoneLabel = computed(
    () => Intl.DateTimeFormat().resolvedOptions().timeZone,
);

function normalizeItems(payload) {
    return payload?.items ?? [];
}

function listIdForTask(task) {
    return task._taskListId || selectedListId.value;
}

function taskKey(task) {
    return `${listIdForTask(task)}:${task.id}`;
}

function clearSelection() {
    selectedKeys.value = {};
    selectionAnchorIndex.value = -1;
}

function toggleSelection(task, force = null) {
    if (task._optimistic) {
        return;
    }
    const k = taskKey(task);
    const next = { ...selectedKeys.value };
    if (force === true) {
        next[k] = true;
    } else if (force === false) {
        delete next[k];
    } else if (next[k]) {
        delete next[k];
    } else {
        next[k] = true;
    }
    selectedKeys.value = next;
}

function isTaskSelected(task) {
    return Boolean(selectedKeys.value[taskKey(task)]);
}

const selectedCount = computed(
    () => Object.keys(selectedKeys.value).length,
);

function selectedTasksFlat() {
    return tasks.value.filter(
        (t) => !t._optimistic && selectedKeys.value[taskKey(t)],
    );
}

function pruneSelectionFromTasks() {
    const valid = new Set(tasks.value.map((t) => taskKey(t)));
    const next = { ...selectedKeys.value };
    let changed = false;
    for (const k of Object.keys(next)) {
        if (!valid.has(k)) {
            delete next[k];
            changed = true;
        }
    }
    if (changed) {
        selectedKeys.value = next;
    }
}

function onTaskRowClick(task, taskIndex, e) {
    if (task._optimistic) {
        focusedTaskIndex.value = taskIndex;
        return;
    }
    if (e.shiftKey) {
        e.preventDefault();
        const anchor =
            selectionAnchorIndex.value >= 0
                ? selectionAnchorIndex.value
                : focusedTaskIndex.value >= 0
                  ? focusedTaskIndex.value
                  : taskIndex;
        const [lo, hi] =
            taskIndex < anchor ? [taskIndex, anchor] : [anchor, taskIndex];
        const next = { ...selectedKeys.value };
        for (let i = lo; i <= hi; i++) {
            const t = tasks.value[i];
            if (t && !t._optimistic) {
                next[taskKey(t)] = true;
            }
        }
        selectedKeys.value = next;
        selectionAnchorIndex.value = taskIndex;
        focusedTaskIndex.value = taskIndex;
        return;
    }
    if (e.ctrlKey || e.metaKey) {
        e.preventDefault();
        toggleSelection(task);
        selectionAnchorIndex.value = taskIndex;
        focusedTaskIndex.value = taskIndex;
        return;
    }
    focusedTaskIndex.value = taskIndex;
    selectionAnchorIndex.value = taskIndex;
}

function onSelectionCheckboxClick(task, taskIndex) {
    if (task._optimistic) {
        return;
    }
    toggleSelection(task);
    selectionAnchorIndex.value = taskIndex;
    focusedTaskIndex.value = taskIndex;
}

function bulkSelectAll() {
    const next = { ...selectedKeys.value };
    for (const t of tasks.value) {
        if (!t._optimistic) {
            next[taskKey(t)] = true;
        }
    }
    selectedKeys.value = next;
}

async function runBulkComplete() {
    const list = selectedTasksFlat();
    if (list.length === 0) {
        return;
    }
    bulkWorking.value = true;
    bulkFailureLines.value = [];
    for (const task of list) {
        if (task.status === 'completed') {
            continue;
        }
        try {
            const listId = listIdForTask(task);
            await axios.patch(
                route('tasks.data.tasks.update', {
                    taskList: listId,
                    task: task.id,
                }),
                { status: 'completed' },
            );
        } catch (e) {
            bulkFailureLines.value.push({
                title: task.title,
                message:
                    e.response?.data?.message ??
                    e.message ??
                    t('tasks.errors.updateFailed'),
            });
        }
    }
    bulkWorking.value = false;
    clearSelection();
    await pollOnce();
    if (bulkFailureLines.value.length > 0) {
        showBulkResultModal.value = true;
    }
}

async function executeBulkDelete() {
    showBulkDeleteConfirm.value = false;
    const list = selectedTasksFlat();
    if (list.length === 0) {
        return;
    }
    bulkWorking.value = true;
    bulkFailureLines.value = [];
    for (const task of list) {
        try {
            const listId = listIdForTask(task);
            await axios.delete(
                route('tasks.data.tasks.destroy', {
                    taskList: listId,
                    task: task.id,
                }),
            );
        } catch (e) {
            bulkFailureLines.value.push({
                title: task.title,
                message:
                    e.response?.data?.message ??
                    e.message ??
                    t('tasks.errors.deleteFailed'),
            });
        }
    }
    bulkWorking.value = false;
    clearSelection();
    await pollOnce();
    if (bulkFailureLines.value.length > 0) {
        showBulkResultModal.value = true;
    }
}

function openBulkMoveModal() {
    bulkMoveDestination.value = taskLists.value[0]?.id ?? null;
    showBulkMoveModal.value = true;
}

async function executeBulkMove() {
    const dest = bulkMoveDestination.value;
    if (!dest) {
        return;
    }
    showBulkMoveModal.value = false;
    const list = selectedTasksFlat();
    if (list.length === 0) {
        return;
    }
    bulkWorking.value = true;
    bulkFailureLines.value = [];
    for (const task of list) {
        const sourceListId = listIdForTask(task);
        if (sourceListId === dest) {
            continue;
        }
        try {
            await axios.post(
                route('tasks.data.tasks.move', {
                    taskList: sourceListId,
                    task: task.id,
                }),
                { destinationTasklist: dest },
            );
        } catch (e) {
            bulkFailureLines.value.push({
                title: task.title,
                message:
                    e.response?.data?.message ??
                    e.message ??
                    t('tasks.errors.updateFailed'),
            });
        }
    }
    bulkWorking.value = false;
    clearSelection();
    await pollOnce();
    if (bulkFailureLines.value.length > 0) {
        showBulkResultModal.value = true;
    }
}

async function fetchTaskLists() {
    const { data } = await axios.get(route('tasks.data.task-lists'));
    taskLists.value = normalizeItems(data);
    if (!selectedListId.value && taskLists.value.length > 0) {
        selectedListId.value = taskLists.value[0].id;
    }
}

async function fetchTasksForList() {
    if (!selectedListId.value) {
        tasks.value = [];
        return;
    }
    const { data } = await axios.get(
        route('tasks.data.tasks', { taskList: selectedListId.value }),
        { params: { showCompleted: true } },
    );
    tasks.value = normalizeItems(data).map((t) => {
        const { _taskListId, _taskListTitle, ...rest } = t;
        return rest;
    });
}

async function fetchToday() {
    const { data } = await axios.get(route('tasks.data.views.today'));
    tasks.value = (data.items ?? []).map((row) => ({
        ...row.task,
        _taskListId: row.taskListId,
        _taskListTitle: row.taskListTitle,
    }));
}

async function fetchInbox() {
    const { data } = await axios.get(route('tasks.data.views.inbox'), {
        params: { showCompleted: true },
    });
    if (data.taskList?.id) {
        selectedListId.value = data.taskList.id;
    }
    tasks.value = (data.items ?? []).map((t) => ({
        ...t,
        _taskListId: data.taskList?.id,
    }));
}

async function pollOnce() {
    loadError.value = '';
    try {
        await fetchTaskLists();
        if (navMode.value === 'today') {
            await fetchToday();
        } else if (navMode.value === 'inbox') {
            await fetchInbox();
        } else {
            await fetchTasksForList();
        }
        pollBackoffMs.value = props.pollIntervalMs;
    } catch (e) {
        if (e.response?.status === 429) {
            pollBackoffMs.value = Math.min(
                props.maxBackoffMs,
                Math.max(props.pollIntervalMs, pollBackoffMs.value * 2),
            );
            loadError.value = t('tasks.errors.rateLimited');
        } else {
            loadError.value =
                e.response?.data?.message ??
                e.message ??
                t('tasks.errors.loadFailed');
        }
    }
}

async function pollLoop() {
    await pollOnce();
    pollTimer = setTimeout(pollLoop, pollBackoffMs.value);
}

async function setNav(mode) {
    navMode.value = mode;
    showListDrawer.value = false;
    if (mode === 'list' && !selectedListId.value && taskLists.value.length > 0) {
        selectedListId.value = taskLists.value[0].id;
    }
    if (!props.connected) {
        return;
    }
    loadError.value = '';
    try {
        if (mode === 'today') {
            await fetchToday();
        } else if (mode === 'inbox') {
            await fetchInbox();
        } else {
            await fetchTasksForList();
        }
    } catch (e) {
        loadError.value =
            e.response?.data?.message ??
            e.message ??
            t('tasks.errors.loadFailed');
    }
}

async function selectList(list) {
    navMode.value = 'list';
    selectedListId.value = list.id;
    showListDrawer.value = false;
    if (!props.connected) {
        return;
    }
    loadError.value = '';
    try {
        await fetchTasksForList();
    } catch (e) {
        loadError.value =
            e.response?.data?.message ??
            e.message ??
            t('tasks.errors.loadFailed');
    }
}

async function onListDropdownChange() {
    navMode.value = 'list';
    loadError.value = '';
    try {
        await fetchTasksForList();
    } catch (e) {
        loadError.value =
            e.response?.data?.message ??
            e.message ??
            t('tasks.errors.loadFailed');
    }
}

watch(searchQuery, (q) => {
    clearTimeout(searchDebounce);
    const trimmed = q.trim();
    if (trimmed.length < 2) {
        searchResults.value = [];
        searchTruncated.value = false;
        searchError.value = '';
        searchLoading.value = false;
        return;
    }
    searchLoading.value = true;
    searchError.value = '';
    searchDebounce = setTimeout(async () => {
        try {
            const { data } = await axios.get(route('tasks.data.search'), {
                params: { q: trimmed },
            });
            searchResults.value = data.items ?? [];
            searchTruncated.value = Boolean(data.truncated);
        } catch (e) {
            searchError.value =
                e.response?.data?.message ??
                e.message ??
                t('tasks.searchError');
            searchResults.value = [];
            searchTruncated.value = false;
        } finally {
            searchLoading.value = false;
        }
    }, 300);
});

async function openSearchResult(row) {
    clearSelection();
    focusedTaskIndex.value = -1;
    searchQuery.value = '';
    searchResults.value = [];
    searchTruncated.value = false;
    searchError.value = '';

    if (!taskLists.value.find((l) => l.id === row.taskListId)) {
        await fetchTaskLists();
    }
    const list = taskLists.value.find((l) => l.id === row.taskListId);
    if (!list) {
        return;
    }
    navMode.value = 'list';
    selectedListId.value = list.id;
    showListDrawer.value = false;
    loadError.value = '';
    try {
        await fetchTasksForList();
        await nextTick();
        for (const el of document.querySelectorAll('[data-task-id]')) {
            if (el.getAttribute('data-task-id') === row.task.id) {
                el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                break;
            }
        }
    } catch (e) {
        loadError.value =
            e.response?.data?.message ??
            e.message ??
            t('tasks.errors.loadFailed');
    }
}

onMounted(() => {
    if (!props.connected) {
        return;
    }
    void pollLoop();
});

onUnmounted(() => {
    clearTimeout(pollTimer);
});

function onNotesPaste(event) {
    const raw = event.clipboardData?.getData('text/plain') ?? '';
    const trimmed = raw.trim();
    if (!/^https?:\/\//i.test(trimmed)) {
        return;
    }
    try {
        const u = new URL(trimmed);
        if (u.protocol !== 'http:' && u.protocol !== 'https:') {
            return;
        }
    } catch {
        return;
    }
    event.preventDefault();
    if (newNotes.value.trim() === '') {
        newNotes.value = trimmed;
    } else {
        newNotes.value = `${newNotes.value.trimEnd()}\n${trimmed}`;
    }
}

async function submitNewTask() {
    formError.value = '';
    const listId = effectiveListId.value;
    if (!listId || !newTitle.value.trim()) {
        formError.value = t('tasks.errors.chooseDest');
        return;
    }

    const notesTrimmed = newNotes.value.trim();
    const tempId = `temp-${Date.now()}`;
    const optimistic = {
        id: tempId,
        title: newTitle.value.trim(),
        status: 'needsAction',
        _optimistic: true,
    };
    if (notesTrimmed) {
        optimistic.notes = notesTrimmed;
    }
    if (navMode.value === 'today') {
        optimistic._taskListId = listId;
    } else if (navMode.value === 'inbox') {
        optimistic._taskListId = listId;
    }
    tasks.value = [optimistic, ...tasks.value];
    const title = newTitle.value.trim();
    newTitle.value = '';

    const body = { title };
    body.priority = newPriority.value;
    if (notesTrimmed) {
        body.notes = notesTrimmed;
    }
    if (newDue.value) {
        body.due = new Date(newDue.value).toISOString();
    }
    if (newRecurrence.value.trim()) {
        body.recurrence = [newRecurrence.value.trim()];
    }

    try {
        const { data } = await axios.post(
            route('tasks.data.tasks.store', { taskList: listId }),
            body,
        );
        const mapped =
            navMode.value === 'today' || navMode.value === 'inbox'
                ? { ...data, _taskListId: listId }
                : data;
        tasks.value = tasks.value.map((t) =>
            t.id === tempId ? mapped : t,
        );
        newDue.value = '';
        newRecurrence.value = '';
        newPriority.value = 'p3';
        newNotes.value = '';
        void pollOnce();
    } catch (e) {
        tasks.value = tasks.value.filter((t) => t.id !== tempId);
        formError.value =
            e.response?.data?.message ??
            e.message ??
            t('tasks.errors.createFailed');
    }
}

async function updatePriority(task, priority) {
    const listId = listIdForTask(task);
    const previousTask = { ...task };
    tasks.value = tasks.value.map((current) =>
        current.id === task.id ? { ...current, priority } : current,
    );
    try {
        const { data } = await axios.patch(
            route('tasks.data.tasks.update', {
                taskList: listId,
                task: task.id,
            }),
            { title: task.title, priority },
        );
        const merged = { ...data };
        if (task._taskListId) {
            merged._taskListId = task._taskListId;
            merged._taskListTitle = task._taskListTitle;
        }
        tasks.value = tasks.value.map((current) =>
            current.id === task.id ? merged : current,
        );
        void pollOnce();
    } catch (e) {
        tasks.value = tasks.value.map((current) =>
            current.id === task.id ? previousTask : current,
        );
        loadError.value =
            e.response?.data?.message ??
            e.message ??
            t('tasks.errors.updateFailed');
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

async function toggleComplete(task) {
    const listId = listIdForTask(task);
    const prev = { ...task };
    const nextStatus =
        task.status === 'completed' ? 'needsAction' : 'completed';
    tasks.value = tasks.value.map((t) =>
        t.id === task.id ? { ...t, status: nextStatus } : t,
    );
    try {
        const { data } = await axios.patch(
            route('tasks.data.tasks.update', {
                taskList: listId,
                task: task.id,
            }),
            { status: nextStatus },
        );
        const merged = { ...data };
        if (task._taskListId) {
            merged._taskListId = task._taskListId;
            merged._taskListTitle = task._taskListTitle;
        }
        tasks.value = tasks.value.map((t) => (t.id === task.id ? merged : t));
        void pollOnce();
    } catch (e) {
        tasks.value = tasks.value.map((t) =>
            t.id === task.id ? prev : t,
        );
        loadError.value =
            e.response?.data?.message ??
            e.message ??
            t('tasks.errors.updateFailed');
    }
}

async function removeTask(task) {
    const listId = listIdForTask(task);
    const snapshot = [...tasks.value];
    tasks.value = tasks.value.filter((t) => t.id !== task.id);
    try {
        await axios.delete(
            route('tasks.data.tasks.destroy', {
                taskList: listId,
                task: task.id,
            }),
        );
        void pollOnce();
    } catch (e) {
        tasks.value = snapshot;
        loadError.value =
            e.response?.data?.message ??
            e.message ??
            t('tasks.errors.deleteFailed');
    }
}

function indentClass(task) {
    return task.parent
        ? 'ps-8 border-s-2 border-gray-200 dark:border-slate-600'
        : '';
}

function navButtonClass(active) {
    return active
        ? 'bg-indigo-50 text-indigo-800 ring-1 ring-inset ring-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-100 dark:ring-indigo-800'
        : 'text-gray-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-800';
}

useTasksKeyboardShortcuts({
    connected: toRef(props, 'connected'),
    showHelp: showKeyboardHelp,
    tasks,
    focusedTaskIndex,
    onOpenHelp: () => {
        showKeyboardHelp.value = true;
    },
    onFocusSearch: () => {
        searchInputRef.value?.focus();
        searchInputRef.value?.select?.();
    },
    onFocusNewTask: () => {
        newTaskTitleRef.value?.focus?.();
    },
    onGoToday: () => setNav('today'),
    onGoInbox: () => setNav('inbox'),
    onGoList: () => setNav('list'),
    onToggleComplete: (task) => toggleComplete(task),
});

watch(navMode, () => {
    focusedTaskIndex.value = -1;
    clearSelection();
});

watch(selectedListId, () => {
    clearSelection();
});

watch(tasks, pruneSelectionFromTasks);

watch(
    () => tasks.value.length,
    () => {
        if (focusedTaskIndex.value >= tasks.value.length) {
            focusedTaskIndex.value =
                tasks.value.length > 0 ? tasks.value.length - 1 : -1;
        }
    },
);

watch(focusedTaskIndex, async (idx) => {
    await nextTick();
    if (idx < 0) {
        return;
    }
    const nodes = document.querySelectorAll('[data-task-id]');
    const el = nodes[idx];
    el?.scrollIntoView?.({ block: 'nearest', behavior: 'smooth' });
});
</script>

<template>
    <Head :title="t('tasks.headTitle')" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex w-full flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4"
            >
                <div
                    class="flex flex-wrap items-center gap-x-3 gap-y-1"
                >
                    <h2
                        class="text-xl font-semibold leading-tight text-gray-800 dark:text-slate-100"
                    >
                        {{ pageTitle }}
                    </h2>
                    <button
                        v-if="connected"
                        type="button"
                        class="text-xs font-medium text-indigo-600 underline decoration-indigo-600/30 underline-offset-2 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                        @click="showKeyboardHelp = true"
                    >
                        {{ t('shortcuts.hint') }}
                    </button>
                </div>
                <div v-if="connected" class="w-full min-w-0 sm:max-w-md">
                    <label class="sr-only" for="task-search">{{
                        t('tasks.searchLabel')
                    }}</label>
                    <input
                        id="task-search"
                        ref="searchInputRef"
                        v-model="searchQuery"
                        type="search"
                        autocomplete="off"
                        class="block w-full rounded-md border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                        :placeholder="t('tasks.searchPlaceholder')"
                    />
                    <p
                        v-if="searchLoading"
                        class="mt-1 text-xs text-gray-500 dark:text-slate-400"
                    >
                        {{ t('tasks.searchLoading') }}
                    </p>
                    <p
                        v-if="searchError"
                        class="mt-1 text-xs text-red-600 dark:text-red-400"
                    >
                        {{ searchError }}
                    </p>
                    <p
                        v-if="searchTruncated && searchResults.length > 0"
                        class="mt-1 text-xs text-amber-700 dark:text-amber-300"
                    >
                        {{ t('tasks.searchTruncated') }}
                    </p>
                    <ul
                        v-if="searchResults.length > 0"
                        class="mt-2 max-h-56 overflow-y-auto rounded-md border border-gray-200 bg-white text-sm shadow-sm dark:border-slate-700 dark:bg-slate-900"
                        role="listbox"
                    >
                        <li
                            v-for="row in searchResults"
                            :key="`${row.taskListId}-${row.task.id}`"
                            class="border-b border-gray-100 last:border-0 dark:border-slate-800"
                        >
                            <button
                                type="button"
                                class="w-full px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-slate-800"
                                @click="openSearchResult(row)"
                            >
                                <span
                                    class="block font-medium text-gray-900 dark:text-slate-100"
                                    >{{ row.task.title }}</span
                                >
                                <span
                                    class="block text-xs text-gray-500 dark:text-slate-400"
                                    >{{ row.taskListTitle }}</span
                                >
                                <TaskNotesRichText
                                    v-if="row.snippet"
                                    class="mt-0.5 line-clamp-2 text-xs text-gray-600 dark:text-slate-400"
                                    :text="row.snippet"
                                />
                            </button>
                        </li>
                    </ul>
                    <p
                        v-if="
                            searchQuery.trim().length >= 2 &&
                            !searchLoading &&
                            !searchError &&
                            searchResults.length === 0
                        "
                        class="mt-1 text-xs text-gray-500 dark:text-slate-400"
                    >
                        {{ t('tasks.searchNoResults') }}
                    </p>
                </div>
            </div>
        </template>

        <div class="overflow-x-hidden pb-24 lg:pb-8">
            <div
                class="mx-auto flex max-w-6xl flex-col gap-6 lg:flex-row lg:px-6"
            >
                <div
                    v-if="!connected"
                    class="overflow-hidden bg-white shadow-sm dark:bg-slate-900 sm:mx-6 sm:rounded-lg lg:mx-0 density-card-padding"
                >
                    <p class="text-gray-700 dark:text-slate-300">
                        {{ t('tasks.connectPrompt') }}
                    </p>
                    <a
                        :href="route('google.redirect')"
                        class="mt-4 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        {{ t('tasks.connectGoogle') }}
                    </a>
                </div>

                <template v-else>
                    <!-- Desktop sidebar -->
                    <aside
                        class="hidden w-56 shrink-0 flex-col gap-1 border-r border-gray-100 pr-4 dark:border-slate-800 lg:flex"
                    >
                        <button
                            type="button"
                            class="w-full rounded-md px-3 py-2 text-left text-sm font-medium"
                            :class="navButtonClass(navMode === 'today')"
                            @click="setNav('today')"
                        >
                            {{ t('tasks.navToday') }}
                        </button>
                        <button
                            type="button"
                            class="w-full rounded-md px-3 py-2 text-left text-sm font-medium"
                            :class="navButtonClass(navMode === 'inbox')"
                            @click="setNav('inbox')"
                        >
                            {{ t('tasks.navInbox') }}
                        </button>
                        <div
                            class="mt-4 border-t border-gray-100 pt-3 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:border-slate-800 dark:text-slate-500"
                        >
                            {{ t('tasks.listsHeading') }}
                        </div>
                        <button
                            v-for="list in taskLists"
                            :key="list.id"
                            type="button"
                            class="w-full truncate rounded-md px-3 py-2 text-left text-sm font-medium"
                            :class="
                                navButtonClass(
                                    navMode === 'list' &&
                                        selectedListId === list.id,
                                )
                            "
                            :title="list.title"
                            @click="selectList(list)"
                        >
                            {{ list.title }}
                        </button>
                    </aside>

                    <div class="min-w-0 flex-1 density-stack px-4 sm:px-6 lg:px-0">
                        <details
                            class="rounded-md border border-gray-200 bg-white text-sm text-gray-700 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 density-card-padding"
                        >
                            <summary
                                class="cursor-pointer font-medium text-gray-900 dark:text-slate-100"
                            >
                                {{ t('tasks.helpTitle') }}
                            </summary>
                            <ul class="mt-3 list-disc space-y-2 ps-5">
                                <li>
                                    {{
                                        t('tasks.helpBullet1', {
                                            tz: timeZoneLabel,
                                        })
                                    }}
                                </li>
                                <li>
                                    {{ t('tasks.helpBullet2') }}
                                </li>
                                <li>
                                    {{ t('tasks.helpBullet3') }}
                                </li>
                                <li>
                                    {{ t('tasks.helpBulletBulk') }}
                                </li>
                            </ul>
                        </details>

                        <div
                            v-if="loadError"
                            class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-100"
                        >
                            {{ loadError }}
                        </div>

                        <div
                            class="overflow-hidden bg-white shadow-sm dark:bg-slate-900 sm:rounded-lg density-card-padding"
                        >
                            <div
                                class="flex flex-col gap-4 sm:flex-row sm:items-end"
                            >
                                <div
                                    v-if="navMode === 'list'"
                                    class="grow lg:hidden"
                                >
                                    <InputLabel
                                        for="list"
                                        :value="t('tasks.activeList')"
                                    />
                                    <select
                                        id="list"
                                        v-model="selectedListId"
                                        class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                                        @change="onListDropdownChange"
                                    >
                                        <option
                                            v-for="list in taskLists"
                                            :key="list.id"
                                            :value="list.id"
                                        >
                                            {{ list.title }}
                                        </option>
                                    </select>
                                </div>
                                <div
                                    class="text-sm text-gray-500 dark:text-slate-400 sm:ms-auto"
                                    title="Polling interval adapts on HTTP 429"
                                >
                                    {{
                                        t('tasks.pollLine', {
                                            current: pollBackoffMs,
                                            target: pollIntervalMs,
                                        })
                                    }}
                                </div>
                            </div>

                            <div class="mt-6 space-y-3">
                                <InputLabel
                                    for="title"
                                    :value="t('tasks.newTask')"
                                />
                                <div class="flex flex-col gap-3 sm:flex-row">
                                    <TextInput
                                        id="title"
                                        ref="newTaskTitleRef"
                                        v-model="newTitle"
                                        type="text"
                                        class="block w-full"
                                        :placeholder="t('tasks.titlePlaceholder')"
                                        @keyup.enter="submitNewTask"
                                    />
                                    <PrimaryButton
                                        type="button"
                                        @click="submitNewTask"
                                    >
                                        {{ t('tasks.add') }}
                                    </PrimaryButton>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <div>
                                        <InputLabel
                                            for="due"
                                            :value="t('tasks.dueOptional')"
                                        />
                                        <TextInput
                                            id="due"
                                            v-model="newDue"
                                            type="datetime-local"
                                            class="mt-1 block w-full"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel
                                            for="recurrence"
                                            :value="t('tasks.recurrenceOptional')"
                                        />
                                        <TextInput
                                            id="recurrence"
                                            v-model="newRecurrence"
                                            type="text"
                                            class="mt-1 block w-full"
                                            :placeholder="
                                                t('tasks.recurrencePlaceholder')
                                            "
                                        />
                                    </div>
                                    <div>
                                        <InputLabel
                                            for="priority"
                                            :value="t('tasks.priorityLabel')"
                                        />
                                        <select
                                            id="priority"
                                            v-model="newPriority"
                                            class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                                        >
                                            <option value="p1">
                                                {{ t('tasks.priorityP1') }}
                                            </option>
                                            <option value="p2">
                                                {{ t('tasks.priorityP2') }}
                                            </option>
                                            <option value="p3">
                                                {{ t('tasks.priorityP3') }}
                                            </option>
                                            <option value="p4">
                                                {{ t('tasks.priorityP4') }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <InputLabel
                                        for="new-notes"
                                        :value="t('tasks.notesOptional')"
                                    />
                                    <textarea
                                        id="new-notes"
                                        v-model="newNotes"
                                        rows="2"
                                        class="mt-1 block w-full rounded-md border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                                        :placeholder="t('tasks.notesPlaceholder')"
                                        @paste="onNotesPaste"
                                    />
                                </div>
                                <InputError class="mt-1" :message="formError" />
                            </div>
                        </div>

                        <div
                            class="overflow-hidden bg-white shadow-sm dark:bg-slate-900 sm:rounded-lg"
                        >
                            <div
                                class="border-b border-gray-100 px-6 py-3 text-sm text-gray-500 dark:border-slate-800 dark:text-slate-400"
                            >
                                <span v-if="navMode === 'today'">
                                    {{ t('tasks.dueTodayRow') }}
                                </span>
                                <span v-else-if="navMode === 'inbox'">
                                    {{ t('tasks.defaultListPrefix') }}
                                    {{
                                        taskLists.find(
                                            (l) => l.id === selectedListId,
                                        )?.title ?? ''
                                    }}
                                </span>
                                <span v-else>{{
                                    selectedListTitle ||
                                    t('tasks.noListSelected')
                                }}</span>
                            </div>
                            <div
                                v-if="selectedCount > 0"
                                class="flex flex-wrap items-center gap-2 border-b border-gray-100 bg-indigo-50/90 px-4 py-2.5 text-sm dark:border-slate-800 dark:bg-indigo-950/40"
                            >
                                <span
                                    class="font-medium text-gray-800 dark:text-slate-100"
                                    >{{
                                        t('tasks.bulkSelected', {
                                            count: selectedCount,
                                        })
                                    }}</span
                                >
                                <SecondaryButton
                                    type="button"
                                    :disabled="bulkWorking"
                                    @click="clearSelection"
                                >
                                    {{ t('tasks.bulkClear') }}
                                </SecondaryButton>
                                <SecondaryButton
                                    type="button"
                                    :disabled="bulkWorking"
                                    @click="bulkSelectAll"
                                >
                                    {{ t('tasks.bulkSelectAll') }}
                                </SecondaryButton>
                                <PrimaryButton
                                    type="button"
                                    :disabled="bulkWorking"
                                    @click="runBulkComplete"
                                >
                                    {{ t('tasks.bulkComplete') }}
                                </PrimaryButton>
                                <SecondaryButton
                                    type="button"
                                    :disabled="bulkWorking"
                                    @click="openBulkMoveModal"
                                >
                                    {{ t('tasks.bulkMove') }}
                                </SecondaryButton>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-25"
                                    :disabled="bulkWorking"
                                    @click="showBulkDeleteConfirm = true"
                                >
                                    {{ t('tasks.bulkDelete') }}
                                </button>
                            </div>
                            <ul
                                class="divide-y divide-gray-100 dark:divide-slate-800"
                                role="list"
                                aria-label="Tasks"
                            >
                                <li
                                    v-for="(task, taskIndex) in tasks"
                                    :key="`${taskKey(task)}`"
                                    :data-task-id="task.id"
                                    role="listitem"
                                    :aria-selected="
                                        focusedTaskIndex === taskIndex
                                    "
                                    :class="[
                                        'density-task-row flex cursor-pointer items-start gap-3 px-6 outline-none transition-shadow',
                                        indentClass(task),
                                        isTaskSelected(task)
                                            ? 'bg-indigo-50/70 dark:bg-indigo-950/30'
                                            : '',
                                        focusedTaskIndex === taskIndex
                                            ? 'ring-2 ring-inset ring-indigo-500 dark:ring-indigo-400'
                                            : '',
                                    ]"
                                    @click="onTaskRowClick(task, taskIndex, $event)"
                                >
                                    <input
                                        type="checkbox"
                                        class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-950"
                                        :checked="isTaskSelected(task)"
                                        :disabled="task._optimistic"
                                        :aria-label="t('tasks.bulkSelectTask')"
                                        @click.prevent="
                                            onSelectionCheckboxClick(
                                                task,
                                                taskIndex,
                                            )
                                        "
                                    />
                                    <input
                                        type="checkbox"
                                        class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-950"
                                        :checked="task.status === 'completed'"
                                        :disabled="task._optimistic"
                                        @click.stop
                                        @change="toggleComplete(task)"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span
                                                class="rounded px-2 py-0.5 text-xs font-semibold uppercase tracking-wide"
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
                                            <p
                                                :class="[
                                                    'font-medium text-gray-900 dark:text-slate-100',
                                                    task.status === 'completed'
                                                        ? 'line-through text-gray-400 dark:text-slate-500'
                                                        : '',
                                                ]"
                                            >
                                                {{ task.title }}
                                            </p>
                                            <span
                                                v-if="
                                                    navMode === 'today' &&
                                                    task._taskListTitle
                                                "
                                                class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-slate-800 dark:text-slate-300"
                                            >
                                                {{ task._taskListTitle }}
                                            </span>
                                        </div>
                                        <TaskNotesRichText
                                            v-if="task.notes"
                                            class="mt-1 text-sm text-gray-600 dark:text-slate-400"
                                            :text="task.notes"
                                        />
                                        <p
                                            v-if="task.due"
                                            class="mt-1 text-xs text-gray-500 dark:text-slate-500"
                                        >
                                            {{
                                                t('tasks.dueLabel', {
                                                    date: formatDateTime(
                                                        task.due,
                                                    ),
                                                })
                                            }}
                                        </p>
                                        <p
                                            v-if="task.recurrence?.length"
                                            class="mt-1 text-xs text-gray-500 dark:text-slate-500"
                                        >
                                            {{ task.recurrence.join(', ') }}
                                        </p>
                                        <div class="mt-2 max-w-48">
                                            <label
                                                class="sr-only"
                                                :for="`priority-${task.id}`"
                                                >{{
                                                    t('tasks.priorityLabel')
                                                }}</label
                                            >
                                            <select
                                                :id="`priority-${task.id}`"
                                                class="block w-full rounded-md border-gray-300 bg-white text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                                                :value="task.priority ?? 'p3'"
                                                :disabled="task._optimistic"
                                                @click.stop
                                                @change="
                                                    updatePriority(
                                                        task,
                                                        $event.target.value,
                                                    )
                                                "
                                            >
                                                <option value="p1">
                                                    {{ t('tasks.priorityP1') }}
                                                </option>
                                                <option value="p2">
                                                    {{ t('tasks.priorityP2') }}
                                                </option>
                                                <option value="p3">
                                                    {{ t('tasks.priorityP3') }}
                                                </option>
                                                <option value="p4">
                                                    {{ t('tasks.priorityP4') }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="text-sm text-red-600 hover:text-red-800 disabled:text-gray-400 dark:text-red-400 dark:hover:text-red-300 dark:disabled:text-slate-600"
                                        :disabled="task._optimistic"
                                        @click.stop="removeTask(task)"
                                    >
                                        {{ t('tasks.delete') }}
                                    </button>
                                </li>
                                <li
                                    v-if="
                                        tasks.length === 0 && navMode === 'today'
                                    "
                                    class="px-6 py-10 text-center text-sm text-gray-500 dark:text-slate-400"
                                >
                                    {{ t('tasks.emptyToday') }}
                                </li>
                                <li
                                    v-else-if="
                                        tasks.length === 0 &&
                                        navMode === 'inbox'
                                    "
                                    class="px-6 py-10 text-center text-sm text-gray-500 dark:text-slate-400"
                                >
                                    {{ t('tasks.emptyInbox') }}
                                </li>
                                <li
                                    v-else-if="
                                        tasks.length === 0 && navMode === 'list'
                                    "
                                    class="px-6 py-10 text-center text-sm text-gray-500 dark:text-slate-400"
                                >
                                    {{ t('tasks.emptyList') }}
                                </li>
                            </ul>
                        </div>

                        <p class="text-center text-sm text-gray-500 dark:text-slate-400">
                            {{ t('tasks.footer') }}
                            <Link
                                :href="route('dashboard')"
                                class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                >{{ t('tasks.dashboardLink') }}</Link
                            >
                        </p>
                    </div>

                    <!-- Mobile drawer overlay -->
                    <div
                        v-if="showListDrawer"
                        class="fixed inset-0 z-40 bg-black/40 lg:hidden"
                        @click.self="showListDrawer = false"
                    />
                    <div
                        v-if="showListDrawer"
                        class="fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] overflow-y-auto bg-white shadow-xl dark:bg-slate-900 lg:hidden"
                    >
                        <div
                            class="border-b border-gray-100 px-4 py-3 text-sm font-semibold text-gray-900 dark:border-slate-800 dark:text-slate-100"
                        >
                            {{ t('tasks.listsDrawerTitle') }}
                        </div>
                        <button
                            type="button"
                            class="block w-full px-4 py-3 text-left text-sm"
                            :class="navButtonClass(navMode === 'today')"
                            @click="
                                setNav('today');
                                showListDrawer = false;
                            "
                        >
                            {{ t('tasks.navToday') }}
                        </button>
                        <button
                            type="button"
                            class="block w-full px-4 py-3 text-left text-sm"
                            :class="navButtonClass(navMode === 'inbox')"
                            @click="
                                setNav('inbox');
                                showListDrawer = false;
                            "
                        >
                            {{ t('tasks.navInbox') }}
                        </button>
                        <div
                            class="border-t border-gray-100 px-4 py-2 text-xs font-semibold uppercase text-gray-400 dark:border-slate-800 dark:text-slate-500"
                        >
                            {{ t('tasks.allLists') }}
                        </div>
                        <button
                            v-for="list in taskLists"
                            :key="`drawer-${list.id}`"
                            type="button"
                            class="block w-full truncate px-4 py-3 text-left text-sm"
                            :class="
                                navButtonClass(
                                    navMode === 'list' &&
                                        selectedListId === list.id,
                                )
                            "
                            @click="selectList(list)"
                        >
                            {{ list.title }}
                        </button>
                    </div>

                    <!-- Mobile bottom nav -->
                    <nav
                        class="fixed bottom-0 left-0 right-0 z-30 flex border-t border-gray-200 bg-white pb-[env(safe-area-inset-bottom)] dark:border-slate-800 dark:bg-slate-900 lg:hidden"
                    >
                        <button
                            type="button"
                            class="flex flex-1 flex-col items-center py-2 text-xs font-medium"
                            :class="
                                navMode === 'today'
                                    ? 'text-indigo-700 dark:text-indigo-400'
                                    : 'text-gray-600 dark:text-slate-400'
                            "
                            @click="setNav('today')"
                        >
                            {{ t('tasks.navToday') }}
                        </button>
                        <button
                            type="button"
                            class="flex flex-1 flex-col items-center py-2 text-xs font-medium"
                            :class="
                                navMode === 'inbox'
                                    ? 'text-indigo-700 dark:text-indigo-400'
                                    : 'text-gray-600 dark:text-slate-400'
                            "
                            @click="setNav('inbox')"
                        >
                            {{ t('tasks.navInbox') }}
                        </button>
                        <button
                            type="button"
                            class="flex flex-1 flex-col items-center py-2 text-xs font-medium text-gray-600 dark:text-slate-400"
                            @click="showListDrawer = true"
                        >
                            {{ t('tasks.listsHeading') }}
                        </button>
                    </nav>
                </template>
            </div>
        </div>

        <TasksKeyboardShortcutsHelp
            :show="showKeyboardHelp"
            @close="showKeyboardHelp = false"
        />

        <Modal
            :show="showBulkDeleteConfirm"
            max-width="md"
            @close="showBulkDeleteConfirm = false"
        >
            <div class="p-6">
                <h3
                    class="text-lg font-semibold text-gray-900 dark:text-slate-100"
                >
                    {{ t('tasks.bulkDeleteConfirmTitle') }}
                </h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-slate-400">
                    {{
                        t('tasks.bulkDeleteConfirmBody', {
                            count: selectedCount,
                        })
                    }}
                </p>
                <div class="mt-6 flex justify-end gap-2">
                    <SecondaryButton
                        type="button"
                        :disabled="bulkWorking"
                        @click="showBulkDeleteConfirm = false"
                    >
                        {{ t('tasks.bulkDeleteConfirmCancel') }}
                    </SecondaryButton>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-25"
                        :disabled="bulkWorking"
                        @click="executeBulkDelete"
                    >
                        {{ t('tasks.bulkDeleteConfirm') }}
                    </button>
                </div>
            </div>
        </Modal>

        <Modal
            :show="showBulkMoveModal"
            max-width="md"
            @close="showBulkMoveModal = false"
        >
            <div class="p-6">
                <h3
                    class="text-lg font-semibold text-gray-900 dark:text-slate-100"
                >
                    {{ t('tasks.bulkMoveModalTitle') }}
                </h3>
                <div class="mt-4">
                    <InputLabel
                        for="bulk-move-dest"
                        :value="t('tasks.bulkMoveLabel')"
                    />
                    <select
                        id="bulk-move-dest"
                        v-model="bulkMoveDestination"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                    >
                        <option
                            v-for="list in taskLists"
                            :key="`bulk-${list.id}`"
                            :value="list.id"
                        >
                            {{ list.title }}
                        </option>
                    </select>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <SecondaryButton
                        type="button"
                        :disabled="bulkWorking"
                        @click="showBulkMoveModal = false"
                    >
                        {{ t('tasks.bulkMoveCancel') }}
                    </SecondaryButton>
                    <PrimaryButton
                        type="button"
                        :disabled="bulkWorking || !bulkMoveDestination"
                        @click="executeBulkMove"
                    >
                        {{ t('tasks.bulkMoveSubmit') }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal
            :show="showBulkResultModal"
            max-width="md"
            @close="showBulkResultModal = false"
        >
            <div class="p-6">
                <h3
                    class="text-lg font-semibold text-gray-900 dark:text-slate-100"
                >
                    {{ t('tasks.bulkResultTitle') }}
                </h3>
                <ul
                    class="mt-3 max-h-48 list-disc space-y-1 overflow-y-auto ps-5 text-sm text-gray-700 dark:text-slate-300"
                >
                    <li
                        v-for="(line, idx) in bulkFailureLines"
                        :key="`bf-${idx}`"
                    >
                        <span class="font-medium">{{ line.title }}</span
                        >:
                        {{ line.message }}
                    </li>
                </ul>
                <div class="mt-6 flex justify-end">
                    <PrimaryButton
                        type="button"
                        @click="showBulkResultModal = false"
                    >
                        {{ t('tasks.bulkResultClose') }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
