/**
 * Client-side task filters (API-backed fields only). Used by US-012.
 *
 * @typedef {'all' | 'needsAction' | 'completed'} CompletionFilter
 * @typedef {'any' | 'overdue' | 'today' | 'hasDue' | 'noDue'} DueFilter
 * @typedef {'all' | 'p1' | 'p2' | 'p3' | 'p4'} PriorityFilter
 * @typedef {{ completion: CompletionFilter, due: DueFilter, priority: PriorityFilter, listId: string | null }} TaskFilterState
 */

/**
 * US-038: Google Tasks sends `T00:00:00.000Z` for date-only due values.
 * Detect this pattern so we can parse as a local calendar date (not UTC).
 * @param {string} raw
 * @returns {boolean}
 */
export function isDueDateOnly(raw) {
    if (typeof raw !== 'string') return false;
    return /T00:00:00(?:\.0+)?Z$/i.test(raw);
}

/**
 * Parse a due date string into a local Date.
 *
 * Date-only values (T00:00:00.000Z — Google convention) are parsed as local
 * noon on that calendar day so filtering and display use the correct date
 * regardless of the user's timezone. Values with a real time are parsed
 * normally (respecting the UTC offset or Z suffix).
 *
 * @param {string | undefined | null} dueRaw
 * @returns {Date | null}
 */
export function parseDueDate(dueRaw) {
    if (dueRaw == null || dueRaw === '') {
        return null;
    }
    if (isDueDateOnly(dueRaw)) {
        // Extract YYYY-MM-DD and create a local date at noon.
        const m = dueRaw.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (m) {
            return new Date(+m[1], +m[2] - 1, +m[3], 12, 0, 0);
        }
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

/**
 * End of the current calendar week (Sunday 23:59:59.999 local, Monday–Sunday week).
 * @param {Date} [now]
 * @returns {Date}
 */
export function endOfCurrentWeek(now) {
    const d = now ? new Date(now) : new Date();
    const day = d.getDay(); // 0=Sun … 6=Sat
    const daysUntilSunday = day === 0 ? 0 : 7 - day;
    d.setDate(d.getDate() + daysUntilSunday);
    d.setHours(23, 59, 59, 999);
    return d;
}

/**
 * First day (Monday 00:00) of the week after the current one.
 * @param {Date} [now]
 * @returns {Date}
 */
export function startOfNextWeek(now) {
    const end = endOfCurrentWeek(now);
    const d = new Date(end);
    d.setDate(d.getDate() + 1);
    d.setHours(0, 0, 0, 0);
    return d;
}

/**
 * Classify a task's due relative to a reference date.
 * Returns 'overdue' | 'today' | 'thisWeek' | 'laterNoDate'.
 * @param {object} task
 * @param {Date} [now]
 * @returns {string}
 */
export function dueLaneForTask(task, now) {
    if (task.status === 'completed') {
        return 'laterNoDate';
    }
    const due = parseDueDate(task.due);
    if (!due) {
        return 'laterNoDate';
    }
    const ref = now || new Date();
    const dueDay = calendarDayStart(due);
    const todayDay = calendarDayStart(ref);
    if (dueDay < todayDay) {
        return 'overdue';
    }
    if (dueDay === todayDay) {
        return 'today';
    }
    const weekEnd = endOfCurrentWeek(ref);
    if (dueDay <= weekEnd.getTime()) {
        return 'thisWeek';
    }
    return 'laterNoDate';
}

/**
 * Is the task's due after today but within the current calendar week (Mon–Sun)?
 * @param {object} task
 * @param {Date} [now]
 * @returns {boolean}
 */
export function isTaskDueThisWeek(task, now) {
    return dueLaneForTask(task, now) === 'thisWeek';
}

/**
 * Group tasks into due-date lanes: overdue, today, thisWeek, laterNoDate.
 * @param {object[]} tasks
 * @param {Date} [now]  - injectable for testing
 * @returns {{ overdue: object[], today: object[], thisWeek: object[], laterNoDate: object[] }}
 */
export function groupTasksByDueLane(tasks, now) {
    const buckets = { overdue: [], today: [], thisWeek: [], laterNoDate: [] };
    for (const t of tasks) {
        buckets[dueLaneForTask(t, now)].push(t);
    }
    return buckets;
}

/**
 * Map a due-lane key to an RFC3339 date string for drop-to-reschedule.
 * @param {string} lane - 'overdue' | 'today' | 'thisWeek' | 'laterNoDate'
 * @param {Date} [now]
 * @returns {string} ISO date string (YYYY-MM-DDT00:00:00.000Z format for Google Tasks)
 */
export function dueLaneDateMapping(lane, now) {
    const ref = now || new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const toIso = (d) =>
        `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T00:00:00.000Z`;

    if (lane === 'overdue') {
        const d = new Date(ref);
        d.setDate(d.getDate() - 1);
        return toIso(d);
    }
    if (lane === 'today') {
        return toIso(ref);
    }
    if (lane === 'thisWeek') {
        // Tomorrow if still in same week, otherwise last day of current week
        const tomorrow = new Date(ref);
        tomorrow.setDate(tomorrow.getDate() + 1);
        const weekEnd = endOfCurrentWeek(ref);
        if (calendarDayStart(tomorrow) <= weekEnd.getTime()) {
            return toIso(tomorrow);
        }
        const sun = new Date(weekEnd);
        sun.setHours(0, 0, 0, 0);
        return toIso(sun);
    }
    // laterNoDate → first day after current week (Monday)
    const next = startOfNextWeek(ref);
    return toIso(next);
}
