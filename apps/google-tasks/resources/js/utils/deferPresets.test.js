import { describe, expect, it } from 'vitest';
import {
    computeDeferDueIso,
    nextSaturdayNineLocal,
    startOfNextIsoWeekMondayMidnight,
    tomorrowDeferLocal,
} from './deferPresets.js';

describe('startOfNextIsoWeekMondayMidnight', () => {
    it('from Wednesday returns following Monday 00:00 local', () => {
        const now = new Date(2026, 2, 18, 15, 30, 0);
        const got = startOfNextIsoWeekMondayMidnight(now);
        expect(got.getFullYear()).toBe(2026);
        expect(got.getMonth()).toBe(2);
        expect(got.getDate()).toBe(23);
        expect(got.getHours()).toBe(0);
        expect(got.getMinutes()).toBe(0);
    });

    it('from Monday returns Monday +7 days at 00:00', () => {
        const now = new Date(2026, 2, 16, 12, 0, 0);
        const got = startOfNextIsoWeekMondayMidnight(now);
        expect(got.getDay()).toBe(1);
        expect(got.getDate()).toBe(23);
        expect(got.getHours()).toBe(0);
    });
});

describe('nextSaturdayNineLocal', () => {
    it('Saturday 08:00 returns same day 09:00', () => {
        const now = new Date(2026, 2, 21, 8, 0, 0);
        const got = nextSaturdayNineLocal(now);
        expect(got.getDay()).toBe(6);
        expect(got.getDate()).toBe(21);
        expect(got.getHours()).toBe(9);
    });

    it('Saturday 10:00 returns next Saturday 09:00', () => {
        const now = new Date(2026, 2, 21, 10, 0, 0);
        const got = nextSaturdayNineLocal(now);
        expect(got.getDay()).toBe(6);
        expect(got.getDate()).toBe(28);
        expect(got.getHours()).toBe(9);
    });

    it('Tuesday returns upcoming Saturday 09:00', () => {
        const now = new Date(2026, 2, 17, 12, 0, 0);
        const got = nextSaturdayNineLocal(now);
        expect(got.getDay()).toBe(6);
        expect(got.getDate()).toBe(21);
    });
});

describe('tomorrowDeferLocal', () => {
    it('without previous due uses 09:00 tomorrow', () => {
        const now = new Date(2026, 2, 18, 14, 0, 0);
        const got = tomorrowDeferLocal(now, null);
        expect(got.getMonth()).toBe(2);
        expect(got.getDate()).toBe(19);
        expect(got.getHours()).toBe(9);
    });

    it('with previous due preserves local time on tomorrow', () => {
        const now = new Date(2026, 2, 18, 14, 0, 0);
        const prev = new Date(2026, 2, 10, 14, 30, 45).toISOString();
        const got = tomorrowDeferLocal(now, prev);
        expect(got.getDate()).toBe(19);
        expect(got.getHours()).toBe(14);
        expect(got.getMinutes()).toBe(30);
    });
});

describe('computeDeferDueIso', () => {
    it('returns valid ISO strings', () => {
        const now = new Date(2026, 2, 18, 12, 0, 0);
        for (const p of ['tomorrow', 'nextWeek', 'weekend']) {
            const iso = computeDeferDueIso(p, now, null);
            expect(new Date(iso).getTime()).not.toBeNaN();
        }
    });
});
