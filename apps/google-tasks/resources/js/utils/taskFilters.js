/**
 * Client-side task filters (API-backed fields only). Used by US-012.
 *
 * @typedef {'all' | 'needsAction' | 'completed'} CompletionFilter
 * @typedef {'any' | 'overdue' | 'today' | 'hasDue' | 'noDue'} DueFilter
 * @typedef {'all' | 'p1' | 'p2' | 'p3' | 'p4'} PriorityFilter
 * @typedef {{ completion: CompletionFilter, due: DueFilter, priority: PriorityFilter, listId: string | null }} TaskFilterState
 */

/**
 * @param {string | undefined | null} dueRaw
 * @returns {Date | null}
 */
export function parseDueDate(dueRaw) {
    if (dueRaw == null || dueRaw === '') {
        return null;
    }
    const d = new Date(dueRaw);
    return Number.isNaN(d.getTime()) ? null : d;
}

function calendarDayStart(d) {
    const x = new Date(d);
    x.setHours(0, 0, 0, 0);
    return x.getTime();
}

/**
 * Incomplete task whose due calendar day is before today.
 * @param {object} task
 * @returns {boolean}
 */
export function isTaskOverdue(task) {
    if (task.status === 'completed') {
        return false;
    }
    const due = parseDueDate(task.due);
    if (!due) {
        return false;
    }
    return calendarDayStart(due) < calendarDayStart(new Date());
}

/**
 * Due date falls on today's calendar day (local).
 * @param {object} task
 * @returns {boolean}
 */
export function isTaskDueToday(task) {
    if (task.status === 'completed') {
        return false;
    }
    const due = parseDueDate(task.due);
    if (!due) {
        return false;
    }
    return calendarDayStart(due) === calendarDayStart(new Date());
}

/**
 * @param {object} task
 * @param {TaskFilterState} f
 * @returns {boolean}
 */
export function taskMatchesFilters(task, f) {
    if (f.completion === 'needsAction' && task.status === 'completed') {
        return false;
    }
    if (f.completion === 'completed' && task.status !== 'completed') {
        return false;
    }

    if (f.priority !== 'all') {
        const p = (task.priority ?? 'p3').toLowerCase();
        if (p !== f.priority) {
            return false;
        }
    }

    if (f.listId && task._taskListId !== f.listId) {
        return false;
    }

    const due = parseDueDate(task.due);

    if (f.due === 'noDue') {
        if (due) {
            return false;
        }
    } else if (f.due === 'hasDue') {
        if (!due) {
            return false;
        }
    } else if (f.due === 'overdue') {
        if (!isTaskOverdue(task)) {
            return false;
        }
    } else if (f.due === 'today') {
        if (!isTaskDueToday(task)) {
            return false;
        }
    }

    return true;
}

/**
 * @param {object[]} tasks
 * @param {TaskFilterState} f
 * @returns {object[]}
 */
export function filterTasks(tasks, f) {
    return tasks.filter((t) => taskMatchesFilters(t, f));
}

/**
 * Group tasks into P1–P4 buckets (for Kanban). Uses decoded priority.
 * @param {object[]} tasks
 * @returns {{ p1: object[], p2: object[], p3: object[], p4: object[] }}
 */
export function groupTasksByPriority(tasks) {
    const buckets = { p1: [], p2: [], p3: [], p4: [] };
    for (const t of tasks) {
        const p = (t.priority ?? 'p3').toLowerCase();
        if (p === 'p1') {
            buckets.p1.push(t);
        } else if (p === 'p2') {
            buckets.p2.push(t);
        } else if (p === 'p3') {
            buckets.p3.push(t);
        } else if (p === 'p4') {
            buckets.p4.push(t);
        } else {
            buckets.p3.push(t);
        }
    }
    return buckets;
}
