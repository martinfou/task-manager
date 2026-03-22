import { describe, expect, it } from 'vitest';
import { isPriorityExplicit } from './taskPriorityMeta';

describe('isPriorityExplicit', () => {
    it('returns true when titleRaw has [P1]–[P4] prefix', () => {
        expect(isPriorityExplicit({ titleRaw: '[P1] Buy milk' })).toBe(true);
        expect(isPriorityExplicit({ titleRaw: '[p4] Done' })).toBe(true);
        expect(isPriorityExplicit({ titleRaw: '[  P2  ] Title' })).toBe(true);
    });

    it('returns false without prefix or titleRaw', () => {
        expect(isPriorityExplicit({ titleRaw: 'No bracket title' })).toBe(
            false,
        );
        expect(isPriorityExplicit({})).toBe(false);
        expect(isPriorityExplicit(null)).toBe(false);
    });

    it('returns false for malformed bracket', () => {
        expect(isPriorityExplicit({ titleRaw: '[P5] Invalid' })).toBe(false);
        expect(isPriorityExplicit({ titleRaw: '[PX] Nope' })).toBe(false);
    });
});
