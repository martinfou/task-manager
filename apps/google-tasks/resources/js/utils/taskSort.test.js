import { describe, expect, it } from 'vitest';
import {
    compareTasksGlobalSort,
    dueUrgencyCohort,
    isTitleEmpty,
    normalizeGlobalSortMode,
    priorityRank,
    sortTasksGlobally,
} from './taskSort.js';

describe('normalizeGlobalSortMode', () => {
    it('returns due_first by default', () => {
        expect(normalizeGlobalSortMode(undefined)).toBe('due_first');
        expect(normalizeGlobalSortMode('')).toBe('due_first');
    });

    it('accepts valid modes', () => {
        expect(normalizeGlobalSortMode('priority_first')).toBe(
            'priority_first',
        );
    });
});

describe('priorityRank', () => {
    it('orders p1 before p4', () => {
        expect(priorityRank({ priority: 'p1' })).toBe(1);
        expect(priorityRank({ priority: 'p4' })).toBe(4);
    });
});

describe('dueUrgencyCohort', () => {
    it('puts completed last', () => {
        const t0 = {
            status: 'completed',
            due: new Date().toISOString(),
        };
        expect(dueUrgencyCohort(t0)).toBe(4);
    });
});

describe('isTitleEmpty', () => {
    it('treats whitespace as empty', () => {
        expect(isTitleEmpty({ title: '   ' })).toBe(true);
        expect(isTitleEmpty({ title: 'a' })).toBe(false);
    });
});

describe('sortTasksGlobally', () => {
    const locale = 'en';

    it('due_first: overdue before due today', () => {
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        const tasks = [
            {
                id: 'b',
                status: 'needsAction',
                priority: 'p3',
                due: new Date().toISOString(),
                title: 'today',
            },
            {
                id: 'a',
                status: 'needsAction',
                priority: 'p3',
                due: yesterday.toISOString(),
                title: 'late',
            },
        ];
        const sorted = sortTasksGlobally(tasks, 'due_first', locale);
        expect(sorted[0].id).toBe('a');
        expect(sorted[1].id).toBe('b');
    });

    it('due_first: higher priority within same cohort', () => {
        const d = new Date();
        d.setDate(d.getDate() + 2);
        const tasks = [
            {
                id: 'low',
                status: 'needsAction',
                priority: 'p4',
                due: d.toISOString(),
                title: 'x',
            },
            {
                id: 'high',
                status: 'needsAction',
                priority: 'p1',
                due: d.toISOString(),
                title: 'y',
            },
        ];
        const sorted = sortTasksGlobally(tasks, 'due_first', locale);
        expect(sorted[0].id).toBe('high');
    });

    it('priority_first: priority beats due cohort', () => {
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        const tasks = [
            {
                id: 'overdue_p4',
                status: 'needsAction',
                priority: 'p4',
                due: yesterday.toISOString(),
                title: 'a',
            },
            {
                id: 'today_p1',
                status: 'needsAction',
                priority: 'p1',
                due: new Date().toISOString(),
                title: 'b',
            },
        ];
        const sorted = sortTasksGlobally(tasks, 'priority_first', locale);
        expect(sorted[0].id).toBe('today_p1');
        expect(sorted[1].id).toBe('overdue_p4');
    });

    it('uses id as final tie-breaker', () => {
        const d = new Date();
        d.setDate(d.getDate() + 3);
        const tasks = [
            {
                id: 'z',
                status: 'needsAction',
                priority: 'p3',
                due: d.toISOString(),
                title: 'same',
            },
            {
                id: 'a',
                status: 'needsAction',
                priority: 'p3',
                due: d.toISOString(),
                title: 'same',
            },
        ];
        const sorted = sortTasksGlobally(tasks, 'due_first', locale);
        expect(sorted[0].id).toBe('a');
        expect(sorted[1].id).toBe('z');
    });
});

describe('compareTasksGlobalSort', () => {
    it('is antisymmetric for distinct tasks', () => {
        const a = {
            id: '1',
            status: 'needsAction',
            priority: 'p3',
            title: 'x',
        };
        const b = {
            id: '2',
            status: 'needsAction',
            priority: 'p3',
            title: 'y',
        };
        const ab = compareTasksGlobalSort(a, b, 'due_first', 'en');
        const ba = compareTasksGlobalSort(b, a, 'due_first', 'en');
        expect(ab === 0 ? 0 : -Math.sign(ab)).toBe(Math.sign(ba));
    });
});
