import { describe, expect, it, vi } from 'vitest';
import {
    isRetryableReadError,
    messageFromAxiosError,
} from './googleTaskError.js';

describe('messageFromAxiosError', () => {
    const t = vi.fn((key) => `t:${key}`);
    const te = vi.fn(() => false);

    it('uses Error.message when no axios response', () => {
        expect(messageFromAxiosError(new Error('net'), t, te)).toBe('net');
    });

    it('uses i18n fallback when value is not an Error with message', () => {
        expect(messageFromAxiosError({}, t, te)).toBe(
            't:tasks.errors.loadFailed',
        );
    });

    it('maps known code via i18n when te returns true', () => {
        te.mockImplementation((key) => key === 'tasks.errors.codes.auth_expired');
        expect(
            messageFromAxiosError(
                {
                    response: {
                        data: { code: 'auth_expired', message: 'raw' },
                    },
                },
                t,
                te,
            ),
        ).toBe('t:tasks.errors.codes.auth_expired');
    });

    it('prefers response message when no code key', () => {
        expect(
            messageFromAxiosError(
                { response: { data: { message: 'Server said' } } },
                t,
                te,
            ),
        ).toBe('Server said');
    });
});

describe('isRetryableReadError', () => {
    it('returns false for auth_expired', () => {
        expect(
            isRetryableReadError({
                response: { status: 401, data: { code: 'auth_expired' } },
            }),
        ).toBe(false);
    });

    it('returns true for 503', () => {
        expect(isRetryableReadError({ response: { status: 503 } })).toBe(true);
    });

    it('returns false for 403 (numeric)', () => {
        expect(isRetryableReadError({ response: { status: 403 } })).toBe(false);
    });

    it('returns false for 403 (string status, axios edge case)', () => {
        expect(isRetryableReadError({ response: { status: '403' } })).toBe(
            false,
        );
    });

    it('returns true when no response (network)', () => {
        expect(isRetryableReadError(new Error('fetch failed'))).toBe(true);
    });
});
