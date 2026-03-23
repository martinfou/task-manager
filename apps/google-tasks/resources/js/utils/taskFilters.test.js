import { describe, expect, it } from 'vitest';
import {
    filterTasks,
    isDueDateOnly,
    parseDueDate,
    taskMatchesFilters,
    endOfCurrentWeek,
    startOfNextWeek,
    isTaskDueThisWeek,
    groupTasksByDueLane,
    dueLaneDateMapping,
} from './taskFilters.js';

describe('parseDueDate', () => {
    it('returns null for empty', () => {
        expect(parseDueDate('')).toBeNull();
        expect(parseDueDate(null)).toBeNull();
    });

    it('parses ISO string', () => {
        const d = parseDueDate('2026-03-21T12:00:00.000Z');
        expect(d).toBeInstanceOf(Date);
        expect(d.getTime()).not.toBeNaN();
    });
});

describe('isDueDateOnly', () => {
    it('detects Google Tasks date-only format', () => {
        expect(isDueDateOnly('2026-03-22T00:00:00.000Z')).toBe(true);
    });

    it('detects short fractional seconds', () => {
        expect(isDueDateOnly('2026-03-22T00:00:00.0Z')).toBe(true);
        expect(isDueDateOnly('2026-03-22T00:00:00Z')).toBe(true);
    });

    it('rejects values with real time', () => {
        expect(isDueDateOnly('2026-03-22T14:30:00.000Z')).toBe(false);
        expect(isDueDateOnly('2026-03-22T00:00:01.000Z')).toBe(false);
    });

    it('rejects non-strings', () => {
        expect(isDueDateOnly(null)).toBe(false);
        expect(isDueDateOnly(undefined)).toBe(false);
        expect(isDueDateOnly(12345)).toBe(false);
    });
});

describe('parseDueDate — date-only (US-038)', () => {
    it('parses date-only as local noon, not UTC midnight', () => {
        const d = parseDueDate('2026-03-22T00:00:00.000Z');
        // Should be Mar 22 local, not Mar 21 (which UTC midnight becomes in Western timezones)
        expect(d.getDate()).toBe(22);
        expect(d.getMonth()).toBe(2); // March = 2
        expect(d.getHours()).toBe(12); // local noon
    });

    it('parses real datetime normally', () => {
        const d = parseDueDate('2026-03-22T14:30:00.000Z');
        expect(d).toBeInstanceOf(Date);
        // This should be parsed as UTC, so getUTCHours should be 14
        expect(d.getUTCHours()).toBe(14);
    });
});

describe('taskMatchesFilters', () => {
    const baseTask = {
        id: '1',
        title: 'T',
        status: 'needsAction',
        priority: 'p3',
    };

    it('filters by completion', () => {
        const done = { ...baseTask, status: 'completed' };
        expect(
            taskMatchesFilters(done, {
                completion: 'needsAction',
                due: 'any',
                priority: 'all',
                listId: null,
            }),
        ).toBe(false);
        expect(
            taskMatchesFilters(done, {
                completion: 'completed',
                due: 'any',
                priority: 'all',
                listId: null,
            }),
        ).toBe(true);
    });

    it('filterTasks returns subset', () => {
        const tasks = [
            baseTask,
            { ...baseTask, id: '2', status: 'completed' },
        ];
        const filtered = filterTasks(tasks, {
            completion: 'needsAction',
            due: 'any',
            priority: 'all',
            listId: null,
        });
        expect(filtered).toHaveLength(1);
        expect(filtered[0].id).toBe('1');
    });
});

describe('endOfCurrentWeek', () => {
    it('returns Sunday 23:59:59 for a Wednesday', () => {
        // 2026-03-25 is a Wednesday
        const wed = new Date(2026, 2, 25, 10, 0, 0);
        const end = endOfCurrentWeek(wed);
        expect(end.getDay()).toBe(0); // Sunday
        expect(end.getDate()).toBe(29);
        expect(end.getHours()).toBe(23);
    });

    it('returns same day for a Sunday', () => {
        // 2026-03-29 is a Sunday
        const sun = new Date(2026, 2, 29, 8, 0, 0);
        const end = endOfCurrentWeek(sun);
        expect(end.getDay()).toBe(0);
        expect(end.getDate()).toBe(29);
    });
});

describe('startOfNextWeek', () => {
    it('returns Monday after current week', () => {
        const wed = new Date(2026, 2, 25, 10, 0, 0);
        const next = startOfNextWeek(wed);
        expect(next.getDay()).toBe(1); // Monday
        expect(next.getDate()).toBe(30);
        expect(next.getHours()).toBe(0);
    });
});

/** Build an ISO string from a local Date (noon to avoid DST edge). */
function localIso(y, m, d) {
    const dt = new Date(y, m - 1, d, 12, 0, 0);
    return dt.toISOString();
}

describe('isTaskDueThisWeek', () => {
    // Reference: Wednesday 2026-03-25
    const now = new Date(2026, 2, 25, 10, 0, 0);

    it('returns true for due on Thursday (same week)', () => {
        const task = { id: '1', status: 'needsAction', due: localIso(2026, 3, 26) };
        expect(isTaskDueThisWeek(task, now)).toBe(true);
    });

    it('returns false for due today', () => {
        const task = { id: '1', status: 'needsAction', due: localIso(2026, 3, 25) };
        expect(isTaskDueThisWeek(task, now)).toBe(false);
    });

    it('returns false for overdue', () => {
        const task = { id: '1', status: 'needsAction', due: localIso(2026, 3, 20) };
        expect(isTaskDueThisWeek(task, now)).toBe(false);
    });

    it('returns false for next week', () => {
        const task = { id: '1', status: 'needsAction', due: localIso(2026, 3, 31) };
        expect(isTaskDueThisWeek(task, now)).toBe(false);
    });

    it('returns false for completed tasks', () => {
        const task = { id: '1', status: 'completed', due: localIso(2026, 3, 26) };
        expect(isTaskDueThisWeek(task, now)).toBe(false);
    });

    it('returns false for no due', () => {
        const task = { id: '1', status: 'needsAction', due: null };
        expect(isTaskDueThisWeek(task, now)).toBe(false);
    });
});

describe('groupTasksByDueLane', () => {
    // Reference: Wednesday 2026-03-25
    const now = new Date(2026, 2, 25, 10, 0, 0);
    const overdue = { id: 'a', status: 'needsAction', due: localIso(2026, 3, 20) };
    const today = { id: 'b', status: 'needsAction', due: localIso(2026, 3, 25) };
    const thisWeek = { id: 'c', status: 'needsAction', due: localIso(2026, 3, 27) };
    const later = { id: 'd', status: 'needsAction', due: localIso(2026, 4, 5) };
    const noDue = { id: 'e', status: 'needsAction', due: null };

    it('groups tasks into correct lanes', () => {
        const result = groupTasksByDueLane([overdue, today, thisWeek, later, noDue], now);
        expect(result.overdue.map((t) => t.id)).toEqual(['a']);
        expect(result.today.map((t) => t.id)).toEqual(['b']);
        expect(result.thisWeek.map((t) => t.id)).toEqual(['c']);
        expect(result.laterNoDate.map((t) => t.id)).toEqual(['d', 'e']);
    });

    it('places each task in exactly one lane', () => {
        const tasks = [overdue, today, thisWeek, later, noDue];
        const result = groupTasksByDueLane(tasks, now);
        const total =
            result.overdue.length +
            result.today.length +
            result.thisWeek.length +
            result.laterNoDate.length;
        expect(total).toBe(tasks.length);
    });
});

describe('dueLaneDateMapping', () => {
    // Reference: Wednesday 2026-03-25
    const now = new Date(2026, 2, 25, 10, 0, 0);

    it('overdue maps to yesterday', () => {
        const iso = dueLaneDateMapping('overdue', now);
        expect(iso).toContain('2026-03-24');
    });

    it('today maps to today', () => {
        const iso = dueLaneDateMapping('today', now);
        expect(iso).toContain('2026-03-25');
    });

    it('thisWeek maps to tomorrow when in same week', () => {
        const iso = dueLaneDateMapping('thisWeek', now);
        expect(iso).toContain('2026-03-26');
    });

    it('laterNoDate maps to Monday after current week', () => {
        const iso = dueLaneDateMapping('laterNoDate', now);
        expect(iso).toContain('2026-03-30');
    });

    it('returns valid ISO strings', () => {
        for (const lane of ['overdue', 'today', 'thisWeek', 'laterNoDate']) {
            const iso = dueLaneDateMapping(lane, now);
            expect(new Date(iso).getTime()).not.toBeNaN();
        }
    });
});
