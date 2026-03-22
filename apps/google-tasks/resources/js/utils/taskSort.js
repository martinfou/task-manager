/**
 * Global task ordering for list views and Kanban columns (US-027).
 *
 * Modes: due_first (default) | priority_first.
 * Tie-breaker after title: Google task `id` string compare (Question 16).
 */

import {
    isTaskDueToday,
    isTaskOverdue,
    parseDueDate,
} from '@/utils/taskFilters.js';

/** @typedef {'due_first' | 'priority_first'} GlobalTaskSortMode */

export const GLOBAL_TASK_SORT_STORAGE_KEY = 'gt-task-sort-mode';

/** @type {GlobalTaskSortMode} */
export const DEFAULT_GLOBAL_TASK_SORT_MODE = 'due_first';

/**
 * @param {unknown} raw
 * @returns {GlobalTaskSortMode}
 */
export function normalizeGlobalSortMode(raw) {
    if (raw === 'priority_first' || raw === 'due_first') {
        return raw;
    }
    return DEFAULT_GLOBAL_TASK_SORT_MODE;
}

/**
 * @param {object} task
 * @returns {number} 1 = highest … 4 = lowest
 */
export function priorityRank(task) {
    const p = String(task.priority ?? 'p3').toLowerCase();
    if (p === 'p1') {
        return 1;
    }
    if (p === 'p2') {
        return 2;
    }
    if (p === 'p4') {
        return 4;
    }
    return 3;
}

/**
 * Incomplete: overdue → due today → future due → no due.
 * Completed tasks sort after all incomplete (cohort 4).
 *
 * @param {object} task
 * @returns {number}
 */
export function dueUrgencyCohort(task) {
    if (task.status === 'completed') {
        return 4;
    }
    if (isTaskOverdue(task)) {
        return 0;
    }
    if (isTaskDueToday(task)) {
        return 1;
    }
    const due = parseDueDate(task.due);
    if (due) {
        return 2;
    }
    return 3;
}

/**
 * Earlier calendar time = more urgent for overdue; for ordering within same cohort.
 *
 * @param {object} task
 * @returns {number}
 */
function dueTimeMs(task) {
    const d = parseDueDate(task.due);
    return d ? d.getTime() : 0;
}

/**
 * Empty / whitespace-only titles sort before any non-empty title (Question 18).
 *
 * @param {object} task
 * @returns {boolean}
 */
export function isTitleEmpty(task) {
    const s = task.title == null ? '' : String(task.title);
    return s.trim() === '';
}

/**
 * @param {object} a
 * @param {object} b
 * @param {string} locale
 * @returns {number}
 */
function compareTitle(a, b, locale) {
    const ea = isTitleEmpty(a);
    const eb = isTitleEmpty(b);
    if (ea !== eb) {
        return ea ? -1 : 1;
    }
    const sa = String(a.title ?? '');
    const sb = String(b.title ?? '');
    const c = sa.localeCompare(sb, locale, {
        sensitivity: 'base',
    });
    if (c !== 0) {
        return c;
    }
    return String(a.id ?? '').localeCompare(String(b.id ?? ''));
}

/**
 * @param {object} a
 * @param {object} b
 * @param {string} locale
 * @returns {number}
 */
function compareDueFirst(a, b, locale) {
    const ca = dueUrgencyCohort(a);
    const cb = dueUrgencyCohort(b);
    if (ca !== cb) {
        return ca - cb;
    }

    const pa = priorityRank(a);
    const pb = priorityRank(b);
    if (pa !== pb) {
        return pa - pb;
    }

    const ta = dueTimeMs(a);
    const tb = dueTimeMs(b);
    if (ta !== tb) {
        return ta - tb;
    }

    return compareTitle(a, b, locale);
}

/**
 * @param {object} a
 * @param {object} b
 * @param {string} locale
 * @returns {number}
 */
function comparePriorityFirst(a, b, locale) {
    const pa = priorityRank(a);
    const pb = priorityRank(b);
    if (pa !== pb) {
        return pa - pb;
    }

    const ca = dueUrgencyCohort(a);
    const cb = dueUrgencyCohort(b);
    if (ca !== cb) {
        return ca - cb;
    }

    const ta = dueTimeMs(a);
    const tb = dueTimeMs(b);
    if (ta !== tb) {
        return ta - tb;
    }

    return compareTitle(a, b, locale);
}

/**
 * @param {object} a
 * @param {object} b
 * @param {GlobalTaskSortMode} mode
 * @param {string} [locale]
 * @returns {number}
 */
export function compareTasksGlobalSort(a, b, mode, locale = 'en') {
    return mode === 'priority_first'
        ? comparePriorityFirst(a, b, locale)
        : compareDueFirst(a, b, locale);
}

/**
 * Stable sort of a task array using the global comparator.
 *
 * @param {object[]} tasks
 * @param {GlobalTaskSortMode} mode
 * @param {string} [locale]
 * @returns {object[]}
 */
export function sortTasksGlobally(tasks, mode, locale = 'en') {
    const copy = tasks.slice();
    copy.sort((a, b) => compareTasksGlobalSort(a, b, mode, locale));
    return copy;
}
