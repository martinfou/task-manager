import { describe, it, expect } from 'vitest';
import { useTaskCache } from './useTaskCache';

describe('useTaskCache', () => {
    it('returns null for cold cache', () => {
        const cache = useTaskCache();
        expect(cache.get('today')).toBeNull();
        expect(cache.get('list', 'abc')).toBeNull();
        expect(cache.has('inbox')).toBe(false);
    });

    it('stores and retrieves tasks by nav mode', () => {
        const cache = useTaskCache();
        const tasks = [{ id: '1', title: 'Test' }];
        cache.set('today', null, tasks);
        expect(cache.get('today')).toEqual(tasks);
        expect(cache.has('today')).toBe(true);
    });

    it('stores and retrieves tasks by list ID', () => {
        const cache = useTaskCache();
        const tasksA = [{ id: '1' }];
        const tasksB = [{ id: '2' }];
        cache.set('list', 'listA', tasksA);
        cache.set('list', 'listB', tasksB);
        expect(cache.get('list', 'listA')).toEqual(tasksA);
        expect(cache.get('list', 'listB')).toEqual(tasksB);
    });

    it('returns a copy so mutations do not affect cache', () => {
        const cache = useTaskCache();
        const original = [{ id: '1' }];
        cache.set('today', null, original);
        original.push({ id: '2' });
        expect(cache.get('today')).toHaveLength(1);
    });

    it('invalidates a specific context', () => {
        const cache = useTaskCache();
        cache.set('today', null, [{ id: '1' }]);
        cache.invalidate('today');
        expect(cache.get('today')).toBeNull();
    });

    it('invalidateForList clears list and aggregate views', () => {
        const cache = useTaskCache();
        cache.set('list', 'abc', [{ id: '1' }]);
        cache.set('today', null, [{ id: '2' }]);
        cache.set('inbox', null, [{ id: '3' }]);
        cache.set('all', null, [{ id: '4' }]);
        cache.set('list', 'other', [{ id: '5' }]);

        cache.invalidateForList('abc');

        expect(cache.get('list', 'abc')).toBeNull();
        expect(cache.get('today')).toBeNull();
        expect(cache.get('inbox')).toBeNull();
        expect(cache.get('all')).toBeNull();
        // Other lists unaffected
        expect(cache.get('list', 'other')).toEqual([{ id: '5' }]);
    });

    it('flush clears everything', () => {
        const cache = useTaskCache();
        cache.set('today', null, [{ id: '1' }]);
        cache.set('list', 'a', [{ id: '2' }]);
        cache.flush();
        expect(cache.get('today')).toBeNull();
        expect(cache.get('list', 'a')).toBeNull();
    });

    it('version increments on writes and flushes', () => {
        const cache = useTaskCache();
        const v0 = cache.version.value;
        cache.set('today', null, []);
        expect(cache.version.value).toBe(v0 + 1);
        cache.flush();
        expect(cache.version.value).toBe(v0 + 2);
    });
});
