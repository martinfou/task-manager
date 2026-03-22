<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TaskNotesRichText from '@/Components/TaskNotesRichText.vue';
import TaskDetailEditPanel from '@/Components/TaskDetailEditPanel.vue';
import TasksIllustratedEmpty from '@/Components/TasksIllustratedEmpty.vue';
import TasksWorkflowHelp from '@/Components/TasksWorkflowHelp.vue';
import TasksKanbanBoard from '@/Components/TasksKanbanBoard.vue';
import TasksKeyboardShortcutsHelp from '@/Components/TasksKeyboardShortcutsHelp.vue';
import UndoToast from '@/Components/UndoToast.vue';
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
import {
    filterTasks,
    groupTasksByPriority,
} from '@/utils/taskFilters';
import {
    messageFromAxiosError,
    withReadRetry,
} from '@/utils/googleTaskError';
import { useUndoToast } from '@/composables/useUndoToast';

const VIEW_MODE_KEY = 'gt-task-view-mode';
const FILTER_STATE_KEY = 'gt-task-filters';
const NAV_PREFS_KEY = 'gt-task-nav';

const { t, te, locale } = useI18n();
const { formatDateTime } = useLocaleDate();
const {
    toast: undoToastRef,
    show: showUndoToast,
    undo: runUndoFromToast,
    holdPolling: undoHoldPolling,
} = useUndoToast();

/** Computed so the template always tracks the toast ref (avoids rare unwrap issues). */
const undoToastUi = computed(() => {
    const t = undoToastRef.value;
    if (!t) {
        return { visible: false, message: '' };
    }
    return { visible: true, message: t.message };
});

const props = defineProps({
    connected: { type: Boolean, default: false },
    pollIntervalMs: { type: Number, default: 5000 },
    maxBackoffMs: { type: Number, default: 120000 },
    semanticSearchAvailable: { type: Boolean, default: false },
});

/** @type {import('vue').Ref<'today'|'inbox'|'all'|'list'>} */
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
/** @type {import('vue').Ref<'new' | null>} */
const inspectorMode = ref(null);
const inspectorEditTaskKey = ref('');
const editTitle = ref('');
const editNotes = ref('');
const editDue = ref('');
const editRecurrence = ref('');
const editPriority = ref('p3');
const editSaving = ref(false);
const formError = ref('');
const pollBackoffMs = ref(props.pollIntervalMs);
const showListDrawer = ref(false);
const searchQuery = ref('');
const searchResults = ref([]);
const searchLoading = ref(false);
const searchError = ref('');
const searchTruncated = ref(false);
/** @type {import('vue').Ref<'keyword'|'semantic'>} */
const searchMode = ref('keyword');
const searchIndexEmpty = ref(false);
const searchReindexLoading = ref(false);
const showKeyboardHelp = ref(false);
const showWorkflowHelpModal = ref(false);
const showMobileSearch = ref(false);
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

/** @type {import('vue').Ref<'list'|'board'>} */
const viewMode = ref('list');
const filterCompletion = ref('needsAction');
const filterDue = ref('any');
const filterPriority = ref('all');
/** Empty string means all lists (Today view only). */
const filterListId = ref('');

const filterState = computed(() => ({
    completion: filterCompletion.value,
    due: filterDue.value,
    priority: filterPriority.value,
    listId:
        (navMode.value === 'today' || navMode.value === 'all') &&
        filterListId.value
            ? filterListId.value
            : null,
}));

const filteredTasks = computed(() => filterTasks(tasks.value, filterState.value));

const kanbanBuckets = computed(() =>
    groupTasksByPriority(filteredTasks.value),
);

function wantsCompletedFromApi() {
    return filterCompletion.value !== 'needsAction';
}

function cloneTaskForUndo(task) {
    return {
        ...task,
        _taskListId: task._taskListId,
        _taskListTitle: task._taskListTitle,
    };
}

const tasksForShortcuts = computed(() =>
    viewMode.value === 'list' ? filteredTasks.value : [],
);

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
    if (navMode.value === 'all') {
        return t('tasks.titleAll');
    }

    return selectedListTitle.value || t('tasks.titleTasks');
});

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

function canTaskDoubleClickEdit(task, target) {
    if (task._optimistic) {
        return false;
    }
    if (!(target instanceof HTMLElement)) {
        return false;
    }
    const tag = target.tagName;
    if (
        tag === 'INPUT' ||
        tag === 'BUTTON' ||
        tag === 'A' ||
        tag === 'SELECT' ||
        tag === 'TEXTAREA'
    ) {
        return false;
    }
    if (
        target.closest('button') ||
        target.closest('a') ||
        target.closest('input') ||
        target.closest('textarea')
    ) {
        return false;
    }
    return true;
}

function onTaskRowDoubleClick(task, e) {
    if (!canTaskDoubleClickEdit(task, e.target)) {
        return;
    }
    openEditInspector(task, e);
}

function onKanbanCardDoubleClick(task, e) {
    if (!canTaskDoubleClickEdit(task, e.target)) {
        return;
    }
    openEditInspector(task, e);
}

function openMobileSearchPanel() {
    showMobileSearch.value = true;
    void nextTick(() => {
        searchInputRef.value?.focus?.();
    });
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
            const t = filteredTasks.value[i];
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
    for (const t of filteredTasks.value) {
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
                message: messageFromAxiosError(
                    e,
                    t,
                    te,
                    'tasks.errors.updateFailed',
                ),
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
                message: messageFromAxiosError(
                    e,
                    t,
                    te,
                    'tasks.errors.deleteFailed',
                ),
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
    /** @type {{ task: object, sourceListId: string, dest: string }[]} */
    const movedOk = [];
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
            movedOk.push({
                task: cloneTaskForUndo(task),
                sourceListId,
                dest,
            });
        } catch (e) {
            bulkFailureLines.value.push({
                title: task.title,
                message: messageFromAxiosError(
                    e,
                    t,
                    te,
                    'tasks.errors.updateFailed',
                ),
            });
        }
    }
    bulkWorking.value = false;
    clearSelection();
    await pollOnce();
    if (bulkFailureLines.value.length > 0) {
        showBulkResultModal.value = true;
    } else if (movedOk.length === 1) {
        const m = movedOk[0];
        void showUndoToast({
            message: t('tasks.undo.moved'),
            onUndo: async () => {
                try {
                    await axios.post(
                        route('tasks.data.tasks.move', {
                            taskList: m.dest,
                            task: m.task.id,
                        }),
                        { destinationTasklist: m.sourceListId },
                    );
                    void pollOnce();
                } catch (e) {
                    loadError.value = messageFromAxiosError(
                        e,
                        t,
                        te,
                        'tasks.errors.updateFailed',
                    );
                }
            },
        });
    }
}

async function fetchTaskLists() {
    const { data } = await axios.get(route('tasks.data.task-lists'));
    taskLists.value = normalizeItems(data);
    const lists = taskLists.value;
    if (lists.length === 0) {
        selectedListId.value = null;
        return;
    }
    const current = selectedListId.value;
    if (!current || !lists.some((l) => l.id === current)) {
        selectedListId.value = lists[0].id;
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
        params: { showCompleted: wantsCompletedFromApi() },
    });
    if (data.taskList?.id) {
        selectedListId.value = data.taskList.id;
    }
    tasks.value = (data.items ?? []).map((t) => ({
        ...t,
        _taskListId: data.taskList?.id,
    }));
}

async function fetchAll() {
    const { data } = await axios.get(route('tasks.data.views.all'), {
        params: { showCompleted: wantsCompletedFromApi() },
    });
    tasks.value = (data.items ?? []).map((row) => ({
        ...row.task,
        _taskListId: row.taskListId,
        _taskListTitle: row.taskListTitle,
    }));
}

async function pollOnce() {
    loadError.value = '';
    try {
        await withReadRetry(async () => {
            await fetchTaskLists();
            if (navMode.value === 'today') {
                await fetchToday();
            } else if (navMode.value === 'inbox') {
                await fetchInbox();
            } else if (navMode.value === 'all') {
                await fetchAll();
            } else {
                await fetchTasksForList();
            }
        });
        pollBackoffMs.value = props.pollIntervalMs;
    } catch (e) {
        if (e.response?.status === 429) {
            pollBackoffMs.value = Math.min(
                props.maxBackoffMs,
                Math.max(props.pollIntervalMs, pollBackoffMs.value * 2),
            );
        }
        loadError.value = messageFromAxiosError(e, t, te);
    }
}

async function retryLoad() {
    loadError.value = '';
    await pollOnce();
}

async function pollLoop() {
    if (!undoHoldPolling.value) {
        await pollOnce();
    }
    pollTimer = setTimeout(pollLoop, pollBackoffMs.value);
}

watch(undoHoldPolling, (held, wasHeld) => {
    if (held === false && wasHeld === true && props.connected) {
        void pollOnce();
    }
});

async function setNav(mode) {
    showMobileSearch.value = false;
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
        await withReadRetry(async () => {
            if (mode === 'today') {
                await fetchToday();
            } else if (mode === 'inbox') {
                await fetchInbox();
            } else if (mode === 'all') {
                await fetchAll();
            } else {
                await fetchTasksForList();
            }
        });
    } catch (e) {
        loadError.value = messageFromAxiosError(e, t, te);
    }
}

async function selectList(list) {
    showMobileSearch.value = false;
    navMode.value = 'list';
    selectedListId.value = list.id;
    showListDrawer.value = false;
    if (!props.connected) {
        return;
    }
    loadError.value = '';
    try {
        await withReadRetry(async () => {
            await fetchTasksForList();
        });
    } catch (e) {
        loadError.value = messageFromAxiosError(e, t, te);
    }
}

async function onListDropdownChange() {
    showMobileSearch.value = false;
    navMode.value = 'list';
    loadError.value = '';
    try {
        await withReadRetry(async () => {
            await fetchTasksForList();
        });
    } catch (e) {
        loadError.value = messageFromAxiosError(e, t, te);
    }
}

async function executeSearchQuery() {
    const trimmed = searchQuery.value.trim();
    if (trimmed.length < 2) {
        return;
    }
    searchLoading.value = true;
    searchError.value = '';
    searchIndexEmpty.value = false;
    try {
        const { data } = await axios.get(route('tasks.data.search'), {
            params: { q: trimmed, mode: searchMode.value },
        });
        searchResults.value = data.items ?? [];
        searchTruncated.value = Boolean(data.truncated);
        searchIndexEmpty.value = Boolean(data.index_empty);
    } catch (e) {
        searchError.value = messageFromAxiosError(
            e,
            t,
            te,
            'tasks.searchError',
        );
        searchResults.value = [];
        searchTruncated.value = false;
        searchIndexEmpty.value = false;
    } finally {
        searchLoading.value = false;
    }
}

watch(searchQuery, (q) => {
    clearTimeout(searchDebounce);
    const trimmed = q.trim();
    if (trimmed.length < 2) {
        searchResults.value = [];
        searchTruncated.value = false;
        searchError.value = '';
        searchIndexEmpty.value = false;
        searchLoading.value = false;
        return;
    }
    searchLoading.value = true;
    searchError.value = '';
    searchDebounce = setTimeout(() => {
        void executeSearchQuery();
    }, 300);
});

watch(searchMode, () => {
    if (searchQuery.value.trim().length >= 2) {
        void executeSearchQuery();
    }
});

async function reindexSemanticIndex() {
    searchReindexLoading.value = true;
    searchError.value = '';
    try {
        await axios.post(route('tasks.data.search.reindex'));
        if (searchQuery.value.trim().length >= 2) {
            await executeSearchQuery();
        }
    } catch (e) {
        searchError.value = messageFromAxiosError(
            e,
            t,
            te,
            'tasks.searchError',
        );
    } finally {
        searchReindexLoading.value = false;
    }
}

async function openSearchResult(row) {
    clearSelection();
    closeDetailEdit();
    showMobileSearch.value = false;
    focusedTaskIndex.value = -1;
    searchQuery.value = '';
    searchResults.value = [];
    searchTruncated.value = false;
    searchError.value = '';
    searchIndexEmpty.value = false;

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
        await withReadRetry(async () => {
            await fetchTasksForList();
        });
        await nextTick();
        for (const el of document.querySelectorAll('[data-task-id]')) {
            if (el.getAttribute('data-task-id') === row.task.id) {
                el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                break;
            }
        }
    } catch (e) {
        loadError.value = messageFromAxiosError(e, t, te);
    }
}

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

function onInspectorNotesPaste(event) {
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
    const target = inspectorEditTaskKey.value ? editNotes : newNotes;
    if (target.value.trim() === '') {
        target.value = trimmed;
    } else {
        target.value = `${target.value.trimEnd()}\n${trimmed}`;
    }
}

function toDatetimeLocalValue(iso) {
    if (!iso) {
        return '';
    }
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) {
        return '';
    }
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function closeDetailEdit() {
    inspectorEditTaskKey.value = '';
    editSaving.value = false;
}

function closeInspector() {
    inspectorMode.value = null;
}

function openInspectorNew() {
    inspectorMode.value = 'new';
    inspectorEditTaskKey.value = '';
}

function openEditInspector(task, e) {
    if (e) {
        e.stopPropagation();
    }
    if (task._optimistic) {
        return;
    }
    const k = taskKey(task);
    if (inspectorEditTaskKey.value === k) {
        closeDetailEdit();
        return;
    }
    inspectorMode.value = null;
    inspectorEditTaskKey.value = k;
    editTitle.value = task.title ?? '';
    editNotes.value = task.notes ?? '';
    editDue.value = toDatetimeLocalValue(task.due);
    const rec = task.recurrence;
    editRecurrence.value = Array.isArray(rec)
        ? rec.filter(Boolean).join('\n')
        : '';
    editPriority.value = (task.priority ?? 'p3').toLowerCase();
}

async function saveEditedTask() {
    const key = inspectorEditTaskKey.value;
    const task = tasks.value.find((t) => taskKey(t) === key);
    if (!task || task._optimistic) {
        return;
    }
    editSaving.value = true;
    const listId = listIdForTask(task);
    const title = editTitle.value.trim();
    const notes = editNotes.value.trim();
    const rec = editRecurrence.value.trim();
    const body = {
        title,
        notes,
        priority: editPriority.value,
        recurrence: rec ? [rec] : [],
    };
    if (editDue.value) {
        body.due = new Date(editDue.value).toISOString();
    }
    try {
        const { data } = await axios.patch(
            route('tasks.data.tasks.update', {
                taskList: listId,
                task: task.id,
            }),
            body,
        );
        const merged = { ...data };
        if (task._taskListId) {
            merged._taskListId = task._taskListId;
            merged._taskListTitle = task._taskListTitle;
        }
        tasks.value = tasks.value.map((current) =>
            taskKey(current) === key ? merged : current,
        );
        closeDetailEdit();
        void pollOnce();
    } catch (e) {
        loadError.value = messageFromAxiosError(
            e,
            t,
            te,
            'tasks.errors.updateFailed',
        );
    } finally {
        editSaving.value = false;
    }
}

async function removeInspectedTask() {
    const key = inspectorEditTaskKey.value;
    const task = tasks.value.find((t) => taskKey(t) === key);
    if (!task) {
        return;
    }
    closeDetailEdit();
    await removeTask(task);
}

function escCloseInspector(e) {
    if (e.key !== 'Escape') {
        return;
    }
    if (showKeyboardHelp.value) {
        return;
    }
    if (inspectorEditTaskKey.value) {
        e.preventDefault();
        closeDetailEdit();
        return;
    }
    if (inspectorMode.value === 'new') {
        e.preventDefault();
        closeInspector();
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
    } else if (navMode.value === 'all') {
        optimistic._taskListId = listId;
        const meta = taskLists.value.find((l) => l.id === listId);
        if (meta?.title) {
            optimistic._taskListTitle = meta.title;
        }
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
        let mapped =
            navMode.value === 'today' ||
            navMode.value === 'inbox' ||
            navMode.value === 'all'
                ? { ...data, _taskListId: listId }
                : data;
        if (navMode.value === 'all') {
            const meta = taskLists.value.find((l) => l.id === listId);
            if (meta?.title) {
                mapped = { ...mapped, _taskListTitle: meta.title };
            }
        }
        tasks.value = tasks.value.map((t) =>
            t.id === tempId ? mapped : t,
        );
        newDue.value = '';
        newRecurrence.value = '';
        newPriority.value = 'p3';
        newNotes.value = '';
        if (inspectorMode.value === 'new') {
            closeInspector();
        }
        void pollOnce();
    } catch (e) {
        tasks.value = tasks.value.filter((t) => t.id !== tempId);
        formError.value = messageFromAxiosError(
            e,
            t,
            te,
            'tasks.errors.createFailed',
        );
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
        loadError.value = messageFromAxiosError(
            e,
            t,
            te,
            'tasks.errors.updateFailed',
        );
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
        if (nextStatus === 'completed') {
            void showUndoToast({
                message: t('tasks.undo.completed'),
                onUndo: async () => {
                    try {
                        const { data: d2 } = await axios.patch(
                            route('tasks.data.tasks.update', {
                                taskList: listId,
                                task: merged.id,
                            }),
                            { status: 'needsAction' },
                        );
                        const restored = { ...d2 };
                        if (merged._taskListId) {
                            restored._taskListId = merged._taskListId;
                            restored._taskListTitle = merged._taskListTitle;
                        }
                        tasks.value = tasks.value.map((x) =>
                            x.id === merged.id ? restored : x,
                        );
                        void pollOnce();
                    } catch (err) {
                        loadError.value = messageFromAxiosError(
                            err,
                            t,
                            te,
                            'tasks.errors.updateFailed',
                        );
                    }
                },
            });
        }
    } catch (e) {
        tasks.value = tasks.value.map((t) =>
            t.id === task.id ? prev : t,
        );
        loadError.value = messageFromAxiosError(
            e,
            t,
            te,
            'tasks.errors.updateFailed',
        );
    }
}

async function removeTask(task) {
    const listId = listIdForTask(task);
    const saved = cloneTaskForUndo(task);
    const titleSnippet = (saved.title || t('tasks.titlePlaceholder')).slice(
        0,
        80,
    );
    tasks.value = tasks.value.filter((t) => t.id !== task.id);
    void showUndoToast({
        message: t('tasks.undo.deleted', { title: titleSnippet }),
        onUndo: () => {
            tasks.value = [...tasks.value, saved];
            void pollOnce();
        },
        onCommit: async () => {
            try {
                await axios.delete(
                    route('tasks.data.tasks.destroy', {
                        taskList: listId,
                        task: saved.id,
                    }),
                );
                void pollOnce();
            } catch (e) {
                loadError.value = messageFromAxiosError(
                    e,
                    t,
                    te,
                    'tasks.errors.deleteFailed',
                );
            }
        },
    });
}

function indentClass(task) {
    return task.parent
        ? 'border-s-2 border-gt-border-strong ps-8'
        : '';
}

function navButtonClass(active) {
    return active
        ? 'bg-gt-accent-tint/60 text-gt-accent ring-1 ring-inset ring-gt-accent/25 dark:bg-gt-accent-tint/20 dark:text-gt-accent-hover dark:ring-gt-accent/30'
        : 'text-gt-ink-secondary hover:bg-gt-field-muted';
}

function viewModeToggleClass(active) {
    return active
        ? 'bg-gt-raised text-gt-ink shadow dark:bg-gt-field'
        : 'text-gt-muted hover:text-gt-ink';
}

function loadPersistedTaskUi() {
    if (typeof localStorage === 'undefined') {
        return;
    }
    try {
        const vm = localStorage.getItem(VIEW_MODE_KEY);
        if (vm === 'list' || vm === 'board') {
            viewMode.value = vm;
        }
        const raw = localStorage.getItem(FILTER_STATE_KEY);
        if (raw) {
            const o = JSON.parse(raw);
            if (
                o.completion === 'all' ||
                o.completion === 'needsAction' ||
                o.completion === 'completed'
            ) {
                filterCompletion.value = o.completion;
            }
            if (
                o.due === 'any' ||
                o.due === 'overdue' ||
                o.due === 'today' ||
                o.due === 'hasDue' ||
                o.due === 'noDue'
            ) {
                filterDue.value = o.due;
            }
            if (
                o.priority === 'all' ||
                o.priority === 'p1' ||
                o.priority === 'p2' ||
                o.priority === 'p3' ||
                o.priority === 'p4'
            ) {
                filterPriority.value = o.priority;
            }
            if (o.listId === null || o.listId === '') {
                filterListId.value = '';
            } else if (typeof o.listId === 'string') {
                filterListId.value = o.listId;
            }
            if (o.searchMode === 'keyword' || o.searchMode === 'semantic') {
                searchMode.value = o.searchMode;
            }
        }
        const navRaw = localStorage.getItem(NAV_PREFS_KEY);
        if (navRaw) {
            const n = JSON.parse(navRaw);
            if (
                n.navMode === 'today' ||
                n.navMode === 'inbox' ||
                n.navMode === 'all' ||
                n.navMode === 'list'
            ) {
                navMode.value = n.navMode;
            }
            if (typeof n.selectedListId === 'string' && n.selectedListId) {
                selectedListId.value = n.selectedListId;
            }
        }
    } catch {
        /* ignore */
    }
}

function persistTaskUi() {
    if (typeof localStorage === 'undefined') {
        return;
    }
    localStorage.setItem(VIEW_MODE_KEY, viewMode.value);
    localStorage.setItem(
        FILTER_STATE_KEY,
        JSON.stringify({
            completion: filterCompletion.value,
            due: filterDue.value,
            priority: filterPriority.value,
            listId: filterListId.value,
            searchMode: searchMode.value,
        }),
    );
}

function persistNavPrefs() {
    if (typeof localStorage === 'undefined') {
        return;
    }
    try {
        localStorage.setItem(
            NAV_PREFS_KEY,
            JSON.stringify({
                navMode: navMode.value,
                selectedListId: selectedListId.value,
            }),
        );
    } catch {
        /* ignore */
    }
}

function resetFilters() {
    filterCompletion.value = 'needsAction';
    filterDue.value = 'any';
    filterPriority.value = 'all';
    filterListId.value = '';
}

function onKanbanDropPriority({ taskId, listId, newPriority }) {
    const task = tasks.value.find(
        (t) => t.id === taskId && listIdForTask(t) === listId,
    );
    if (!task || task._optimistic) {
        return;
    }
    const cur = (task.priority ?? 'p3').toLowerCase();
    if (cur === newPriority) {
        return;
    }
    updatePriority(task, newPriority);
}

function onKanbanTaskClick(task, e) {
    const idx = filteredTasks.value.findIndex(
        (x) => taskKey(x) === taskKey(task),
    );
    if (idx >= 0) {
        onTaskRowClick(task, idx, e);
    }
}

function onKanbanSelectionClick(task) {
    const idx = filteredTasks.value.findIndex(
        (x) => taskKey(x) === taskKey(task),
    );
    if (idx >= 0) {
        onSelectionCheckboxClick(task, idx);
    }
}

useTasksKeyboardShortcuts({
    connected: toRef(props, 'connected'),
    showHelp: showKeyboardHelp,
    tasks: tasksForShortcuts,
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
    onGoAll: () => setNav('all'),
    onGoList: () => setNav('list'),
    onToggleComplete: (task) => toggleComplete(task),
    onInspectFocusedTask: () => {
        const list = tasksForShortcuts.value;
        const idx = focusedTaskIndex.value;
        if (idx < 0 || idx >= list.length) {
            return;
        }
        openEditInspector(list[idx]);
    },
});

watch(
    [inspectorMode, inspectorEditTaskKey],
    () => {
        window.removeEventListener('keydown', escCloseInspector);
        if (inspectorMode.value || inspectorEditTaskKey.value) {
            window.addEventListener('keydown', escCloseInspector);
        }
    },
    { immediate: true },
);

watch(navMode, () => {
    focusedTaskIndex.value = -1;
    clearSelection();
    closeDetailEdit();
});

watch(selectedListId, () => {
    clearSelection();
    closeDetailEdit();
});

watch(tasks, pruneSelectionFromTasks);

watch(
    () => filteredTasks.value.length,
    () => {
        if (focusedTaskIndex.value >= filteredTasks.value.length) {
            focusedTaskIndex.value =
                filteredTasks.value.length > 0
                    ? filteredTasks.value.length - 1
                    : -1;
        }
    },
);

watch(viewMode, () => {
    closeDetailEdit();
});

watch(filterCompletion, async () => {
    if (!props.connected) {
        return;
    }
    loadError.value = '';
    try {
        await withReadRetry(async () => {
            if (navMode.value === 'today') {
                await fetchToday();
            } else if (navMode.value === 'inbox') {
                await fetchInbox();
            } else if (navMode.value === 'all') {
                await fetchAll();
            } else if (navMode.value === 'list') {
                await fetchTasksForList();
            }
        });
        pollBackoffMs.value = props.pollIntervalMs;
    } catch (e) {
        if (e.response?.status === 429) {
            pollBackoffMs.value = Math.min(
                props.maxBackoffMs,
                Math.max(props.pollIntervalMs, pollBackoffMs.value * 2),
            );
        }
        loadError.value = messageFromAxiosError(e, t, te);
    }
});

watch(
    [
        viewMode,
        filterCompletion,
        filterDue,
        filterPriority,
        filterListId,
        searchMode,
    ],
    () => {
        persistTaskUi();
    },
    { deep: true },
);

watch([navMode, selectedListId], () => {
    persistNavPrefs();
});

watch(focusedTaskIndex, async (idx) => {
    await nextTick();
    if (idx < 0) {
        return;
    }
    const nodes = document.querySelectorAll('[data-task-id]');
    const el = nodes[idx];
    el?.scrollIntoView?.({ block: 'nearest', behavior: 'smooth' });
});

onMounted(() => {
    loadPersistedTaskUi();
    if (searchMode.value === 'semantic' && !props.semanticSearchAvailable) {
        searchMode.value = 'keyword';
    }
    if (!props.connected) {
        return;
    }
    void pollLoop();
});

watch(
    () => props.semanticSearchAvailable,
    (ok) => {
        if (!ok && searchMode.value === 'semantic') {
            searchMode.value = 'keyword';
        }
    },
);

onUnmounted(() => {
    clearTimeout(pollTimer);
    window.removeEventListener('keydown', escCloseInspector);
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
                        class="text-xl font-semibold leading-tight text-gt-ink"
                    >
                        {{ pageTitle }}
                    </h2>
                    <button
                        v-if="connected"
                        type="button"
                        class="text-xs font-medium text-gt-accent underline decoration-gt-accent/40 underline-offset-2 hover:text-gt-accent-hover"
                        @click="showKeyboardHelp = true"
                    >
                        {{ t('shortcuts.hint') }}
                    </button>
                </div>
                <div
                    v-if="connected"
                    class="flex w-full min-w-0 flex-col gap-2 sm:max-w-md"
                >
                    <div
                        class="flex items-center justify-end gap-2 lg:hidden"
                    >
                        <button
                            v-if="!showMobileSearch"
                            type="button"
                            class="rounded-md border border-gt-border-strong bg-gt-field px-3 py-1.5 text-sm font-medium text-gt-ink shadow-sm hover:bg-gt-field-muted"
                            @click="openMobileSearchPanel"
                        >
                            {{ t('tasks.searchOpen') }}
                        </button>
                        <button
                            v-else
                            type="button"
                            class="rounded-md px-3 py-1.5 text-sm font-medium text-gt-muted hover:text-gt-ink"
                            @click="showMobileSearch = false"
                        >
                            {{ t('tasks.searchClose') }}
                        </button>
                    </div>
                    <div
                        :class="[
                            'min-w-0',
                            showMobileSearch ? 'max-lg:block' : 'max-lg:hidden',
                            'lg:block',
                        ]"
                    >
                    <label class="sr-only" for="task-search">{{
                        t('tasks.searchLabel')
                    }}</label>
                    <div
                        v-if="semanticSearchAvailable"
                        class="mb-2 flex flex-wrap items-center gap-2"
                    >
                        <span class="text-xs text-gt-muted">{{
                            t('tasks.searchModeLabel')
                        }}</span>
                        <select
                            v-model="searchMode"
                            class="rounded-md border border-gt-border-strong bg-gt-field text-xs text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring"
                        >
                            <option value="keyword">
                                {{ t('tasks.searchModeKeyword') }}
                            </option>
                            <option value="semantic">
                                {{ t('tasks.searchModeSemantic') }}
                            </option>
                        </select>
                    </div>
                    <input
                        id="task-search"
                        ref="searchInputRef"
                        v-model="searchQuery"
                        type="search"
                        autocomplete="off"
                        class="block w-full rounded-md border border-gt-border-strong bg-gt-field text-sm text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring"
                        :placeholder="t('tasks.searchPlaceholder')"
                    />
                    <p
                        v-if="searchLoading"
                        class="mt-1 text-xs text-gt-muted"
                    >
                        {{ t('tasks.searchLoading') }}
                    </p>
                    <p
                        v-if="searchError"
                        class="mt-1 text-xs text-red-600 dark:text-red-400"
                    >
                        {{ searchError }}
                    </p>
                    <div
                        v-if="searchIndexEmpty && searchMode === 'semantic'"
                        class="mt-2 rounded-md border border-gt-accent/30 bg-gt-accent-tint/40 p-2 text-xs text-gt-accent dark:bg-gt-accent-tint/20 dark:text-gt-accent-hover"
                    >
                        <p>{{ t('tasks.searchIndexEmpty') }}</p>
                        <SecondaryButton
                            type="button"
                            class="mt-2"
                            :disabled="searchReindexLoading"
                            @click="reindexSemanticIndex"
                        >
                            {{
                                searchReindexLoading
                                    ? t('tasks.searchReindexLoading')
                                    : t('tasks.searchBuildIndex')
                            }}
                        </SecondaryButton>
                    </div>
                    <p
                        v-if="searchTruncated && searchResults.length > 0"
                        class="mt-1 text-xs text-amber-700 dark:text-amber-300"
                    >
                        {{ t('tasks.searchTruncated') }}
                    </p>
                    <ul
                        v-if="searchResults.length > 0"
                        class="mt-2 max-h-56 overflow-y-auto rounded-md border border-gt-border bg-gt-raised text-sm shadow-sm"
                        role="listbox"
                    >
                        <li
                            v-for="row in searchResults"
                            :key="`${row.taskListId}-${row.task.id}`"
                            class="border-b border-gt-border last:border-0"
                        >
                            <button
                                type="button"
                                class="w-full px-3 py-2 text-left hover:bg-gt-field-muted"
                                @click="openSearchResult(row)"
                            >
                                <span
                                    class="block font-medium text-gt-ink"
                                    >{{ row.task.title }}</span
                                >
                                <span
                                    class="block text-xs text-gt-muted"
                                    >{{ row.taskListTitle }}</span
                                >
                                <TaskNotesRichText
                                    v-if="row.snippet"
                                    class="mt-0.5 line-clamp-2 text-xs text-gt-muted"
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
                            !searchIndexEmpty &&
                            searchResults.length === 0
                        "
                        class="mt-1 text-xs text-gt-muted"
                    >
                        {{ t('tasks.searchNoResults') }}
                    </p>
                    </div>
                </div>
            </div>
        </template>

        <div class="overflow-x-hidden pb-24 lg:pb-8">
            <div
                class="mx-auto flex max-w-6xl flex-col gap-6 lg:flex-row lg:px-6"
            >
                <div
                    v-if="!connected"
                    class="overflow-hidden gt-surface sm:mx-6 sm:rounded-lg lg:mx-0"
                >
                    <div class="density-stack density-card-padding max-w-2xl">
                        <h3
                            class="text-lg font-semibold text-gt-ink"
                        >
                            {{ t('tasks.connectHeadline') }}
                        </h3>
                        <p
                            class="text-sm leading-relaxed text-gt-muted"
                        >
                            {{ t('tasks.connectSubhead') }}
                        </p>
                        <ul
                            class="ms-1 list-inside list-disc space-y-1 text-sm text-gt-muted"
                        >
                            <li>{{ t('tasks.connectBullet1') }}</li>
                            <li>{{ t('tasks.connectBullet2') }}</li>
                        </ul>
                        <div class="flex flex-wrap items-center gap-3 pt-1">
                            <Link
                                :href="route('google.redirect')"
                                class="inline-flex items-center rounded-md border border-transparent bg-gt-accent px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gt-accent-hover focus:outline-none focus:ring-2 focus:ring-gt-accent-ring focus:ring-offset-2 focus:ring-offset-gt-raised active:opacity-90"
                            >
                                {{ t('tasks.connectGoogle') }}
                            </Link>
                            <Link
                                :href="route('profile.edit')"
                                class="text-sm font-medium text-gt-accent underline decoration-gt-accent/40 underline-offset-2 hover:text-gt-accent-hover"
                            >
                                {{ t('tasks.connectProfileLink') }}
                            </Link>
                        </div>
                    </div>
                </div>

                <template v-else>
                    <!-- Desktop sidebar -->
                    <aside
                        class="hidden w-56 shrink-0 flex-col gap-1 border-r border-gt-border pr-4 lg:flex"
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
                        <button
                            type="button"
                            class="w-full rounded-md px-3 py-2 text-left text-sm font-medium"
                            :class="navButtonClass(navMode === 'all')"
                            @click="setNav('all')"
                        >
                            {{ t('tasks.navAll') }}
                        </button>
                        <div
                            class="mt-4 border-t border-gt-border pt-3 text-xs font-semibold uppercase tracking-wide text-gt-subtle"
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

                    <div
                        class="flex min-w-0 flex-1 flex-col lg:flex-row lg:items-start"
                    >
                        <div
                            class="min-w-0 flex-1 density-stack px-4 sm:px-6 lg:px-0"
                        >
                        <button
                            type="button"
                            class="w-full rounded-md border border-gt-border bg-gt-raised px-3 py-2 text-left text-sm font-medium text-gt-accent shadow-sm hover:bg-gt-field-muted lg:hidden"
                            @click="showWorkflowHelpModal = true"
                        >
                            {{ t('tasks.helpLinkMobile') }}
                        </button>
                        <details
                            class="hidden rounded-md border border-gt-border bg-gt-raised text-sm text-gt-ink-secondary shadow-sm density-card-padding lg:block"
                        >
                            <summary
                                class="cursor-pointer font-medium text-gt-ink"
                            >
                                {{ t('tasks.helpSummary') }}
                            </summary>
                            <div
                                class="mt-3 border-t border-gt-border pt-3"
                            >
                                <TasksWorkflowHelp />
                            </div>
                        </details>

                        <div
                            v-if="loadError"
                            class="flex flex-col gap-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-100 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <p class="min-w-0 flex-1">
                                {{ loadError }}
                            </p>
                            <SecondaryButton
                                type="button"
                                class="shrink-0 self-start sm:self-center"
                                @click="retryLoad"
                            >
                                {{ t('tasks.retry') }}
                            </SecondaryButton>
                        </div>

                        <div
                            class="overflow-hidden gt-surface sm:rounded-lg"
                        >
                            <div
                                class="border-b border-gt-border px-4 py-3 sm:px-6"
                            >
                                <div
                                    v-if="navMode === 'list'"
                                    class="mb-3 lg:hidden"
                                >
                                    <InputLabel
                                        for="list"
                                        :value="t('tasks.activeList')"
                                    />
                                    <select
                                        id="list"
                                        v-model="selectedListId"
                                        class="mt-1 block w-full rounded-md border border-gt-border-strong bg-gt-field text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring"
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
                                <InputLabel
                                    for="title"
                                    :value="t('tasks.newTask')"
                                />
                                <div
                                    class="mt-1 flex flex-col gap-2 sm:flex-row sm:items-center"
                                >
                                    <TextInput
                                        id="title"
                                        ref="newTaskTitleRef"
                                        v-model="newTitle"
                                        type="text"
                                        class="block min-w-0 w-full flex-1"
                                        :placeholder="t('tasks.titlePlaceholder')"
                                        @keyup.enter="submitNewTask"
                                    />
                                    <div
                                        class="flex shrink-0 flex-wrap gap-2"
                                    >
                                        <PrimaryButton
                                            type="button"
                                            @click="submitNewTask"
                                        >
                                            {{ t('tasks.add') }}
                                        </PrimaryButton>
                                        <SecondaryButton
                                            type="button"
                                            @click="openInspectorNew"
                                        >
                                            {{ t('tasks.moreFields') }}
                                        </SecondaryButton>
                                    </div>
                                </div>
                                <InputError
                                    class="mt-2"
                                    :message="formError"
                                />
                            </div>
                            <div
                                class="border-b border-gt-border px-6 py-3 text-sm text-gt-muted"
                            >
                                <span v-if="navMode === 'today'">
                                    {{ t('tasks.dueTodayRow') }}
                                </span>
                                <span v-else-if="navMode === 'all'">
                                    {{ t('tasks.allListsRow') }}
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
                                class="flex flex-wrap items-end gap-3 border-b border-gt-border px-4 py-3"
                            >
                                <div
                                    class="inline-flex gap-0.5 rounded-lg bg-gt-field-muted p-0.5"
                                    role="group"
                                    :aria-label="t('tasks.viewModeGroup')"
                                >
                                    <button
                                        type="button"
                                        class="rounded-md px-3 py-1.5 text-xs font-medium transition"
                                        :class="viewModeToggleClass(viewMode === 'list')"
                                        @click="viewMode = 'list'"
                                    >
                                        {{ t('tasks.viewList') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-md px-3 py-1.5 text-xs font-medium transition"
                                        :class="viewModeToggleClass(viewMode === 'board')"
                                        @click="viewMode = 'board'"
                                    >
                                        {{ t('tasks.viewBoard') }}
                                    </button>
                                </div>
                                <div class="min-w-[8rem]">
                                    <InputLabel
                                        for="filter-completion"
                                        :value="t('tasks.filterCompletion')"
                                    />
                                    <select
                                        id="filter-completion"
                                        v-model="filterCompletion"
                                        class="mt-1 block w-full rounded-md border border-gt-border-strong bg-gt-field text-sm text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring"
                                    >
                                        <option value="all">
                                            {{ t('tasks.filterCompletionAll') }}
                                        </option>
                                        <option value="needsAction">
                                            {{
                                                t('tasks.filterCompletionActive')
                                            }}
                                        </option>
                                        <option value="completed">
                                            {{
                                                t('tasks.filterCompletionDone')
                                            }}
                                        </option>
                                    </select>
                                </div>
                                <div class="min-w-[8rem]">
                                    <InputLabel
                                        for="filter-due"
                                        :value="t('tasks.filterDue')"
                                    />
                                    <select
                                        id="filter-due"
                                        v-model="filterDue"
                                        class="mt-1 block w-full rounded-md border border-gt-border-strong bg-gt-field text-sm text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring"
                                    >
                                        <option value="any">
                                            {{ t('tasks.filterDueAny') }}
                                        </option>
                                        <option value="overdue">
                                            {{ t('tasks.filterDueOverdue') }}
                                        </option>
                                        <option value="today">
                                            {{ t('tasks.filterDueToday') }}
                                        </option>
                                        <option value="hasDue">
                                            {{ t('tasks.filterDueHas') }}
                                        </option>
                                        <option value="noDue">
                                            {{ t('tasks.filterDueNo') }}
                                        </option>
                                    </select>
                                </div>
                                <div class="min-w-[8rem]">
                                    <InputLabel
                                        for="filter-prio"
                                        :value="t('tasks.filterPriority')"
                                    />
                                    <select
                                        id="filter-prio"
                                        v-model="filterPriority"
                                        class="mt-1 block w-full rounded-md border border-gt-border-strong bg-gt-field text-sm text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring"
                                    >
                                        <option value="all">
                                            {{ t('tasks.filterPriorityAll') }}
                                        </option>
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
                                <div
                                    v-if="navMode === 'today' || navMode === 'all'"
                                    class="min-w-[10rem]"
                                >
                                    <InputLabel
                                        for="filter-list"
                                        :value="t('tasks.filterList')"
                                    />
                                    <select
                                        id="filter-list"
                                        v-model="filterListId"
                                        class="mt-1 block w-full rounded-md border border-gt-border-strong bg-gt-field text-sm text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring"
                                    >
                                        <option value="">
                                            {{ t('tasks.filterListAll') }}
                                        </option>
                                        <option
                                            v-for="list in taskLists"
                                            :key="`flt-${list.id}`"
                                            :value="list.id"
                                        >
                                            {{ list.title }}
                                        </option>
                                    </select>
                                </div>
                                <SecondaryButton
                                    type="button"
                                    class="mt-6"
                                    @click="resetFilters"
                                >
                                    {{ t('tasks.resetFilters') }}
                                </SecondaryButton>
                            </div>
                            <div
                                v-if="selectedCount > 0"
                                class="flex flex-wrap items-center gap-2 border-b border-gt-border bg-gt-accent-tint/35 px-4 py-2.5 text-sm dark:bg-gt-accent-tint/15"
                            >
                                <span
                                    class="font-medium text-gt-ink"
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
                            <p
                                v-if="viewMode === 'list'"
                                class="border-b border-gt-border px-6 py-2 text-xs leading-relaxed text-gt-muted"
                            >
                                <span
                                    class="me-2 font-medium text-gt-ink-secondary"
                                    >{{ t('tasks.checkboxRowHintShort') }}:</span
                                >
                                {{ t('tasks.checkboxRowHint') }}
                            </p>
                            <ul
                                v-if="viewMode === 'list'"
                                class="divide-y divide-gt-border"
                                role="list"
                                aria-label="Tasks"
                            >
                                <li
                                    v-for="(task, taskIndex) in filteredTasks"
                                    :key="`${taskKey(task)}`"
                                    role="listitem"
                                    :aria-selected="
                                        focusedTaskIndex === taskIndex
                                    "
                                    :aria-expanded="
                                        inspectorEditTaskKey === taskKey(task)
                                    "
                                    class="border-b border-gt-border last:border-b-0"
                                >
                                    <div
                                        :data-task-id="task.id"
                                        :class="[
                                            'density-task-row flex cursor-pointer items-start gap-3 px-6 py-3 outline-none transition-shadow',
                                            indentClass(task),
                                            isTaskSelected(task)
                                                ? 'bg-gt-accent-tint/40 dark:bg-gt-accent-tint/15'
                                                : '',
                                            focusedTaskIndex === taskIndex
                                                ? 'ring-2 ring-inset ring-gt-accent dark:ring-gt-accent'
                                                : '',
                                        ]"
                                        @click="
                                            onTaskRowClick(
                                                task,
                                                taskIndex,
                                                $event,
                                            )
                                        "
                                        @dblclick="onTaskRowDoubleClick(task, $event)"
                                    >
                                    <input
                                        type="checkbox"
                                        class="mt-1 rounded border-gt-border-strong text-gt-accent focus:ring-gt-accent-ring dark:bg-gt-field"
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
                                        class="mt-1 rounded border-gt-border-strong text-gt-accent focus:ring-gt-accent-ring dark:bg-gt-field"
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
                                                    'font-medium text-gt-ink',
                                                    task.status === 'completed'
                                                        ? 'line-through text-gt-subtle'
                                                        : '',
                                                ]"
                                            >
                                                {{ task.title }}
                                            </p>
                                            <span
                                                v-if="
                                                    (navMode === 'today' ||
                                                        navMode === 'all') &&
                                                    task._taskListTitle
                                                "
                                                class="rounded bg-gt-field-muted px-2 py-0.5 text-xs text-gt-muted"
                                            >
                                                {{ task._taskListTitle }}
                                            </span>
                                        </div>
                                        <TaskNotesRichText
                                            v-if="task.notes"
                                            class="mt-1 text-sm text-gt-muted"
                                            :text="task.notes"
                                        />
                                        <p
                                            v-if="task.due"
                                            class="mt-1 text-xs text-gt-muted"
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
                                            class="mt-1 text-xs text-gt-muted"
                                        >
                                            {{ task.recurrence.join(', ') }}
                                        </p>
                                    </div>
                                    <div
                                        class="flex shrink-0 flex-col items-end gap-2 sm:flex-row sm:items-center"
                                    >
                                        <button
                                            type="button"
                                            class="rounded-md px-2 py-1 text-xs font-medium text-gt-accent hover:bg-gt-accent-tint/30 disabled:text-gt-subtle dark:hover:bg-gt-accent-tint/15 dark:disabled:text-gt-subtle"
                                            :disabled="task._optimistic"
                                            @click.stop="openEditInspector(task)"
                                        >
                                            {{ t('tasks.taskDetails') }}
                                        </button>
                                        <button
                                            type="button"
                                            class="text-sm text-red-600 hover:text-red-800 disabled:text-gt-subtle dark:text-red-400 dark:hover:text-red-300 dark:disabled:text-gt-subtle"
                                            :disabled="task._optimistic"
                                            @click.stop="removeTask(task)"
                                        >
                                            {{ t('tasks.delete') }}
                                        </button>
                                    </div>
                                    </div>
                                    <div
                                        v-if="
                                            inspectorEditTaskKey ===
                                            taskKey(task)
                                        "
                                        class="border-t border-gt-border bg-gt-field-muted/30 px-6 py-4 dark:bg-gt-field/25"
                                        @click.stop
                                    >
                                        <p
                                            class="mb-3 text-sm font-semibold text-gt-ink"
                                        >
                                            {{ t('tasks.inspectorEditTitle') }}
                                        </p>
                                        <TaskDetailEditPanel
                                            :title="editTitle"
                                            :due="editDue"
                                            :recurrence="editRecurrence"
                                            :priority="editPriority"
                                            :notes="editNotes"
                                            :saving="editSaving"
                                            @update:title="editTitle = $event"
                                            @update:due="editDue = $event"
                                            @update:recurrence="
                                                editRecurrence = $event
                                            "
                                            @update:priority="
                                                editPriority = $event
                                            "
                                            @update:notes="editNotes = $event"
                                            @save="saveEditedTask"
                                            @close="closeDetailEdit"
                                            @delete="removeInspectedTask"
                                            @notes-paste="onInspectorNotesPaste"
                                        />
                                    </div>
                                </li>
                                <TasksIllustratedEmpty
                                    v-if="
                                        tasks.length === 0 && navMode === 'today'
                                    "
                                    :title="t('tasks.emptyTodayTitle')"
                                    :description="t('tasks.emptyTodayBody')"
                                />
                                <TasksIllustratedEmpty
                                    v-else-if="
                                        tasks.length === 0 &&
                                        navMode === 'inbox'
                                    "
                                    :title="t('tasks.emptyInboxTitle')"
                                    :description="t('tasks.emptyInboxBody')"
                                />
                                <TasksIllustratedEmpty
                                    v-else-if="
                                        tasks.length === 0 && navMode === 'all'
                                    "
                                    :title="t('tasks.emptyAllTitle')"
                                    :description="t('tasks.emptyAllBody')"
                                />
                                <TasksIllustratedEmpty
                                    v-else-if="
                                        tasks.length === 0 && navMode === 'list'
                                    "
                                    :title="t('tasks.emptyListTitle')"
                                    :description="t('tasks.emptyListBody')"
                                />
                                <TasksIllustratedEmpty
                                    v-else-if="
                                        tasks.length > 0 &&
                                        filteredTasks.length === 0
                                    "
                                    :title="t('tasks.noFilterMatchTitle')"
                                    :description="t('tasks.noFilterMatchBody')"
                                    show-reset-filters
                                    @reset-filters="resetFilters"
                                />
                            </ul>
                            <div
                                v-else
                                class="border-t border-gt-border px-3 py-3"
                            >
                                <TasksIllustratedEmpty
                                    v-if="
                                        tasks.length > 0 &&
                                        filteredTasks.length === 0
                                    "
                                    tag="div"
                                    :title="t('tasks.noFilterMatchTitle')"
                                    :description="t('tasks.noFilterMatchBody')"
                                    show-reset-filters
                                    @reset-filters="resetFilters"
                                />
                                <template v-else>
                                    <p
                                        class="mb-2 text-xs leading-relaxed text-gt-muted"
                                    >
                                        <span
                                            class="me-2 font-medium text-gt-ink-secondary"
                                            >{{ t('tasks.checkboxRowHintShort') }}:</span
                                        >
                                        {{ t('tasks.checkboxRowHint') }}
                                    </p>
                                    <p
                                        class="mb-3 text-xs text-gt-muted"
                                    >
                                        {{ t('tasks.kanbanHint') }}
                                    </p>
                                    <TasksKanbanBoard
                                        :buckets="kanbanBuckets"
                                        :nav-mode="navMode"
                                        :is-task-selected="isTaskSelected"
                                        :task-key="taskKey"
                                        :list-id-for-task="listIdForTask"
                                        @drop-priority="onKanbanDropPriority"
                                        @task-click="onKanbanTaskClick"
                                        @selection-click="onKanbanSelectionClick"
                                        @toggle-complete="toggleComplete"
                                        @inspect-task="openEditInspector"
                                        @card-dblclick="
                                            (task, e) =>
                                                onKanbanCardDoubleClick(
                                                    task,
                                                    e,
                                                )
                                        "
                                    >
                                        <template #task-detail="{ task }">
                                            <div
                                                v-if="
                                                    inspectorEditTaskKey ===
                                                    taskKey(task)
                                                "
                                                class="border-t border-gt-border bg-gt-field-muted/30 px-2 py-3 dark:bg-gt-field/25"
                                                @click.stop
                                            >
                                                <p
                                                    class="mb-2 text-xs font-semibold text-gt-ink"
                                                >
                                                    {{
                                                        t(
                                                            'tasks.inspectorEditTitle',
                                                        )
                                                    }}
                                                </p>
                                                <TaskDetailEditPanel
                                                    :title="editTitle"
                                                    :due="editDue"
                                                    :recurrence="
                                                        editRecurrence
                                                    "
                                                    :priority="editPriority"
                                                    :notes="editNotes"
                                                    :saving="editSaving"
                                                    @update:title="
                                                        editTitle = $event
                                                    "
                                                    @update:due="
                                                        editDue = $event
                                                    "
                                                    @update:recurrence="
                                                        editRecurrence = $event
                                                    "
                                                    @update:priority="
                                                        editPriority = $event
                                                    "
                                                    @update:notes="
                                                        editNotes = $event
                                                    "
                                                    @save="saveEditedTask"
                                                    @close="closeDetailEdit"
                                                    @delete="
                                                        removeInspectedTask
                                                    "
                                                    @notes-paste="
                                                        onInspectorNotesPaste
                                                    "
                                                />
                                            </div>
                                        </template>
                                    </TasksKanbanBoard>
                                </template>
                            </div>
                        </div>

                        <p class="text-center text-sm text-gt-muted">
                            {{ t('tasks.footer') }}
                            <Link
                                :href="route('dashboard')"
                                class="text-gt-accent hover:text-gt-accent-hover"
                                >{{ t('tasks.dashboardLink') }}</Link
                            >
                        </p>
                        </div>

                        <template v-if="inspectorMode === 'new'">
                            <div
                                class="fixed inset-0 z-[45] bg-black/40 lg:hidden"
                                role="presentation"
                                @click="closeInspector"
                            />
                            <aside
                                class="fixed inset-x-0 bottom-0 z-50 flex max-h-[min(88vh,100dvh)] flex-col overflow-hidden rounded-t-2xl border border-gt-border border-b-0 bg-gt-raised pb-[env(safe-area-inset-bottom)] shadow-[0_-8px_30px_rgba(0,0,0,0.12)] dark:shadow-[0_-8px_30px_rgba(0,0,0,0.35)] lg:sticky lg:top-24 lg:z-0 lg:max-h-[calc(100vh-7rem)] lg:w-[min(26rem,36vw)] lg:shrink-0 lg:rounded-none lg:border-0 lg:border-l lg:border-gt-border lg:border-b-0 lg:pb-0 lg:shadow-none"
                                role="complementary"
                                :aria-label="t('tasks.inspectorNewTitle')"
                            >
                                <div
                                    class="mx-auto mt-2 h-1.5 w-10 shrink-0 rounded-full bg-gt-border-strong lg:hidden"
                                />
                                <div
                                    class="flex shrink-0 items-center justify-between gap-2 border-b border-gt-border px-4 py-3"
                                >
                                    <h3
                                        class="text-base font-semibold text-gt-ink"
                                    >
                                        {{ t('tasks.inspectorNewTitle') }}
                                    </h3>
                                    <button
                                        type="button"
                                        class="rounded-md p-2 text-gt-muted hover:bg-gt-field-muted hover:text-gt-ink"
                                        :aria-label="t('tasks.closeInspector')"
                                        @click="closeInspector"
                                    >
                                        <span class="sr-only">{{
                                            t('tasks.closeInspector')
                                        }}</span>
                                        <svg
                                            class="h-5 w-5"
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
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </button>
                                </div>
                                <div
                                    class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5"
                                >
                                    <div class="space-y-3">
                                        <div>
                                            <InputLabel
                                                for="new-due"
                                                :value="t('tasks.dueOptional')"
                                            />
                                            <TextInput
                                                id="new-due"
                                                v-model="newDue"
                                                type="datetime-local"
                                                class="mt-1 block w-full"
                                            />
                                        </div>
                                        <div>
                                            <InputLabel
                                                for="new-recurrence"
                                                :value="
                                                    t('tasks.recurrenceOptional')
                                                "
                                            />
                                            <TextInput
                                                id="new-recurrence"
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
                                                for="new-priority"
                                                :value="t('tasks.priorityLabel')"
                                            />
                                            <select
                                                id="new-priority"
                                                v-model="newPriority"
                                                class="mt-1 block w-full rounded-md border border-gt-border-strong bg-gt-field text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring"
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
                                        <div>
                                            <InputLabel
                                                for="new-notes"
                                                :value="t('tasks.notesOptional')"
                                            />
                                            <textarea
                                                id="new-notes"
                                                v-model="newNotes"
                                                rows="4"
                                                class="mt-1 block w-full rounded-md border border-gt-border-strong bg-gt-field text-sm text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring"
                                                :placeholder="
                                                    t('tasks.notesPlaceholder')
                                                "
                                                @paste="onInspectorNotesPaste"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="flex shrink-0 flex-wrap gap-2 border-t border-gt-border px-4 py-3 sm:px-5"
                                >
                                    <PrimaryButton
                                        type="button"
                                        @click="submitNewTask"
                                    >
                                        {{ t('tasks.addTaskFromInspector') }}
                                    </PrimaryButton>
                                    <SecondaryButton
                                        type="button"
                                        @click="closeInspector"
                                    >
                                        {{ t('tasks.closeInspector') }}
                                    </SecondaryButton>
                                </div>
                            </aside>
                        </template>
                    </div>

                    <!-- Mobile drawer overlay -->
                    <div
                        v-if="showListDrawer"
                        class="fixed inset-0 z-40 bg-black/40 lg:hidden"
                        @click.self="showListDrawer = false"
                    />
                    <div
                        v-if="showListDrawer"
                        class="fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] overflow-y-auto bg-gt-raised shadow-xl lg:hidden"
                    >
                        <div
                            class="border-b border-gt-border px-4 py-3 text-sm font-semibold text-gt-ink"
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
                        <button
                            type="button"
                            class="block w-full px-4 py-3 text-left text-sm"
                            :class="navButtonClass(navMode === 'all')"
                            @click="
                                setNav('all');
                                showListDrawer = false;
                            "
                        >
                            {{ t('tasks.navAll') }}
                        </button>
                        <div
                            class="border-t border-gt-border px-4 py-2 text-xs font-semibold uppercase text-gt-subtle"
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
                        class="fixed bottom-0 left-0 right-0 z-30 flex border-t border-gt-border bg-gt-raised pb-[env(safe-area-inset-bottom)] lg:hidden"
                    >
                        <button
                            type="button"
                            class="flex flex-1 flex-col items-center py-2 text-xs font-medium"
                            :class="
                                navMode === 'today'
                                    ? 'text-gt-accent dark:text-gt-accent-hover'
                                    : 'text-gt-muted'
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
                                    ? 'text-gt-accent dark:text-gt-accent-hover'
                                    : 'text-gt-muted'
                            "
                            @click="setNav('inbox')"
                        >
                            {{ t('tasks.navInbox') }}
                        </button>
                        <button
                            type="button"
                            class="flex flex-1 flex-col items-center py-2 text-xs font-medium"
                            :class="
                                navMode === 'all'
                                    ? 'text-gt-accent dark:text-gt-accent-hover'
                                    : 'text-gt-muted'
                            "
                            @click="setNav('all')"
                        >
                            {{ t('tasks.navAllShort') }}
                        </button>
                        <button
                            type="button"
                            class="flex flex-1 flex-col items-center py-2 text-xs font-medium text-gt-muted"
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
            :show="showWorkflowHelpModal"
            max-width="lg"
            @close="showWorkflowHelpModal = false"
        >
            <div class="p-6">
                <h3
                    class="text-lg font-semibold text-gt-ink"
                >
                    {{ t('tasks.helpSummary') }}
                </h3>
                <div class="mt-4">
                    <TasksWorkflowHelp :hide-title="true" />
                </div>
                <div class="mt-6 flex justify-end">
                    <PrimaryButton
                        type="button"
                        @click="showWorkflowHelpModal = false"
                    >
                        {{ t('tasks.workflowHelpDone') }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal
            :show="showBulkDeleteConfirm"
            max-width="md"
            @close="showBulkDeleteConfirm = false"
        >
            <div class="p-6">
                <h3
                    class="text-lg font-semibold text-gt-ink"
                >
                    {{ t('tasks.bulkDeleteConfirmTitle') }}
                </h3>
                <p class="mt-2 text-sm text-gt-muted">
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
                    class="text-lg font-semibold text-gt-ink"
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
                        class="mt-1 block w-full rounded-md border border-gt-border-strong bg-gt-field text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring"
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
                    class="text-lg font-semibold text-gt-ink"
                >
                    {{ t('tasks.bulkResultTitle') }}
                </h3>
                <ul
                    class="mt-3 max-h-48 list-disc space-y-1 overflow-y-auto ps-5 text-sm text-gt-ink-secondary"
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

    <Teleport to="body">
        <div
            v-if="undoToastUi.visible"
            class="pointer-events-none fixed inset-x-0 bottom-0 z-[9999] flex justify-center p-4 pb-[calc(1rem+env(safe-area-inset-bottom))]"
        >
            <UndoToast
                :message="undoToastUi.message"
                @undo="() => runUndoFromToast()"
            />
        </div>
    </Teleport>
</template>
