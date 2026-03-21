import { describe, expect, it } from 'vitest';
import {
    filterTasks,
    parseDueDate,
    taskMatchesFilters,
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
