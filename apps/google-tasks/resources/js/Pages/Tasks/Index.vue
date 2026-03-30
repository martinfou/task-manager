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
import TaskDeferMenu from '@/Components/TaskDeferMenu.vue';
import TaskListRowSwipe from '@/Components/TaskListRowSwipe.vue';
import TaskPriorityDueMeta from '@/Components/TaskPriorityDueMeta.vue';
import TasksCommandPalette from '@/Components/TasksCommandPalette.vue';
import TaskListOrganiseSheet from '@/Components/TaskListOrganiseSheet.vue';
import UndoToast from '@/Components/UndoToast.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useLocaleDate } from '@/composables/useLocaleDate';
import {
    registerTasksCommandPaletteOpener,
    unregisterTasksCommandPaletteOpener,
} from '@/composables/commandPaletteBridge';
import { useTasksKeyboardShortcuts } from '@/composables/useTasksKeyboardShortcuts';
import { useVisibilitySoftRefresh } from '@/composables/useVisibilitySoftRefresh';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    computed,
    nextTick,
    onMounted,
    onUnmounted,
    ref,
    watch,
} from 'vue';
import { useI18n } from 'vue-i18n';
import {
    filterTasks,
    groupTasksByPriority,
    groupTasksByDueLane,
    dueLaneDateMapping,
} from '@/utils/taskFilters';
import {
    DEFAULT_GLOBAL_TASK_SORT_MODE,
    GLOBAL_TASK_SORT_STORAGE_KEY,
    normalizeGlobalSortMode,
    sortTasksGlobally,
} from '@/utils/taskSort';
import {
    messageFromAxiosError,
    withReadRetry,
} from '@/utils/googleTaskError';
import { computeDeferDueIso } from '@/utils/deferPresets';
import { useUndoToast } from '@/composables/useUndoToast';
import { useTaskCache } from '@/composables/useTaskCache';
import { useListOrder } from '@/composables/useListOrder';
import { usePullToRefresh } from '@/composables/usePullToRefresh';

const VIEW_MODE_KEY = 'gt-task-view-mode';
const FILTER_STATE_KEY = 'gt-task-filters';
const NAV_PREFS_KEY = 'gt-task-nav';

const { t, te, locale } = useI18n();
const { formatDateTime } = useLocaleDate();
const {
    toast: undoToastRef,
    show: showUndoToast,
    undo: runUndoFromToast,
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
const {
    autoSort: listAutoSort,
    pinnedLists,
    unpinnedLists,
    showOrganiseSheet,
    contextMenu: listContextMenu,
    saveOrder: saveListOrder,
    togglePin: toggleListPin,
    showContextMenu: showListContextMenu,
    hideContextMenu: hideListContextMenu,
    setAutoSort: setListAutoSort,
} = useListOrder(taskLists);
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
/** Target list while editing (may differ from source — move on save). */
const editListId = ref('');
const editSaving = ref(false);
const formError = ref('');
/** ISO timestamp of when the current view was last cached on the server. */
const viewCachedAt = ref(null);
/** US-046: toolbar / keyboard / banner force-sync in flight. */
const syncFromGoogleLoading = ref(false);
const pollBackoffMs = ref(props.pollIntervalMs);
const showListDrawer = ref(false);
const searchQuery = ref('');
const searchResults = ref([]);
const searchLoading = ref(false);
/** Ref-counted while tasks for the current nav are (re)fetched */
const tasksLoadDepth = ref(0);
const tasksLoading = computed(() => tasksLoadDepth.value > 0);

function pushTasksLoad() {
    tasksLoadDepth.value++;
}

function popTasksLoad() {
    tasksLoadDepth.value = Math.max(0, tasksLoadDepth.value - 1);
}

async function withTasksLoad(fn) {
    pushTasksLoad();
    try {
        await fn();
    } finally {
        popTasksLoad();
    }
}
const searchError = ref('');
const searchTruncated = ref(false);
/** @type {import('vue').Ref<'keyword'|'semantic'>} */
const searchMode = ref('keyword');
const searchIndexEmpty = ref(false);
const searchReindexLoading = ref(false);
const showKeyboardHelp = ref(false);
const showWorkflowHelpModal = ref(false);
const showMobileSearch = ref(false);
const mobileComposerOpen = ref(false);
const showCommandPalette = ref(false);
/** Collapsible filter bar on small screens; forced open at lg+ */
const filtersDetailsRef = ref(null);
let removeFiltersMqListener = null;
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
/** US-031: list-row swipe on coarse pointer + narrow viewport */
const swipeRowsEnabled = ref(false);
let removeSwipeMqListener = null;
const swipeSheetReset = ref(0);
const showSwipeMoreModal = ref(false);
/** @type {import('vue').Ref<object|null>} */
const swipeMoreForTask = ref(null);
/** @type {import('vue').Ref<object|null>} */
const swipeMoveTask = ref(null);
let searchDebounce = null;
let pollOnceInFlight = false;
/** US-037: in-memory task cache for instant list switching. */
const taskCache = useTaskCache();
/** US-044: pull-to-refresh container ref and composable. */
const taskAreaRef = ref(null);
const {
    pullIndicatorStyle,
    pullProgress,
    isPulling: pullRefreshActive,
    isRefreshing: pullRefreshBusy,
} = usePullToRefresh(taskAreaRef, forceRefreshCurrentView);
/** Skip one-shot filter watcher while restoring localStorage (avoids racing pollOnce). */
const suppressCompletionSyncFetch = ref(false);
/** Google Tasks data API returned 403 (disconnected / stale Inertia props). */
const googleTasksForbidden = ref(false);

/** @type {import('vue').Ref<'list'|'board'>} */
const viewMode = ref('list');
/** @type {import('vue').Ref<'priority'|'due'>} US-032: board grouping mode */
const boardGroupMode = ref('priority');
const BOARD_GROUP_KEY = 'gt-board-group-mode';
const filterCompletion = ref('needsAction');
const filterDue = ref('any');
const filterPriority = ref('all');
const filterDate = ref(null); // 'YYYY-MM-DD'
const filterWeekday = ref(null); // 'monday', etc.
/** Empty string means all lists (Today view only). */
const filterListId = ref('');

const filterState = computed(() => ({
    completion: filterCompletion.value,
    due: filterDue.value,
    priority: filterPriority.value,
    date: filterDate.value,
    weekday: filterWeekday.value,
    listId:
        (navMode.value === 'today' || navMode.value === 'all') &&
        filterListId.value
            ? filterListId.value
            : null,
}));

const filteredTasks = computed(() => filterTasks(tasks.value, filterState.value));

/** US-027: global sort mode (Due first | Priority first), persisted per browser. */
const globalSortMode = ref(DEFAULT_GLOBAL_TASK_SORT_MODE);

const sortedFilteredTasks = computed(() =>
    sortTasksGlobally(
        filteredTasks.value,
        globalSortMode.value,
        typeof locale.value === 'string' ? locale.value : 'en',
    ),
);

watch(globalSortMode, (v) => {
    if (typeof localStorage === 'undefined') {
        return;
    }
    try {
        localStorage.setItem(GLOBAL_TASK_SORT_STORAGE_KEY, v);
    } catch {
        /* ignore */
    }
});

const TASKS_ONBOARDING_KEY = 'gt-tasks-onboarding-v1-dismissed';
const showTasksOnboarding = ref(false);
const showGlobalSortSheet = ref(false);

/** US-033: duplicate detection state */
const showDuplicatesModal = ref(false);
const duplicatesLoading = ref(false);
const duplicatePairs = ref([]);
const duplicatesIndexEmpty = ref(false);
const duplicatesError = ref('');
const duplicateMergeConfirm = ref(null); // { pair, keepSide: 'A'|'B' }
const tasksOnboardingDismissed = ref(false);
let offeredTasksOnboardingThisMount = false;

try {
    if (typeof localStorage !== 'undefined') {
        tasksOnboardingDismissed.value =
            localStorage.getItem(TASKS_ONBOARDING_KEY) === '1';
    }
} catch {
    /* ignore */
}

const globalSortModeCurrentLabel = computed(() =>
    globalSortMode.value === 'priority_first'
        ? t('tasks.sort.priorityFirst')
        : t('tasks.sort.dueFirst'),
);

/** US-033: load duplicate candidates from server */
async function openDuplicatesModal() {
    showDuplicatesModal.value = true;
    duplicatesLoading.value = true;
    duplicatePairs.value = [];
    duplicatesIndexEmpty.value = false;
    duplicatesError.value = '';
    duplicateMergeConfirm.value = null;
    try {
        const { data } = await axios.get(route('tasks.data.duplicates'));
        duplicatePairs.value = data.pairs ?? [];
        duplicatesIndexEmpty.value = data.index_empty ?? false;
    } catch {
        duplicatesError.value = t('tasks.duplicates.error');
    } finally {
        duplicatesLoading.value = false;
    }
}

function startMerge(pair, keepSide) {
    duplicateMergeConfirm.value = { pair, keepSide };
}

async function confirmMerge() {
    const { pair, keepSide } = duplicateMergeConfirm.value;
    const removeTask = keepSide === 'A' ? pair.taskB : pair.taskA;
    duplicateMergeConfirm.value = null;

    try {
        await axios.patch(
            route('tasks.data.tasks.update', {
                taskList: removeTask.taskListId,
                task: removeTask.taskId,
            }),
            { status: 'completed' },
        );
        // Remove the pair from the list
        duplicatePairs.value = duplicatePairs.value.filter((p) => p !== pair);
        // Refresh local task data
        syncCacheAfterMutation(removeTask.taskListId);
        void showUndoToast({
            message: t('tasks.undo.completed'),
            onUndo: async () => {
                try {
                    await axios.patch(
                        route('tasks.data.tasks.update', {
                            taskList: removeTask.taskListId,
                            task: removeTask.taskId,
                        }),
                        { status: 'needsAction' },
                    );
                    syncCacheAfterMutation(removeTask.taskListId);
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
    } catch (e) {
        loadError.value = messageFromAxiosError(
            e,
            t,
            te,
            'tasks.errors.updateFailed',
        );
    }
}

function dismissTasksOnboarding() {
    showTasksOnboarding.value = false;
    tasksOnboardingDismissed.value = true;
    try {
        localStorage.setItem(TASKS_ONBOARDING_KEY, '1');
    } catch {
        /* ignore */
    }
}

const tasksDataAvailable = computed(
    () => props.connected && !googleTasksForbidden.value,
);

watch(
    () => [tasksDataAvailable.value, taskLists.value.length],
    () => {
        if (
            !tasksDataAvailable.value ||
            tasksOnboardingDismissed.value ||
            offeredTasksOnboardingThisMount
        ) {
            return;
        }
        if (taskLists.value.length > 0) {
            offeredTasksOnboardingThisMount = true;
            showTasksOnboarding.value = true;
        }
    },
);


/**
 * Any HTTP 403 from Tasks JSON routes stops polling and shows the connect / refresh UI.
 * Payloads differ: middleware (“Google Tasks is not connected…”), Google proxy (“Google denied…”),
 * or non-JSON 403 pages — all previously left the poll loop running and spammed the console.
 *
 * @param {unknown} e
 * @returns {boolean}
 */
function consumeGoogleTasksForbidden(e) {
    const st =
        e &&
        typeof e === 'object' &&
        'response' in e &&
        e.response &&
        typeof e.response === 'object'
            ? Number(
                  /** @type {{ status?: unknown }} */ (e.response).status,
              )
            : NaN;
    if (
        !e ||
        typeof e !== 'object' ||
        !('response' in e) ||
        !e.response ||
        typeof e.response !== 'object' ||
        !Number.isFinite(st) ||
        st !== 403
    ) {
        return false;
    }
    googleTasksForbidden.value = true;
    taskCache.flush(); /* US-037: clear cache on disconnect */
    return true;
}

function reloadTasksPage() {
    googleTasksForbidden.value = false;
    router.reload({
        preserveScroll: true,
    });
}

const kanbanBuckets = computed(() => {
    const loc = typeof locale.value === 'string' ? locale.value : 'en';
    const sortMode = globalSortMode.value;
    if (boardGroupMode.value === 'due') {
        const raw = groupTasksByDueLane(filteredTasks.value);
        return {
            overdue: sortTasksGlobally(raw.overdue, sortMode, loc),
            today: sortTasksGlobally(raw.today, sortMode, loc),
            thisWeek: sortTasksGlobally(raw.thisWeek, sortMode, loc),
            laterNoDate: sortTasksGlobally(raw.laterNoDate, sortMode, loc),
        };
    }
    const raw = groupTasksByPriority(filteredTasks.value);
    return {
        p1: sortTasksGlobally(raw.p1, sortMode, loc),
        p2: sortTasksGlobally(raw.p2, sortMode, loc),
        p3: sortTasksGlobally(raw.p3, sortMode, loc),
        p4: sortTasksGlobally(raw.p4, sortMode, loc),
    };
});

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
    viewMode.value === 'list' ? sortedFilteredTasks.value : [],
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

/** Target Google list for new tasks (composer / “More” sheet). */
const newTaskListId = ref(null);

function syncNewTaskListFromNav() {
    const lists = taskLists.value;
    if (!lists.length) {
        newTaskListId.value = null;
        return;
    }
    if (navMode.value === 'list' && selectedListId.value) {
        newTaskListId.value = selectedListId.value;
    } else if (defaultListId.value) {
        newTaskListId.value = defaultListId.value;
    }
}

watch([navMode, selectedListId, defaultListId], () => {
    syncNewTaskListFromNav();
});

watch(
    taskLists,
    (lists) => {
        if (!lists?.length) {
            newTaskListId.value = null;
            return;
        }
        if (
            newTaskListId.value &&
            lists.some((l) => l.id === newTaskListId.value)
        ) {
            return;
        }
        syncNewTaskListFromNav();
    },
    { flush: 'post' },
);

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

/** Nav-aware loading line: warmer than a generic spinner (see `.impeccable.md`). */
const tasksLoadingLabel = computed(() => {
    switch (navMode.value) {
        case 'today':
            return t('tasks.loadingTasksToday');
        case 'inbox':
            return t('tasks.loadingTasksInbox');
        case 'all':
            return t('tasks.loadingTasksAll');
        case 'list': {
            const name = selectedListTitle.value?.trim();
            if (name) {
                return t('tasks.loadingTasksListNamed', { list: name });
            }
            return t('tasks.loadingTasksList');
        }
        default:
            return t('tasks.loadingTasks');
    }
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

const commandPaletteItems = computed(() => {
    if (!tasksDataAvailable.value) {
        return [];
    }
    const items = [];
    const push = (id, sectionKey, labelKey, keywords = '') => {
        items.push({
            id,
            sectionKey,
            label: t(labelKey),
            keywords,
        });
    };
    push(
        'action-search',
        'actions',
        'tasks.commandPalette.cmdSearch',
        'search find',
    );
    push(
        'action-sync-google',
        'actions',
        'tasks.commandPalette.cmdSyncGoogle',
        'refresh sync google reload',
    );
    push(
        'action-new-task',
        'actions',
        'tasks.commandPalette.cmdNewTask',
        'new task composer title',
    );
    push(
        'quick-add',
        'actions',
        'tasks.commandPalette.cmdQuickAdd',
        'quick add create',
    );
    push(
        'view-today',
        'views',
        'tasks.commandPalette.cmdToday',
        'today',
    );
    push(
        'view-inbox',
        'views',
        'tasks.commandPalette.cmdInbox',
        'inbox',
    );
    push(
        'view-all',
        'views',
        'tasks.commandPalette.cmdAll',
        'all lists',
    );
    for (const list of taskLists.value) {
        items.push({
            id: `list-${list.id}`,
            sectionKey: 'lists',
            label: list.title,
            keywords: list.title,
        });
    }
    return items;
});

async function handleCommandPaletteSelect(id) {
    await nextTick();
    if (id === 'action-sync-google') {
        await userInitiatedSyncFromGoogle();
        return;
    }
    if (id === 'action-search') {
        openMobileSearchPanel();
        await nextTick();
        searchInputRef.value?.focus?.();
        searchInputRef.value?.select?.();
        return;
    }
    if (id === 'action-new-task') {
        focusedTaskIndex.value = -1;
        await nextTick();
        newTaskTitleRef.value?.focus?.();
        return;
    }
    if (id === 'view-today') {
        await setNav('today');
        return;
    }
    if (id === 'view-inbox') {
        await setNav('inbox');
        return;
    }
    if (id === 'view-all') {
        await setNav('all');
        return;
    }
    if (id.startsWith('list-')) {
        const listId = id.slice('list-'.length);
        const list = taskLists.value.find((l) => l.id === listId);
        if (list) {
            await selectList(list);
        }
    }
}

async function handleCommandPaletteQuickAdd(title) {
    newTitle.value = title;
    await nextTick();
    await submitNewTask();
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
            const t = sortedFilteredTasks.value[i];
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
    for (const t of sortedFilteredTasks.value) {
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
    syncCacheAfterMutation(selectedListId.value || '');
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
    syncCacheAfterMutation(selectedListId.value || '');
    if (bulkFailureLines.value.length > 0) {
        showBulkResultModal.value = true;
    }
}

function openBulkMoveModal() {
    swipeMoveTask.value = null;
    bulkMoveDestination.value = taskLists.value[0]?.id ?? null;
    showBulkMoveModal.value = true;
}

function onBulkMoveModalClose() {
    showBulkMoveModal.value = false;
    swipeMoveTask.value = null;
}

function updateSwipeRowsEnabled() {
    if (typeof window === 'undefined' || !window.matchMedia) {
        return;
    }
    swipeRowsEnabled.value = window.matchMedia(
        '(max-width: 639px) and (hover: none) and (pointer: coarse)',
    ).matches;
}

function openSwipeMoreSheet(task) {
    if (task._optimistic) {
        return;
    }
    swipeMoreForTask.value = task;
    swipeSheetReset.value++;
    showSwipeMoreModal.value = true;
}

function closeSwipeMoreSheet() {
    showSwipeMoreModal.value = false;
    swipeMoreForTask.value = null;
}

function onSwipeRowComplete(task) {
    swipeSheetReset.value++;
    void toggleComplete(task);
}

function onSwipeDeferPreset(preset) {
    const task = swipeMoreForTask.value;
    if (!task) {
        return;
    }
    closeSwipeMoreSheet();
    void applyDeferPreset(task, preset);
}

function openSwipeMoveFromSheet() {
    const task = swipeMoreForTask.value;
    if (!task) {
        return;
    }
    swipeMoveTask.value = task;
    const sid = listIdForTask(task);
    bulkMoveDestination.value =
        taskLists.value.find((l) => l.id !== sid)?.id ??
        taskLists.value[0]?.id ??
        null;
    closeSwipeMoreSheet();
    swipeSheetReset.value++;
    showBulkMoveModal.value = true;
}

function onSwipeSheetDelete() {
    const task = swipeMoreForTask.value;
    if (!task) {
        return;
    }
    closeSwipeMoreSheet();
    void removeTask(task);
}

async function executeBulkMove() {
    const dest = bulkMoveDestination.value;
    if (!dest) {
        return;
    }
    const singleFromSwipe = swipeMoveTask.value;
    showBulkMoveModal.value = false;
    swipeMoveTask.value = null;

    if (singleFromSwipe) {
        bulkWorking.value = true;
        bulkFailureLines.value = [];
        /** @type {{ task: object, sourceListId: string, dest: string }[]} */
        const movedOk = [];
        const task = singleFromSwipe;
        const sourceListId = listIdForTask(task);
        if (sourceListId !== dest) {
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
        syncCacheAfterMutation(sourceListId);
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
                        syncCacheAfterMutation(m.sourceListId);
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
        return;
    }

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
    syncCacheAfterMutation(selectedListId.value || '');
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
                    syncCacheAfterMutation(m.sourceListId);
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
    if (!tasksDataAvailable.value) {
        return;
    }
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

async function onOrganiseListsSave(orderedLists, sortMode) {
    taskLists.value = orderedLists;
    setListAutoSort(sortMode);
    await saveListOrder(orderedLists, sortMode);
}

async function onListContextMenuPin() {
    const list = listContextMenu.value.list;
    if (!list) return;
    hideListContextMenu();
    await toggleListPin(list.id);
    // Re-fetch to get correct server order
    await fetchTaskLists();
}

async function fetchTasksForList() {
    if (!tasksDataAvailable.value) {
        return;
    }
    if (!selectedListId.value) {
        tasks.value = [];
        return;
    }
    const listId = selectedListId.value;
    const { data } = await axios.get(
        route('tasks.data.tasks', { taskList: listId }),
        { params: { showCompleted: true } },
    );
    const result = normalizeItems(data).map((t) => {
        const { _taskListId, _taskListTitle, ...rest } = t;
        return rest;
    });
    tasks.value = result;
    viewCachedAt.value = null;
    taskCache.set('list', listId, result);
}

async function fetchToday({ forceRefresh = false } = {}) {
    if (!tasksDataAvailable.value) {
        return;
    }
    const params = {};
    if (forceRefresh) params.forceRefresh = 1;
    const { data } = await axios.get(route('tasks.data.views.today'), { params });
    const result = (data.items ?? []).map((row) => ({
        ...row.task,
        _taskListId: row.taskListId,
        _taskListTitle: row.taskListTitle,
    }));
    tasks.value = result;
    viewCachedAt.value = data.cachedAt ?? null;
    taskCache.set('today', null, result);
}

async function fetchInbox({ forceRefresh = false } = {}) {
    if (!tasksDataAvailable.value) {
        return;
    }
    const params = { showCompleted: wantsCompletedFromApi() };
    if (forceRefresh) params.forceRefresh = 1;
    const { data } = await axios.get(route('tasks.data.views.inbox'), { params });
    if (data.taskList?.id) {
        selectedListId.value = data.taskList.id;
    }
    const result = (data.items ?? []).map((t) => ({
        ...t,
        _taskListId: data.taskList?.id,
    }));
    tasks.value = result;
    viewCachedAt.value = data.cachedAt ?? null;
    taskCache.set('inbox', null, result);
}

async function fetchAll({ forceRefresh = false } = {}) {
    if (!tasksDataAvailable.value) {
        return;
    }
    const params = { showCompleted: wantsCompletedFromApi() };
    if (forceRefresh) params.forceRefresh = 1;
    const { data } = await axios.get(route('tasks.data.views.all'), { params });
    const result = (data.items ?? []).map((row) => ({
        ...row.task,
        _taskListId: row.taskListId,
        _taskListTitle: row.taskListTitle,
    }));
    tasks.value = result;
    viewCachedAt.value = data.cachedAt ?? null;
    taskCache.set('all', null, result);
}

/** US-044: Force refresh the current view, bypassing server cache. Used by pull-to-refresh. */
async function forceRefreshCurrentView() {
    if (!tasksDataAvailable.value) return;
    const mode = navMode.value;
    taskCache.invalidate(mode, mode === 'list' ? selectedListId.value : null);
    try {
        await withReadRetry(async () => {
            await fetchTaskLists();
            if (mode === 'today') await fetchToday({ forceRefresh: true });
            else if (mode === 'inbox') await fetchInbox({ forceRefresh: true });
            else if (mode === 'all') await fetchAll({ forceRefresh: true });
            else await fetchTasksForList();
        });
    } catch (e) {
        if (!consumeGoogleTasksForbidden(e)) {
            loadError.value = messageFromAxiosError(e, t, te);
        }
    }
}

/** US-046: soft re-fetch when tab becomes visible (uses server cache when fresh). */
async function softRefreshCurrentView() {
    if (!tasksDataAvailable.value || googleTasksForbidden.value) {
        return;
    }
    try {
        await withReadRetry(async () => {
            await fetchTaskLists();
            if (!tasksDataAvailable.value) {
                return;
            }
            const mode = navMode.value;
            if (mode === 'today') await fetchToday();
            else if (mode === 'inbox') await fetchInbox();
            else if (mode === 'all') await fetchAll();
            else await fetchTasksForList();
        });
    } catch {
        /* background refresh — do not surface errors */
    }
}

/** US-046: explicit sync from toolbar, banner, ⌘⌥R / Ctrl+Alt+R, command palette. */
async function userInitiatedSyncFromGoogle() {
    if (!tasksDataAvailable.value || syncFromGoogleLoading.value) {
        return;
    }
    syncFromGoogleLoading.value = true;
    loadError.value = '';
    try {
        await forceRefreshCurrentView();
    } finally {
        syncFromGoogleLoading.value = false;
    }
}

useVisibilitySoftRefresh(
    () =>
        tasksDataAvailable.value &&
        !googleTasksForbidden.value &&
        !syncFromGoogleLoading.value &&
        !pullRefreshBusy.value,
    () => {
        void softRefreshCurrentView();
    },
);

async function pollOnce() {
    if (!props.connected || googleTasksForbidden.value) {
        return;
    }
    if (pollOnceInFlight) {
        return;
    }
    pollOnceInFlight = true;
    loadError.value = '';
    try {
        await withReadRetry(async () => {
            await fetchTaskLists();
            if (!tasksDataAvailable.value) {
                return;
            }
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
        if (consumeGoogleTasksForbidden(e)) {
            loadError.value = messageFromAxiosError(e, t, te);
            return;
        }
        if (e.response?.status === 429) {
            pollBackoffMs.value = Math.min(
                props.maxBackoffMs,
                Math.max(props.pollIntervalMs, pollBackoffMs.value * 2),
            );
        }
        loadError.value = messageFromAxiosError(e, t, te);
    } finally {
        pollOnceInFlight = false;
    }
}

async function retryLoad() {
    loadError.value = '';
    await withTasksLoad(async () => {
        await pollOnce();
    });
}

/**
 * DEF-002: After a mutation, sync the current tasks.value into the client-side
 * cache for the active view. This replaces the old pollOnce() pattern which
 * re-fetched from the server and could return stale cached data.
 *
 * @param {string|null} affectedListId - list ID affected by the mutation
 */
function syncCacheAfterMutation(affectedListId = null) {
    taskCache.syncAfterMutation(
        navMode.value,
        selectedListId.value,
        tasks.value,
        affectedListId,
    );
}


async function setNav(mode) {
    showMobileSearch.value = false;
    navMode.value = mode;
    showListDrawer.value = false;
    if (mode === 'list' && !selectedListId.value && taskLists.value.length > 0) {
        selectedListId.value = taskLists.value[0].id;
    }
    if (!tasksDataAvailable.value) {
        return;
    }
    /* US-037: show cached tasks immediately if available */
    const listId = mode === 'list' ? selectedListId.value : null;
    const cached = taskCache.get(mode, listId);
    if (cached) {
        tasks.value = cached;
        /* Background refresh — no loading spinner */
        loadError.value = '';
        fetchNavBackground(mode);
        return;
    }
    /* Cold cache: full loading state */
    await withTasksLoad(async () => {
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
            consumeGoogleTasksForbidden(e);
            loadError.value = messageFromAxiosError(e, t, te);
        }
    });
}

/** US-037: background refresh for cache-first navigation (no loading spinner). */
async function fetchNavBackground(mode) {
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
        if (!consumeGoogleTasksForbidden(e)) {
            loadError.value = messageFromAxiosError(e, t, te);
        }
    }
}

async function selectList(list) {
    showMobileSearch.value = false;
    navMode.value = 'list';
    selectedListId.value = list.id;
    showListDrawer.value = false;
    if (!tasksDataAvailable.value) {
        return;
    }
    /* US-037: show cached tasks immediately if available */
    const cached = taskCache.get('list', list.id);
    if (cached) {
        tasks.value = cached;
        loadError.value = '';
        fetchNavBackground('list');
        return;
    }
    /* Cold cache: full loading state */
    await withTasksLoad(async () => {
        loadError.value = '';
        try {
            await withReadRetry(async () => {
                await fetchTasksForList();
            });
        } catch (e) {
            consumeGoogleTasksForbidden(e);
            loadError.value = messageFromAxiosError(e, t, te);
        }
    });
}

/** When the composer list changes in list mode, follow the selection (tasks + URL state). */
async function onComposerListChange() {
    if (!newTaskListId.value || navMode.value !== 'list') {
        return;
    }
    if (!tasksDataAvailable.value) {
        return;
    }
    if (selectedListId.value === newTaskListId.value) {
        return;
    }
    selectedListId.value = newTaskListId.value;
    /* US-037: cache-first for composer list change */
    const cached = taskCache.get('list', newTaskListId.value);
    if (cached) {
        tasks.value = cached;
        loadError.value = '';
        fetchNavBackground('list');
    } else {
        await withTasksLoad(async () => {
            loadError.value = '';
            try {
                await withReadRetry(() => fetchTasksForList());
            } catch (e) {
                consumeGoogleTasksForbidden(e);
                loadError.value = messageFromAxiosError(e, t, te);
            }
        });
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
        consumeGoogleTasksForbidden(e);
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
        consumeGoogleTasksForbidden(e);
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
    if (!tasksDataAvailable.value) {
        return;
    }
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
    await withTasksLoad(async () => {
        loadError.value = '';
        try {
            await withReadRetry(async () => {
                await fetchTasksForList();
            });
            await nextTick();
            for (const el of document.querySelectorAll('[data-task-id]')) {
                if (el.getAttribute('data-task-id') === row.task.id) {
                    el.scrollIntoView({
                        block: 'nearest',
                        behavior: 'smooth',
                    });
                    break;
                }
            }
        } catch (e) {
            if (consumeGoogleTasksForbidden(e)) {
                loadError.value = messageFromAxiosError(e, t, te);
                return;
            }
            loadError.value = messageFromAxiosError(e, t, te);
        }
    });
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
    editListId.value = '';
    editSaving.value = false;
}

function closeInspector() {
    inspectorMode.value = null;
}

function openInspectorNew() {
    inspectorMode.value = 'new';
    inspectorEditTaskKey.value = '';
    syncNewTaskListFromNav();
}

function openEditInspector(task, e, opts = {}) {
    if (e) {
        e.stopPropagation();
    }
    if (task._optimistic) {
        return;
    }
    const k = taskKey(task);
    if (inspectorEditTaskKey.value === k && !opts.forceOpen) {
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
    editListId.value = listIdForTask(task) ?? '';
}

async function saveEditedTask() {
    const key = inspectorEditTaskKey.value;
    const task = tasks.value.find((t) => taskKey(t) === key);
    if (!task || task._optimistic) {
        return;
    }
    editSaving.value = true;
    const sourceListId = listIdForTask(task);
    const destListId =
        editListId.value && taskLists.value.some((l) => l.id === editListId.value)
            ? editListId.value
            : sourceListId;
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
        let taskId = task.id;
        if (destListId !== sourceListId) {
            const { data: moved } = await axios.post(
                route('tasks.data.tasks.move', {
                    taskList: sourceListId,
                    task: taskId,
                }),
                { destinationTasklist: destListId },
            );
            if (moved?.id) {
                taskId = moved.id;
            }
        }
        const { data } = await axios.patch(
            route('tasks.data.tasks.update', {
                taskList: destListId,
                task: taskId,
            }),
            body,
        );
        const merged = { ...data };
        merged._taskListId = destListId;
        const meta = taskLists.value.find((l) => l.id === destListId);
        if (meta?.title) {
            merged._taskListTitle = meta.title;
        }
        tasks.value = tasks.value.map((current) =>
            taskKey(current) === key ? merged : current,
        );
        closeDetailEdit();
        syncCacheAfterMutation(destListId);
        if (destListId !== sourceListId) taskCache.invalidate('list', sourceListId);
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
    if (showCommandPalette.value) {
        e.preventDefault();
        showCommandPalette.value = false;
        return;
    }
    if (showSwipeMoreModal.value) {
        e.preventDefault();
        closeSwipeMoreSheet();
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
    const listId = newTaskListId.value || effectiveListId.value;
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
        syncCacheAfterMutation(listId);
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
        syncCacheAfterMutation(listId);
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
        syncCacheAfterMutation(listId);
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
                        syncCacheAfterMutation(listId);
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

function syncInspectorDueIfOpen(editKey, dueIso) {
    if (inspectorEditTaskKey.value === editKey) {
        editDue.value = toDatetimeLocalValue(dueIso);
    }
}

function onDeferPresetFromPanel(preset) {
    const key = inspectorEditTaskKey.value;
    const task = tasks.value.find((t) => taskKey(t) === key);
    if (!task) {
        return;
    }
    void applyDeferPreset(task, preset);
}

async function applyDeferPreset(task, preset) {
    if (task._optimistic) {
        return;
    }
    if (preset === 'pickDate') {
        openEditInspector(task, undefined, { forceOpen: true });
        nextTick(() => {
            document.getElementById('detail-edit-due')?.focus?.();
        });
        return;
    }
    const listId = listIdForTask(task);
    const editKey = taskKey(task);
    const prev = { ...task };
    let dueIso;
    try {
        dueIso = computeDeferDueIso(preset, new Date(), task.due);
    } catch {
        return;
    }
    tasks.value = tasks.value.map((t) =>
        t.id === task.id ? { ...t, due: dueIso } : t,
    );
    syncInspectorDueIfOpen(editKey, dueIso);
    try {
        const { data } = await axios.patch(
            route('tasks.data.tasks.update', {
                taskList: listId,
                task: task.id,
            }),
            { due: dueIso },
        );
        const merged = { ...data };
        if (task._taskListId) {
            merged._taskListId = task._taskListId;
            merged._taskListTitle = task._taskListTitle;
        }
        tasks.value = tasks.value.map((t) =>
            t.id === task.id ? merged : t,
        );
        syncInspectorDueIfOpen(editKey, merged.due);
        syncCacheAfterMutation(listId);
        if (prev.due) {
            const prevDue = prev.due;
            void showUndoToast({
                message: t('tasks.undo.dueUpdated'),
                onUndo: async () => {
                    try {
                        const { data: d2 } = await axios.patch(
                            route('tasks.data.tasks.update', {
                                taskList: listId,
                                task: merged.id,
                            }),
                            { due: prevDue },
                        );
                        const restored = { ...d2 };
                        if (merged._taskListId) {
                            restored._taskListId = merged._taskListId;
                            restored._taskListTitle = merged._taskListTitle;
                        }
                        tasks.value = tasks.value.map((x) =>
                            x.id === merged.id ? restored : x,
                        );
                        syncInspectorDueIfOpen(editKey, restored.due);
                        syncCacheAfterMutation(listId);
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
        syncInspectorDueIfOpen(editKey, prev.due);
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
            syncCacheAfterMutation(listId);
        },
        onCommit: async () => {
            try {
                await axios.delete(
                    route('tasks.data.tasks.destroy', {
                        taskList: listId,
                        task: saved.id,
                    }),
                );
                syncCacheAfterMutation(listId);
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
        ? 'bg-gt-accent-tint/60 text-gt-accent ring-1 ring-inset ring-gt-accent/25 dark:bg-gt-accent-tint/20 dark:text-gt-accent-hover dark:ring-gt-accent/30 active:opacity-90'
        : 'text-gt-ink-secondary hover:bg-gt-field-muted active:bg-gt-field-muted';
}

function viewModeToggleClass(active) {
    return active
        ? 'bg-gt-raised text-gt-ink shadow dark:bg-gt-field inline-flex items-center justify-center'
        : 'text-gt-muted hover:text-gt-ink inline-flex items-center justify-center active:bg-gt-field-muted/60';
}

function loadPersistedTaskUi() {
    const params = new URLSearchParams(window.location.search);
    const urlFilter = params.get('filter');
    const urlDate = params.get('date');
    const urlWeekday = params.get('weekday');
    const urlStatus = params.get('status');

    if (urlDate) filterDate.value = urlDate;
    if (urlWeekday) filterWeekday.value = urlWeekday;
    if (urlStatus === 'completed' || urlStatus === 'needsAction') {
        filterCompletion.value = urlStatus;
    }

    if (typeof localStorage === 'undefined') {
        if (urlFilter === 'no-due') {
            filterDue.value = 'noDue';
        }
        return;
    }
    try {
        const vm = localStorage.getItem(VIEW_MODE_KEY);
        if (vm === 'list' || vm === 'board') {
            viewMode.value = vm;
        }
        const bgm = localStorage.getItem(BOARD_GROUP_KEY);
        if (bgm === 'priority' || bgm === 'due') {
            boardGroupMode.value = bgm;
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

        // URL param overrides local storage
        if (urlFilter === 'no-due') {
            filterDue.value = 'noDue';
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
    localStorage.setItem(BOARD_GROUP_KEY, boardGroupMode.value);
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

/** US-032: drop-to-reschedule on due-date board */
async function onKanbanDropDue({ taskId, listId, newLane }) {
    const task = tasks.value.find(
        (t) => t.id === taskId && listIdForTask(t) === listId,
    );
    if (!task || task._optimistic) {
        return;
    }
    const dueIso = dueLaneDateMapping(newLane);
    const editKey = taskKey(task);
    const prev = { ...task };
    tasks.value = tasks.value.map((t) =>
        t.id === task.id ? { ...t, due: dueIso } : t,
    );
    syncInspectorDueIfOpen(editKey, dueIso);
    try {
        const { data } = await axios.patch(
            route('tasks.data.tasks.update', {
                taskList: listId,
                task: task.id,
            }),
            { due: dueIso },
        );
        const merged = { ...data };
        if (task._taskListId) {
            merged._taskListId = task._taskListId;
            merged._taskListTitle = task._taskListTitle;
        }
        tasks.value = tasks.value.map((t) =>
            t.id === task.id ? merged : t,
        );
        syncInspectorDueIfOpen(editKey, merged.due);
        syncCacheAfterMutation(listId);
        const prevDue = prev.due;
        void showUndoToast({
            message: t('tasks.undo.dueUpdated'),
            onUndo: async () => {
                try {
                    const { data: d2 } = await axios.patch(
                        route('tasks.data.tasks.update', {
                            taskList: listId,
                            task: merged.id,
                        }),
                        { due: prevDue ?? null },
                    );
                    const restored = { ...d2 };
                    if (merged._taskListId) {
                        restored._taskListId = merged._taskListId;
                        restored._taskListTitle = merged._taskListTitle;
                    }
                    tasks.value = tasks.value.map((x) =>
                        x.id === merged.id ? restored : x,
                    );
                    syncInspectorDueIfOpen(editKey, restored.due);
                    syncCacheAfterMutation(listId);
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
    } catch (e) {
        tasks.value = tasks.value.map((t) =>
            t.id === task.id ? prev : t,
        );
        syncInspectorDueIfOpen(editKey, prev.due);
        loadError.value = messageFromAxiosError(
            e,
            t,
            te,
            'tasks.errors.updateFailed',
        );
    }
}

function onKanbanTaskClick(task, e) {
    const idx = sortedFilteredTasks.value.findIndex(
        (x) => taskKey(x) === taskKey(task),
    );
    if (idx >= 0) {
        onTaskRowClick(task, idx, e);
    }
}

function onKanbanSelectionClick(task) {
    const idx = sortedFilteredTasks.value.findIndex(
        (x) => taskKey(x) === taskKey(task),
    );
    if (idx >= 0) {
        onSelectionCheckboxClick(task, idx);
    }
}

useTasksKeyboardShortcuts({
    connected: tasksDataAvailable,
    showHelp: showKeyboardHelp,
    showCommandPalette,
    tasks: tasksForShortcuts,
    focusedTaskIndex,
    onOpenHelp: () => {
        showKeyboardHelp.value = true;
    },
    onOpenCommandPalette: () => {
        if (!tasksDataAvailable.value) {
            return;
        }
        showCommandPalette.value = true;
    },
    onFocusSearch: () => {
        openMobileSearchPanel();
        void nextTick(() => {
            searchInputRef.value?.focus();
            searchInputRef.value?.select?.();
        });
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
    onSyncFromGoogle: () => {
        void userInitiatedSyncFromGoogle();
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
    () => sortedFilteredTasks.value.length,
    () => {
        if (focusedTaskIndex.value >= sortedFilteredTasks.value.length) {
            focusedTaskIndex.value =
                sortedFilteredTasks.value.length > 0
                    ? sortedFilteredTasks.value.length - 1
                    : -1;
        }
    },
);

watch(viewMode, () => {
    closeDetailEdit();
});

watch(filterCompletion, async () => {
    if (suppressCompletionSyncFetch.value) {
        return;
    }
    if (!tasksDataAvailable.value) {
        return;
    }
    await withTasksLoad(async () => {
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
            if (consumeGoogleTasksForbidden(e)) {
                loadError.value = messageFromAxiosError(e, t, te);
                return;
            }
            if (e.response?.status === 429) {
                pollBackoffMs.value = Math.min(
                    props.maxBackoffMs,
                    Math.max(props.pollIntervalMs, pollBackoffMs.value * 2),
                );
            }
            loadError.value = messageFromAxiosError(e, t, te);
        }
    });
});

watch(
    [
        viewMode,
        boardGroupMode,
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

function syncFiltersDetailsOpen() {
    const el = filtersDetailsRef.value;
    if (!el) {
        return;
    }
    el.open = window.matchMedia('(min-width: 1024px)').matches;
}

onMounted(async () => {
    try {
        if (typeof localStorage !== 'undefined') {
            globalSortMode.value = normalizeGlobalSortMode(
                localStorage.getItem(GLOBAL_TASK_SORT_STORAGE_KEY),
            );
        }
    } catch {
        /* ignore */
    }
    registerTasksCommandPaletteOpener(() => {
        if (tasksDataAvailable.value) {
            showCommandPalette.value = true;
        }
    });
    suppressCompletionSyncFetch.value = true;
    loadPersistedTaskUi();
    if (searchMode.value === 'semantic' && !props.semanticSearchAvailable) {
        searchMode.value = 'keyword';
    }
    await nextTick();
    suppressCompletionSyncFetch.value = false;
    syncFiltersDetailsOpen();
    const mq = window.matchMedia('(min-width: 1024px)');
    mq.addEventListener('change', syncFiltersDetailsOpen);
    removeFiltersMqListener = () =>
        mq.removeEventListener('change', syncFiltersDetailsOpen);
    updateSwipeRowsEnabled();
    const swipeMq = window.matchMedia(
        '(max-width: 639px) and (hover: none) and (pointer: coarse)',
    );
    swipeMq.addEventListener('change', updateSwipeRowsEnabled);
    removeSwipeMqListener = () =>
        swipeMq.removeEventListener('change', updateSwipeRowsEnabled);
    if (!props.connected) {
        return;
    }
    /* Initial data load (no recurring poll — cache + pull-to-refresh handles freshness) */
    void pollOnce();
});

watch(
    () => props.semanticSearchAvailable,
    (ok) => {
        if (!ok && searchMode.value === 'semantic') {
            searchMode.value = 'keyword';
        }
    },
);

watch(
    () => props.connected,
    async (ok, wasOk) => {
        if (!ok) {
            googleTasksForbidden.value = false;
            taskCache.flush(); /* US-037: clear cache on disconnect */
        } else if (wasOk === false) {
            googleTasksForbidden.value = false;
        }
        if (ok) {
            await nextTick();
            syncFiltersDetailsOpen();
        }
    },
);

onUnmounted(() => {
    unregisterTasksCommandPaletteOpener();
    removeFiltersMqListener?.();
    removeSwipeMqListener?.();
    window.removeEventListener('keydown', escCloseInspector);
});
</script>

<template>
    <Head :title="t('tasks.headTitle')" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex w-full flex-col gap-2 sm:gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4"
            >
                <div
                    class="flex w-full items-center justify-between gap-2 sm:w-auto sm:justify-start"
                >
                    <div
                        class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-1 sm:flex-initial sm:gap-x-3"
                    >
                        <h2
                            class="truncate font-display text-lg font-bold leading-tight tracking-tight text-gt-ink sm:text-2xl lg:text-3xl"
                        >
                            {{ pageTitle }}
                        </h2>
                        <button
                            v-if="tasksDataAvailable"
                            type="button"
                            class="hidden min-h-10 shrink-0 items-center justify-center rounded-md border border-gt-border-strong bg-gt-field p-2 text-gt-ink shadow-sm touch-manipulation hover:bg-gt-field-muted focus:outline-none focus:ring-2 focus:ring-gt-accent-ring disabled:cursor-not-allowed disabled:opacity-60 lg:inline-flex"
                            :aria-busy="syncFromGoogleLoading"
                            :aria-label="t('tasks.syncFromGoogle')"
                            :disabled="syncFromGoogleLoading"
                            @click="userInitiatedSyncFromGoogle"
                        >
                            <svg
                                class="h-5 w-5 text-gt-accent"
                                :class="{
                                    'motion-safe:animate-spin': syncFromGoogleLoading,
                                }"
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
                                    d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"
                                />
                            </svg>
                        </button>
                        <button
                            v-if="tasksDataAvailable"
                            type="button"
                            class="inline-flex min-h-10 shrink-0 items-center rounded-md px-2 text-xs font-medium text-gt-accent underline decoration-gt-accent/40 underline-offset-2 touch-manipulation hover:text-gt-accent-hover active:bg-gt-accent-tint/20 sm:min-h-11 sm:px-0"
                            @click="showKeyboardHelp = true"
                        >
                            <span class="hidden sm:inline">{{
                                t('shortcuts.hint')
                            }}</span>
                            <span
                                class="font-semibold sm:hidden"
                                aria-hidden="true"
                                >?</span
                            >
                            <span class="sr-only sm:hidden">{{
                                t('shortcuts.hint')
                            }}</span>
                        </button>
                    </div>
                    <div
                        v-if="tasksDataAvailable"
                        class="flex shrink-0 items-center sm:hidden"
                    >
                        <button
                            v-if="!showMobileSearch"
                            type="button"
                            class="inline-flex min-h-10 items-center justify-center rounded-md border border-gt-border-strong bg-gt-field px-3 text-sm font-medium text-gt-ink shadow-sm touch-manipulation hover:bg-gt-field-muted active:bg-gt-field-muted"
                            @click="openMobileSearchPanel"
                        >
                            {{ t('tasks.searchOpen') }}
                        </button>
                        <button
                            v-else
                            type="button"
                            class="inline-flex min-h-10 items-center justify-center rounded-md px-3 text-sm font-medium text-gt-muted touch-manipulation hover:text-gt-ink active:bg-gt-field-muted"
                            @click="showMobileSearch = false"
                        >
                            {{ t('tasks.searchClose') }}
                        </button>
                    </div>
                </div>
                <div
                    v-if="tasksDataAvailable"
                    class="flex w-full min-w-0 flex-col gap-2 sm:max-w-md"
                >
                    <div
                        class="hidden items-center justify-end gap-2 sm:flex lg:hidden"
                    >
                        <button
                            v-if="!showMobileSearch"
                            type="button"
                            class="inline-flex min-h-11 items-center justify-center rounded-md border border-gt-border-strong bg-gt-field px-4 text-sm font-medium text-gt-ink shadow-sm touch-manipulation hover:bg-gt-field-muted active:bg-gt-field-muted"
                            @click="openMobileSearchPanel"
                        >
                            {{ t('tasks.searchOpen') }}
                        </button>
                        <button
                            v-else
                            type="button"
                            class="inline-flex min-h-11 items-center justify-center rounded-md px-4 text-sm font-medium text-gt-muted touch-manipulation hover:text-gt-ink active:bg-gt-field-muted"
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
                            class="min-h-11 rounded-md border border-gt-border-strong bg-gt-field text-sm text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring sm:min-h-0 sm:text-xs"
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
                        class="block w-full rounded-md border border-gt-border-strong bg-gt-field text-base text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring sm:text-sm"
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
                                class="flex min-h-11 w-full flex-col items-start gap-1 px-3 py-3 text-left hover:bg-gt-field-muted active:bg-gt-field-muted"
                                @click="openSearchResult(row)"
                            >
                                <div
                                    class="flex flex-wrap items-center gap-x-2 gap-y-0.5"
                                >
                                    <TaskPriorityDueMeta
                                        :task="row.task"
                                        variant="search"
                                    />
                                </div>
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
                    v-if="!tasksDataAvailable"
                    class="overflow-hidden gt-surface sm:mx-6 sm:rounded-lg lg:mx-0"
                >
                    <div class="density-stack density-card-padding max-w-2xl">
                        <h3
                            class="text-lg font-semibold text-gt-ink"
                        >
                            {{ t('tasks.connectHeadline') }}
                        </h3>
                        <p
                            v-if="googleTasksForbidden && connected"
                            class="mt-1 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100"
                        >
                            {{ t('tasks.staleGoogleConnectionHint') }}
                        </p>
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
                            <button
                                v-if="googleTasksForbidden && connected"
                                type="button"
                                class="inline-flex items-center rounded-md border border-gt-border-strong bg-gt-field px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gt-ink shadow-sm transition hover:bg-gt-field-muted focus:outline-none focus:ring-2 focus:ring-gt-accent-ring"
                                @click="reloadTasksPage"
                            >
                                {{ t('tasks.reloadPage') }}
                            </button>
                            <Link
                                :href="route('google.redirect')"
                                class="inline-flex items-center rounded-md border border-transparent bg-gt-accent-strong px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gt-accent-strong-hover focus:outline-none focus:ring-2 focus:ring-gt-accent-ring focus:ring-offset-2 focus:ring-offset-gt-raised active:opacity-90"
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
                            class="flex min-h-11 w-full touch-manipulation items-center rounded-md px-3 text-left text-sm font-medium"
                            :class="navButtonClass(navMode === 'today')"
                            @click="setNav('today')"
                        >
                            {{ t('tasks.navToday') }}
                        </button>
                        <button
                            type="button"
                            class="flex min-h-11 w-full touch-manipulation items-center rounded-md px-3 text-left text-sm font-medium"
                            :class="navButtonClass(navMode === 'inbox')"
                            @click="setNav('inbox')"
                        >
                            {{ t('tasks.navInbox') }}
                        </button>
                        <button
                            type="button"
                            class="flex min-h-11 w-full touch-manipulation items-center rounded-md px-3 text-left text-sm font-medium"
                            :class="navButtonClass(navMode === 'all')"
                            @click="setNav('all')"
                        >
                            {{ t('tasks.navAll') }}
                        </button>
                        <!-- Pinned lists -->
                        <template v-if="pinnedLists.length > 0">
                            <div
                                class="mt-4 border-t border-gt-border pt-3 text-xs font-semibold uppercase tracking-wide text-gt-subtle"
                            >
                                {{ t('tasks.pinnedLists') }}
                            </div>
                            <button
                                v-for="list in pinnedLists"
                                :key="`pin-${list.id}`"
                                type="button"
                                class="flex min-h-11 w-full touch-manipulation items-center gap-1.5 truncate rounded-md px-3 text-left text-sm font-medium"
                                :class="
                                    navButtonClass(
                                        navMode === 'list' &&
                                            selectedListId === list.id,
                                    )
                                "
                                :title="list.title"
                                @click="selectList(list)"
                                @contextmenu="showListContextMenu($event, list)"
                            >
                                <svg class="h-3.5 w-3.5 shrink-0 text-gt-accent opacity-60" viewBox="0 0 16 16" fill="currentColor"><path d="M9.828.722a.5.5 0 0 1 .354.146l4.95 4.95a.5.5 0 0 1-.707.708l-.812-.813-3.04 3.04a4 4 0 0 1-.79 5.088l-.353.353a.5.5 0 0 1-.707 0L6.17 11.64l-3.96 3.96a.5.5 0 1 1-.708-.707l3.96-3.96-2.554-2.554a.5.5 0 0 1 0-.707l.354-.354a4 4 0 0 1 5.087-.79l3.04-3.04-.812-.812a.5.5 0 0 1 .353-.854z"/></svg>
                                <span class="truncate">{{ list.title }}</span>
                            </button>
                        </template>

                        <!-- Unpinned lists -->
                        <div
                            class="mt-4 border-t border-gt-border pt-3 text-xs font-semibold uppercase tracking-wide text-gt-subtle"
                        >
                            {{ pinnedLists.length > 0 ? t('tasks.otherLists') : t('tasks.listsHeading') }}
                        </div>
                        <button
                            v-for="list in unpinnedLists"
                            :key="list.id"
                            type="button"
                            class="flex min-h-11 w-full touch-manipulation items-center truncate rounded-md px-3 text-left text-sm font-medium"
                            :class="
                                navButtonClass(
                                    navMode === 'list' &&
                                        selectedListId === list.id,
                                )
                            "
                            :title="list.title"
                            @click="selectList(list)"
                            @contextmenu="showListContextMenu($event, list)"
                        >
                            {{ list.title }}
                        </button>

                        <!-- Organise lists button -->
                        <button
                            type="button"
                            class="mt-2 w-full text-left text-xs text-gt-accent underline decoration-gt-accent/40 underline-offset-2 hover:text-gt-accent-hover"
                            @click="showOrganiseSheet = true"
                        >
                            {{ t('tasks.organiseLists') }}
                        </button>
                    </aside>

                    <!-- Right-click context menu for list pin/unpin -->
                    <Teleport to="body">
                        <div
                            v-if="listContextMenu.visible"
                            class="fixed z-[100] rounded-md border border-gt-border bg-gt-raised py-1 shadow-lg"
                            :style="{ left: listContextMenu.x + 'px', top: listContextMenu.y + 'px' }"
                            @click.stop
                        >
                            <button
                                type="button"
                                class="w-full px-4 py-2 text-left text-sm text-gt-ink hover:bg-gt-field-muted"
                                @click="onListContextMenuPin"
                            >
                                {{ listContextMenu.list?.pinned ? t('tasks.unpin') : t('tasks.pinToTop') }}
                            </button>
                        </div>
                        <div
                            v-if="listContextMenu.visible"
                            class="fixed inset-0 z-[99]"
                            @click="hideListContextMenu"
                        />
                    </Teleport>

                    <div class="flex min-w-0 flex-1 flex-col">
                        <div
                            class="density-stack max-lg:space-y-3 min-w-0 flex-1 px-4 sm:px-6 lg:px-0"
                        >
                        <button
                            type="button"
                            class="w-full touch-manipulation py-1 text-left text-sm font-medium text-gt-accent underline decoration-gt-accent/40 underline-offset-2 hover:text-gt-accent-hover active:opacity-80 lg:hidden"
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
                            v-if="viewCachedAt && (navMode === 'today' || navMode === 'inbox' || navMode === 'all')"
                            class="flex flex-col gap-2 rounded-lg border border-yellow-300 bg-yellow-50 p-2 text-xs text-yellow-800 dark:border-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300 sm:flex-row sm:items-center sm:justify-between sm:gap-3"
                        >
                            <p class="min-w-0 flex-1 leading-relaxed">
                                {{ t('dashboard.staleDisclaimer', { date: new Date(viewCachedAt).toLocaleString(locale === 'fr' ? 'fr-CA' : 'en-CA', { timeZone: 'America/Toronto' }) }) }}
                            </p>
                            <button
                                type="button"
                                class="shrink-0 self-start rounded-md px-2 py-1 text-xs font-semibold text-yellow-900 underline decoration-yellow-700/50 underline-offset-2 hover:bg-yellow-100/80 dark:text-yellow-200 dark:decoration-yellow-400/50 dark:hover:bg-yellow-900/30 sm:self-center"
                                :disabled="syncFromGoogleLoading"
                                @click="userInitiatedSyncFromGoogle"
                            >
                                {{
                                    syncFromGoogleLoading
                                        ? t('tasks.syncRefreshing')
                                        : t('tasks.syncRefreshLink')
                                }}
                            </button>
                        </div>

                        <div
                            class="flex w-full min-w-0 flex-col overflow-hidden gt-surface sm:rounded-lg"
                        >
                            <!-- Mobile: compact add-task bar; Desktop: full composer -->
                            <div
                                class="border-b border-gt-border"
                            >
                                <!-- Mobile compact prompt (hidden on sm+) -->
                                <button
                                    v-if="!mobileComposerOpen"
                                    type="button"
                                    class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-gt-muted touch-manipulation active:bg-gt-field-muted sm:hidden"
                                    @click="mobileComposerOpen = true"
                                >
                                    <svg class="h-4 w-4 shrink-0 text-gt-accent" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    {{ t('tasks.addTaskPrompt') }}
                                </button>
                                <!-- Full composer (always on sm+, toggled on mobile) -->
                                <div
                                    :class="[
                                        'px-4 py-2 sm:px-6 sm:py-3',
                                        mobileComposerOpen ? '' : 'hidden sm:block',
                                    ]"
                                >
                                    <div
                                        v-if="taskLists.length > 0"
                                        class="mb-2"
                                    >
                                        <InputLabel
                                            for="composer-list"
                                            :value="t('tasks.addToList')"
                                        />
                                        <select
                                            id="composer-list"
                                            v-model="newTaskListId"
                                            class="mt-1 block min-h-11 w-full rounded-md border border-gt-border-strong bg-gt-field text-base text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring sm:text-sm"
                                            @change="onComposerListChange"
                                        >
                                            <option
                                                v-for="list in taskLists"
                                                :key="`cl-${list.id}`"
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
                                            class="block min-h-11 min-w-0 w-full flex-1 text-base sm:text-sm"
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
                            </div>
                            <div
                                v-if="inspectorMode === 'new'"
                                class="w-full min-w-0 shrink-0 border-b border-gt-border bg-gt-field-muted/30 px-4 py-4 sm:px-6 dark:bg-gt-field/20"
                                role="region"
                                :aria-label="t('tasks.inspectorNewTitle')"
                            >
                                <div
                                    class="mb-3 flex items-center justify-between gap-2"
                                >
                                    <h3
                                        class="text-sm font-semibold text-gt-ink sm:text-base"
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
                                            class="mt-1 block min-h-11 w-full text-base sm:text-sm"
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
                                            class="mt-1 block min-h-11 w-full text-base sm:text-sm"
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
                                            class="mt-1 block min-h-11 w-full rounded-md border border-gt-border-strong bg-gt-field text-base text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring sm:text-sm"
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
                                            class="mt-1 block w-full rounded-md border border-gt-border-strong bg-gt-field text-base text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring sm:text-sm"
                                            :placeholder="
                                                t('tasks.notesPlaceholder')
                                            "
                                            @paste="onInspectorNotesPaste"
                                        />
                                    </div>
                                </div>
                                <div
                                    class="mt-4 flex flex-wrap gap-2"
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
                            </div>
                            <div
                                class="border-b border-gt-border bg-gt-field-muted/40 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-gt-muted sm:bg-transparent sm:px-6 sm:py-2 sm:text-sm sm:font-normal sm:normal-case sm:tracking-normal dark:bg-gt-field/20 sm:dark:bg-transparent"
                                :class="
                                    navMode === 'list' ? 'max-lg:hidden' : ''
                                "
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
                            <details
                                ref="filtersDetailsRef"
                                class="group border-b border-gt-border"
                            >
                                <summary
                                    class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-2 text-sm font-medium text-gt-ink marker:content-none sm:px-6 lg:hidden [&::-webkit-details-marker]:hidden"
                                >
                                    <span>{{ t('tasks.filtersToggle') }}</span>
                                    <svg
                                        class="h-4 w-4 shrink-0 text-gt-muted transition group-open:rotate-180 motion-reduce:transition-none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </summary>
                                <div
                                    class="flex flex-wrap items-end gap-3 border-t border-gt-border px-4 pb-3 pt-2 max-sm:flex-col max-sm:items-stretch sm:px-6 lg:border-t-0 lg:px-6 lg:py-3"
                                >
                                <div
                                    class="inline-flex w-full gap-0.5 rounded-lg bg-gt-field-muted p-0.5 sm:w-auto"
                                    role="group"
                                    :aria-label="t('tasks.viewModeGroup')"
                                >
                                    <button
                                        type="button"
                                        class="min-h-11 flex-1 touch-manipulation rounded-md px-3 text-xs font-medium transition sm:flex-none sm:px-3 sm:py-2"
                                        :class="viewModeToggleClass(viewMode === 'list')"
                                        @click="viewMode = 'list'"
                                    >
                                        {{ t('tasks.viewList') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="min-h-11 flex-1 touch-manipulation rounded-md px-3 text-xs font-medium transition sm:flex-none sm:px-3 sm:py-2"
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
                                        class="mt-1 block min-h-11 w-full rounded-md border border-gt-border-strong bg-gt-field text-base text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring sm:text-sm"
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
                                        class="mt-1 block min-h-11 w-full rounded-md border border-gt-border-strong bg-gt-field text-base text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring sm:text-sm"
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
                                        class="mt-1 block min-h-11 w-full rounded-md border border-gt-border-strong bg-gt-field text-base text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring sm:text-sm"
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
                                        class="mt-1 block min-h-11 w-full rounded-md border border-gt-border-strong bg-gt-field text-base text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring sm:text-sm"
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
                                    class="mt-6 max-sm:mt-0 max-sm:w-full max-sm:justify-center"
                                    @click="resetFilters"
                                >
                                    {{ t('tasks.resetFilters') }}
                                </SecondaryButton>
                            </div>
                            </details>
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
                            <div
                                ref="taskAreaRef"
                                class="relative min-h-[8rem]"
                                :aria-busy="tasksLoading ? 'true' : 'false'"
                            >
                            <!-- US-044: pull-to-refresh indicator -->
                            <Transition name="gt-tasks-loading">
                                <div
                                    v-if="pullRefreshActive || pullRefreshBusy"
                                    class="pointer-events-none absolute inset-x-0 top-0 z-30 flex items-center justify-center"
                                    :style="{ height: '48px', opacity: pullProgress }"
                                >
                                    <svg
                                        class="h-6 w-6 text-gt-accent"
                                        :class="{ 'animate-spin': pullRefreshBusy }"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                </div>
                            </Transition>
                            <div :style="pullIndicatorStyle">
                            <Transition name="gt-tasks-loading">
                            <div
                                v-if="tasksLoading"
                                class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 bg-gt-raised/90 dark:bg-gt-raised/92"
                                role="status"
                                aria-live="polite"
                            >
                                <span class="sr-only">{{
                                    tasksLoadingLabel
                                }}</span>
                                <svg
                                    class="h-9 w-9 motion-reduce:animate-none animate-spin text-gt-accent"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    />
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    />
                                </svg>
                                <span
                                    class="text-sm font-medium text-gt-muted"
                                    aria-hidden="true"
                                    >{{ tasksLoadingLabel }}</span
                                >
                            </div>
                            </Transition>
                            <div
                                v-if="
                                    (viewMode === 'list' ||
                                        viewMode === 'board') &&
                                    taskLists.length > 0
                                "
                                class="flex flex-col gap-2 border-b border-gt-border bg-gt-field-muted/25 px-4 py-1.5 sm:flex-row sm:items-start sm:justify-between sm:px-6 sm:py-3 dark:bg-gt-field/15"
                            >
                                <div
                                    class="min-w-0 flex max-w-xl flex-1 flex-col gap-1"
                                >
                                    <div
                                        class="hidden flex-col gap-1 sm:flex"
                                    >
                                        <label
                                            for="global-task-sort"
                                            class="text-xs font-medium text-gt-ink-secondary"
                                        >
                                            {{ t('tasks.sort.label') }}
                                        </label>
                                        <select
                                            id="global-task-sort"
                                            v-model="globalSortMode"
                                            class="block w-full rounded-md border border-gt-border-strong bg-gt-field text-sm text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring"
                                            aria-describedby="global-sort-hint"
                                        >
                                            <option value="due_first">
                                                {{ t('tasks.sort.dueFirst') }}
                                            </option>
                                            <option value="priority_first">
                                                {{
                                                    t('tasks.sort.priorityFirst')
                                                }}
                                            </option>
                                        </select>
                                        <p
                                            id="global-sort-hint"
                                            class="text-xs leading-relaxed text-gt-muted"
                                        >
                                            {{
                                                viewMode === 'board'
                                                    ? t('tasks.sort.hintKanban')
                                                    : t('tasks.sort.hintLists')
                                            }}
                                        </p>
                                    </div>
                                    <div class="flex flex-col gap-1 sm:hidden">
                                        <div
                                            class="flex items-center justify-between gap-2"
                                        >
                                            <p class="min-w-0 flex-1 truncate text-xs text-gt-muted">
                                                <span class="font-medium text-gt-ink-secondary">{{ t('tasks.sort.label') }}:</span>
                                                {{ globalSortModeCurrentLabel }}
                                            </p>
                                            <button
                                                type="button"
                                                class="inline-flex min-h-9 min-w-9 shrink-0 items-center justify-center rounded-md border border-gt-border-strong bg-gt-field text-gt-ink shadow-sm touch-manipulation focus:border-gt-accent focus:outline-none focus:ring-gt-accent-ring"
                                                :aria-label="t('tasks.sort.openSheet')"
                                                :aria-describedby="
                                                    'global-sort-hint-mobile'
                                                "
                                                @click="showGlobalSortSheet = true"
                                            >
                                                <svg
                                                    class="h-4 w-4"
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
                                                        d="M8.25 14.25l3.75 3.75 3.75-3.75M15.75 9.75l-3.75-3.75-3.75 3.75"
                                                    />
                                                </svg>
                                            </button>
                                            <button
                                                v-if="props.semanticSearchAvailable"
                                                type="button"
                                                class="inline-flex min-h-9 min-w-9 shrink-0 items-center justify-center rounded-md border border-gt-border-strong bg-gt-field text-gt-ink shadow-sm touch-manipulation focus:border-gt-accent focus:outline-none focus:ring-gt-accent-ring"
                                                :aria-label="t('tasks.duplicates.menuLabel')"
                                                @click="openDuplicatesModal"
                                            >
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
                                                        d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m0 0a2.625 2.625 0 115.25 0"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                        <p
                                            id="global-sort-hint-mobile"
                                            class="sr-only"
                                        >
                                            {{
                                                viewMode === 'board'
                                                    ? t('tasks.sort.hintKanban')
                                                    : t('tasks.sort.hintLists')
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <p
                                v-if="viewMode === 'list'"
                                class="hidden border-b border-gt-border px-4 py-2 text-xs leading-relaxed text-gt-muted sm:block sm:px-6"
                            >
                                <span
                                    class="me-2 font-medium text-gt-ink-secondary"
                                    >{{ t('tasks.checkboxRowHintShort') }}:</span
                                >
                                {{ t('tasks.checkboxRowHint') }}
                            </p>
                            <p
                                v-if="viewMode === 'list' && swipeRowsEnabled"
                                class="border-b border-gt-border px-4 py-2 text-xs leading-relaxed text-gt-muted sm:hidden"
                            >
                                {{ t('tasks.swipe.rowHint') }}
                            </p>
                            <ul
                                v-if="viewMode === 'list'"
                                class="divide-y divide-gt-border"
                                role="list"
                                aria-label="Tasks"
                            >
                                <li
                                    v-for="(task, taskIndex) in sortedFilteredTasks"
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
                                    <TaskListRowSwipe
                                        :enabled="swipeRowsEnabled"
                                        :disabled="!!task._optimistic"
                                        :is-completed="
                                            task.status === 'completed'
                                        "
                                        :reset-signal="swipeSheetReset"
                                        @complete="
                                            onSwipeRowComplete(task)
                                        "
                                        @more="openSwipeMoreSheet(task)"
                                    >
                                    <div
                                        :data-task-id="task.id"
                                        :class="[
                                            'density-task-row flex cursor-pointer items-start gap-3 px-4 py-3 outline-none transition-shadow sm:px-6',
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
                                        v-show="!swipeRowsEnabled"
                                        type="checkbox"
                                        class="mt-0.5 h-[1.125rem] w-[1.125rem] rounded border-gt-border-strong text-gt-accent focus:ring-gt-accent-ring dark:bg-gt-field sm:mt-1 sm:h-4 sm:w-4"
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
                                        class="mt-0.5 h-[1.125rem] w-[1.125rem] rounded border-gt-border-strong text-gt-accent focus:ring-gt-accent-ring dark:bg-gt-field sm:mt-1 sm:h-4 sm:w-4"
                                        :checked="task.status === 'completed'"
                                        :disabled="task._optimistic"
                                        @click.stop
                                        @change="toggleComplete(task)"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p
                                            :class="[
                                                'text-[0.9375rem] font-medium leading-snug text-gt-ink sm:text-sm',
                                                task.status === 'completed'
                                                    ? 'line-through text-gt-subtle'
                                                    : '',
                                            ]"
                                        >
                                            {{ task.title }}
                                        </p>
                                        <div
                                            class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5"
                                        >
                                            <TaskPriorityDueMeta
                                                :task="task"
                                                variant="list"
                                            />
                                            <span
                                                v-if="
                                                    (navMode === 'today' ||
                                                        navMode === 'all') &&
                                                    task._taskListTitle
                                                "
                                                class="rounded bg-gt-field-muted px-1.5 py-0.5 text-[11px] text-gt-muted sm:text-xs"
                                            >
                                                {{ task._taskListTitle }}
                                            </span>
                                        </div>
                                        <TaskNotesRichText
                                            v-if="task.notes"
                                            class="mt-1 line-clamp-2 text-[13px] leading-relaxed text-gt-muted sm:text-sm"
                                            :text="task.notes"
                                        />
                                        <p
                                            v-if="task.recurrence?.length"
                                            class="mt-0.5 text-[11px] text-gt-muted sm:text-xs"
                                        >
                                            {{ task.recurrence.join(', ') }}
                                        </p>
                                    </div>
                                    <div
                                        v-show="!swipeRowsEnabled"
                                        class="flex shrink-0 flex-col items-end gap-2 sm:flex-row sm:items-center"
                                    >
                                        <TaskDeferMenu
                                            touch-comfortable
                                            :disabled="task._optimistic"
                                            @pick="applyDeferPreset(task, $event)"
                                        />
                                        <button
                                            type="button"
                                            class="inline-flex min-h-11 touch-manipulation items-center rounded-md px-3 text-xs font-medium text-gt-accent hover:bg-gt-accent-tint/30 disabled:text-gt-subtle dark:hover:bg-gt-accent-tint/15 dark:disabled:text-gt-subtle"
                                            :disabled="task._optimistic"
                                            @click.stop="openEditInspector(task)"
                                        >
                                            {{ t('tasks.taskDetails') }}
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex min-h-11 touch-manipulation items-center rounded-md px-3 text-sm text-red-600 hover:bg-red-50 hover:text-red-800 disabled:text-gt-subtle dark:hover:bg-red-950/30 dark:text-red-400 dark:hover:text-red-300 dark:disabled:text-gt-subtle"
                                            :disabled="task._optimistic"
                                            @click.stop="removeTask(task)"
                                        >
                                            {{ t('tasks.delete') }}
                                        </button>
                                    </div>
                                    </div>
                                    </TaskListRowSwipe>
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
                                            :lists="taskLists"
                                            :target-list-id="editListId"
                                            @update:title="editTitle = $event"
                                            @update:due="editDue = $event"
                                            @update:recurrence="
                                                editRecurrence = $event
                                            "
                                            @update:priority="
                                                editPriority = $event
                                            "
                                            @update:notes="editNotes = $event"
                                            @update:target-list-id="
                                                editListId = $event
                                            "
                                            @save="saveEditedTask"
                                            @close="closeDetailEdit"
                                            @delete="removeInspectedTask"
                                            @notes-paste="onInspectorNotesPaste"
                                            @defer-preset="
                                                onDeferPresetFromPanel
                                            "
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
                                    <div class="mb-3 flex flex-wrap items-center gap-3">
                                        <div
                                            class="inline-flex gap-0.5 rounded-lg bg-gt-field-muted p-0.5"
                                            role="group"
                                            :aria-label="t('tasks.boardGroupLabel')"
                                        >
                                            <button
                                                type="button"
                                                class="min-h-9 touch-manipulation rounded-md px-3 py-1 text-xs font-medium transition"
                                                :class="viewModeToggleClass(boardGroupMode === 'priority')"
                                                @click="boardGroupMode = 'priority'"
                                            >
                                                {{ t('tasks.boardGroupPriority') }}
                                            </button>
                                            <button
                                                type="button"
                                                class="min-h-9 touch-manipulation rounded-md px-3 py-1 text-xs font-medium transition"
                                                :class="viewModeToggleClass(boardGroupMode === 'due')"
                                                @click="boardGroupMode = 'due'"
                                            >
                                                {{ t('tasks.boardGroupDue') }}
                                            </button>
                                        </div>
                                        <p class="text-xs text-gt-muted">
                                            {{ boardGroupMode === 'due' ? t('tasks.kanbanHintDue') : t('tasks.kanbanHint') }}
                                        </p>
                                    </div>
                                    <TasksKanbanBoard
                                        :buckets="kanbanBuckets"
                                        :group-mode="boardGroupMode"
                                        :nav-mode="navMode"
                                        :is-task-selected="isTaskSelected"
                                        :task-key="taskKey"
                                        :list-id-for-task="listIdForTask"
                                        @drop-priority="onKanbanDropPriority"
                                        @drop-due="onKanbanDropDue"
                                        @task-click="onKanbanTaskClick"
                                        @selection-click="onKanbanSelectionClick"
                                        @toggle-complete="toggleComplete"
                                        @inspect-task="openEditInspector"
                                        @defer-preset="
                                            ({ task, preset }) =>
                                                applyDeferPreset(
                                                    task,
                                                    preset,
                                                )
                                        "
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
                                                    :lists="taskLists"
                                                    :target-list-id="editListId"
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
                                                    @update:target-list-id="
                                                        editListId = $event
                                                    "
                                                    @save="saveEditedTask"
                                                    @close="closeDetailEdit"
                                                    @delete="
                                                        removeInspectedTask
                                                    "
                                                    @notes-paste="
                                                        onInspectorNotesPaste
                                                    "
                                                    @defer-preset="
                                                        onDeferPresetFromPanel
                                                    "
                                                />
                                            </div>
                                        </template>
                                    </TasksKanbanBoard>
                                </template>
                            </div>
                            </div><!-- /pullIndicatorStyle -->
                            </div><!-- /taskAreaRef -->
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
                            class="flex min-h-12 w-full touch-manipulation items-center px-4 text-left text-sm"
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
                            class="flex min-h-12 w-full touch-manipulation items-center px-4 text-left text-sm"
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
                            class="flex min-h-12 w-full touch-manipulation items-center px-4 text-left text-sm"
                            :class="navButtonClass(navMode === 'all')"
                            @click="
                                setNav('all');
                                showListDrawer = false;
                            "
                        >
                            {{ t('tasks.navAll') }}
                        </button>
                        <!-- Pinned lists in drawer -->
                        <template v-if="pinnedLists.length > 0">
                            <div
                                class="border-t border-gt-border px-4 py-2 text-xs font-semibold uppercase text-gt-subtle"
                            >
                                {{ t('tasks.pinnedLists') }}
                            </div>
                            <button
                                v-for="list in pinnedLists"
                                :key="`drawer-pin-${list.id}`"
                                type="button"
                                class="flex min-h-12 w-full touch-manipulation items-center gap-2 truncate px-4 text-left text-sm"
                                :class="
                                    navButtonClass(
                                        navMode === 'list' &&
                                            selectedListId === list.id,
                                    )
                                "
                                @click="selectList(list)"
                            >
                                <svg class="h-3.5 w-3.5 shrink-0 text-gt-accent opacity-60" viewBox="0 0 16 16" fill="currentColor"><path d="M9.828.722a.5.5 0 0 1 .354.146l4.95 4.95a.5.5 0 0 1-.707.708l-.812-.813-3.04 3.04a4 4 0 0 1-.79 5.088l-.353.353a.5.5 0 0 1-.707 0L6.17 11.64l-3.96 3.96a.5.5 0 1 1-.708-.707l3.96-3.96-2.554-2.554a.5.5 0 0 1 0-.707l.354-.354a4 4 0 0 1 5.087-.79l3.04-3.04-.812-.812a.5.5 0 0 1 .353-.854z"/></svg>
                                <span class="truncate">{{ list.title }}</span>
                            </button>
                        </template>

                        <!-- Unpinned lists in drawer -->
                        <div
                            class="border-t border-gt-border px-4 py-2 text-xs font-semibold uppercase text-gt-subtle"
                        >
                            {{ pinnedLists.length > 0 ? t('tasks.otherLists') : t('tasks.allLists') }}
                        </div>
                        <button
                            v-for="list in unpinnedLists"
                            :key="`drawer-${list.id}`"
                            type="button"
                            class="flex min-h-12 w-full touch-manipulation items-center truncate px-4 text-left text-sm"
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

                        <!-- Organise lists button (mobile) -->
                        <button
                            type="button"
                            class="flex min-h-12 w-full items-center px-4 text-left text-xs text-gt-accent underline decoration-gt-accent/40 underline-offset-2"
                            @click="showOrganiseSheet = true; showListDrawer = false"
                        >
                            {{ t('tasks.organiseLists') }}
                        </button>
                    </div>

                    <!-- Mobile bottom nav -->
                    <nav
                        class="fixed bottom-0 left-0 right-0 z-30 flex min-h-[3.25rem] border-t border-gt-border bg-gt-raised pb-[env(safe-area-inset-bottom)] touch-manipulation lg:hidden"
                    >
                        <button
                            type="button"
                            class="flex min-h-12 min-w-0 flex-1 flex-col items-center justify-center gap-0.5 px-1 py-2 text-xs font-medium active:bg-gt-field-muted"
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
                            class="flex min-h-12 min-w-0 flex-1 flex-col items-center justify-center gap-0.5 px-1 py-2 text-xs font-medium active:bg-gt-field-muted"
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
                            class="flex min-h-12 min-w-0 flex-1 flex-col items-center justify-center gap-0.5 px-1 py-2 text-xs font-medium active:bg-gt-field-muted"
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
                            class="flex min-h-12 min-w-0 flex-1 flex-col items-center justify-center gap-0.5 px-1 py-2 text-xs font-medium text-gt-muted active:bg-gt-field-muted"
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
            :show="showSwipeMoreModal"
            max-width="sm"
            @close="closeSwipeMoreSheet"
        >
            <div
                v-if="swipeMoreForTask"
                class="p-6"
            >
                <h3
                    class="text-lg font-semibold text-gt-ink"
                >
                    {{ t('tasks.swipe.sheetTitle') }}
                </h3>
                <p
                    class="mt-1 truncate text-sm text-gt-muted"
                    :title="swipeMoreForTask.title"
                >
                    {{ swipeMoreForTask.title }}
                </p>
                <p
                    class="mt-3 text-xs font-semibold uppercase tracking-wide text-gt-subtle"
                >
                    {{ t('tasks.defer.menuSummary') }}
                </p>
                <div class="mt-2 grid gap-2">
                    <button
                        type="button"
                        class="w-full rounded-md border border-gt-border bg-gt-field px-3 py-2 text-start text-sm text-gt-ink touch-manipulation hover:bg-gt-field-muted"
                        @click="onSwipeDeferPreset('tomorrow')"
                    >
                        {{ t('tasks.defer.tomorrow') }}
                    </button>
                    <button
                        type="button"
                        class="w-full rounded-md border border-gt-border bg-gt-field px-3 py-2 text-start text-sm text-gt-ink touch-manipulation hover:bg-gt-field-muted"
                        @click="onSwipeDeferPreset('nextWeek')"
                    >
                        {{ t('tasks.defer.nextWeek') }}
                    </button>
                    <button
                        type="button"
                        class="w-full rounded-md border border-gt-border bg-gt-field px-3 py-2 text-start text-sm text-gt-ink touch-manipulation hover:bg-gt-field-muted"
                        @click="onSwipeDeferPreset('weekend')"
                    >
                        {{ t('tasks.defer.weekend') }}
                    </button>
                    <button
                        type="button"
                        class="w-full rounded-md border border-gt-border bg-gt-field px-3 py-2 text-start text-sm text-gt-ink touch-manipulation hover:bg-gt-field-muted"
                        @click="onSwipeDeferPreset('pickDate')"
                    >
                        {{ t('tasks.defer.pickDate') }}
                    </button>
                </div>
                <div class="mt-4 grid gap-2 border-t border-gt-border pt-4">
                    <button
                        type="button"
                        class="w-full rounded-md border border-gt-border bg-gt-field px-3 py-2 text-start text-sm font-medium text-gt-ink touch-manipulation hover:bg-gt-field-muted"
                        @click="openSwipeMoveFromSheet"
                    >
                        {{ t('tasks.swipe.moveToList') }}
                    </button>
                    <button
                        type="button"
                        class="w-full rounded-md border border-red-200 bg-red-50 px-3 py-2 text-start text-sm font-medium text-red-800 touch-manipulation hover:bg-red-100 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"
                        @click="onSwipeSheetDelete"
                    >
                        {{ t('tasks.delete') }}
                    </button>
                </div>
                <div class="mt-4 flex justify-end">
                    <SecondaryButton
                        type="button"
                        @click="closeSwipeMoreSheet"
                    >
                        {{ t('tasks.swipe.sheetClose') }}
                    </SecondaryButton>
                </div>
            </div>
        </Modal>

        <Modal
            :show="showBulkMoveModal"
            max-width="md"
            @close="onBulkMoveModalClose"
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
                        @click="onBulkMoveModalClose"
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

        <Modal
            :show="showGlobalSortSheet"
            max-width="md"
            @close="showGlobalSortSheet = false"
        >
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gt-ink">
                    {{ t('tasks.sort.label') }}
                </h3>
                <fieldset
                    class="mt-4 space-y-3"
                    aria-describedby="global-sort-sheet-hint"
                >
                    <legend class="sr-only">
                        {{ t('tasks.sort.label') }}
                    </legend>
                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-md border border-gt-border px-3 py-2"
                    >
                        <input
                            v-model="globalSortMode"
                            type="radio"
                            value="due_first"
                            class="mt-1 text-gt-accent focus:ring-gt-accent-ring"
                        />
                        <span class="text-sm text-gt-ink">{{
                            t('tasks.sort.dueFirst')
                        }}</span>
                    </label>
                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-md border border-gt-border px-3 py-2"
                    >
                        <input
                            v-model="globalSortMode"
                            type="radio"
                            value="priority_first"
                            class="mt-1 text-gt-accent focus:ring-gt-accent-ring"
                        />
                        <span class="text-sm text-gt-ink">{{
                            t('tasks.sort.priorityFirst')
                        }}</span>
                    </label>
                </fieldset>
                <p
                    id="global-sort-sheet-hint"
                    class="mt-4 text-xs leading-relaxed text-gt-muted"
                >
                    {{
                        viewMode === 'board'
                            ? t('tasks.sort.hintKanban')
                            : t('tasks.sort.hintLists')
                    }}
                </p>
                <div class="mt-6 flex justify-end">
                    <PrimaryButton
                        type="button"
                        @click="showGlobalSortSheet = false"
                    >
                        {{ t('tasks.sort.sheetDone') }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal
            :show="showTasksOnboarding"
            max-width="lg"
            @close="dismissTasksOnboarding"
        >
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gt-ink">
                    {{ t('tasks.onboarding.title') }}
                </h3>
                <p class="mt-2 text-sm text-gt-ink-secondary">
                    {{ t('tasks.onboarding.lead') }}
                </p>
                <ul
                    class="mt-4 list-disc space-y-2 ps-5 text-sm text-gt-ink-secondary"
                >
                    <li>{{ t('tasks.onboarding.bulletToday') }}</li>
                    <li>{{ t('tasks.onboarding.bulletSearch') }}</li>
                    <li>{{ t('tasks.onboarding.bulletPalette') }}</li>
                    <li>{{ t('tasks.onboarding.bulletSwipe') }}</li>
                    <li>{{ t('tasks.onboarding.bulletSort') }}</li>
                </ul>
                <div class="mt-6 flex justify-end">
                    <PrimaryButton
                        type="button"
                        @click="dismissTasksOnboarding"
                    >
                        {{ t('tasks.onboarding.dismiss') }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>

    <!-- US-033: Duplicates modal -->
    <Modal
        :show="showDuplicatesModal"
        max-width="2xl"
        @close="showDuplicatesModal = false"
    >
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gt-ink">
                {{ t('tasks.duplicates.title') }}
            </h3>

            <p
                v-if="duplicatesLoading"
                class="mt-4 text-sm text-gt-muted"
            >
                {{ t('tasks.duplicates.scanning') }}
            </p>

            <p
                v-else-if="duplicatesError"
                class="mt-4 text-sm text-red-600"
            >
                {{ duplicatesError }}
            </p>

            <p
                v-else-if="duplicatesIndexEmpty"
                class="mt-4 text-sm text-gt-muted"
            >
                {{ t('tasks.duplicates.indexEmpty') }}
            </p>

            <p
                v-else-if="duplicatePairs.length === 0"
                class="mt-4 text-sm text-gt-muted"
            >
                {{ t('tasks.duplicates.empty') }}
            </p>

            <div
                v-else
                class="mt-4 max-h-[60vh] space-y-4 overflow-y-auto"
            >
                <div
                    v-for="(pair, idx) in duplicatePairs"
                    :key="idx"
                    class="rounded-lg border border-gt-border bg-gt-field-muted/50 p-4"
                >
                    <div class="mb-2 flex items-center gap-2">
                        <span class="rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                            {{ t('tasks.duplicates.pairLabel') }}
                        </span>
                        <span class="text-xs text-gt-muted">
                            {{ Math.round(pair.score * 100) }}%
                        </span>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-md border border-gt-border bg-gt-raised p-3">
                            <p class="text-sm font-medium text-gt-ink">{{ pair.taskA.title }}</p>
                            <p class="mt-1 text-xs text-gt-muted">
                                {{ t('tasks.duplicates.listLabel', { list: pair.taskA.taskListTitle }) }}
                            </p>
                            <p
                                v-if="pair.taskA.notes"
                                class="mt-1 line-clamp-2 text-xs text-gt-subtle"
                            >
                                {{ pair.taskA.notes }}
                            </p>
                            <button
                                type="button"
                                class="mt-2 rounded bg-gt-accent px-3 py-1 text-xs font-medium text-white hover:bg-gt-accent-hover"
                                @click="startMerge(pair, 'A')"
                            >
                                {{ t('tasks.duplicates.keepLabel') }}
                            </button>
                        </div>
                        <div class="rounded-md border border-gt-border bg-gt-raised p-3">
                            <p class="text-sm font-medium text-gt-ink">{{ pair.taskB.title }}</p>
                            <p class="mt-1 text-xs text-gt-muted">
                                {{ t('tasks.duplicates.listLabel', { list: pair.taskB.taskListTitle }) }}
                            </p>
                            <p
                                v-if="pair.taskB.notes"
                                class="mt-1 line-clamp-2 text-xs text-gt-subtle"
                            >
                                {{ pair.taskB.notes }}
                            </p>
                            <button
                                type="button"
                                class="mt-2 rounded bg-gt-accent px-3 py-1 text-xs font-medium text-white hover:bg-gt-accent-hover"
                                @click="startMerge(pair, 'B')"
                            >
                                {{ t('tasks.duplicates.keepLabel') }}
                            </button>
                        </div>
                    </div>
                    <p
                        v-if="pair.keeperHint"
                        class="mt-2 text-xs italic text-gt-muted"
                    >
                        {{ t('tasks.duplicates.keeperHint') }}
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <PrimaryButton
                    type="button"
                    @click="showDuplicatesModal = false"
                >
                    {{ t('tasks.duplicates.close') }}
                </PrimaryButton>
            </div>
        </div>
    </Modal>

    <!-- US-033: Merge confirmation -->
    <Modal
        :show="duplicateMergeConfirm !== null"
        max-width="md"
        @close="duplicateMergeConfirm = null"
    >
        <div
            v-if="duplicateMergeConfirm"
            class="p-6"
        >
            <h3 class="text-lg font-semibold text-gt-ink">
                {{ t('tasks.duplicates.mergeConfirmTitle') }}
            </h3>
            <p class="mt-2 text-sm text-gt-ink-secondary">
                {{
                    t('tasks.duplicates.mergeConfirmBody', {
                        title: duplicateMergeConfirm.keepSide === 'A'
                            ? duplicateMergeConfirm.pair.taskB.title
                            : duplicateMergeConfirm.pair.taskA.title,
                    })
                }}
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    class="rounded-md px-4 py-2 text-sm font-medium text-gt-ink-secondary hover:bg-gt-field-muted"
                    @click="duplicateMergeConfirm = null"
                >
                    {{ t('tasks.duplicates.mergeConfirmCancel') }}
                </button>
                <button
                    type="button"
                    class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                    @click="confirmMerge"
                >
                    {{ t('tasks.duplicates.mergeConfirmOk') }}
                </button>
            </div>
        </div>
    </Modal>

    <TasksCommandPalette
        :show="showCommandPalette"
        :items="commandPaletteItems"
        @close="showCommandPalette = false"
        @select="handleCommandPaletteSelect"
        @quick-add="handleCommandPaletteQuickAdd"
    />

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

    <!-- Organise Lists sheet -->
    <TaskListOrganiseSheet
        :show="showOrganiseSheet"
        :lists="taskLists"
        :auto-sort="listAutoSort"
        @close="showOrganiseSheet = false"
        @save="onOrganiseListsSave"
    />
</template>

<style scoped>
@media (prefers-reduced-motion: no-preference) {
    .gt-tasks-loading-enter-active,
    .gt-tasks-loading-leave-active {
        transition: opacity 0.2s ease-out;
    }
}
.gt-tasks-loading-enter-from,
.gt-tasks-loading-leave-to {
    opacity: 0;
}
@media (prefers-reduced-motion: reduce) {
    .gt-tasks-loading-enter-active,
    .gt-tasks-loading-leave-active {
        transition: none;
    }
}
</style>
