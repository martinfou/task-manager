/**
 * Maps Laravel JSON error payloads from TasksController (code + message) to UI strings.
 *
 * @param {unknown} e
 * @param {(key: string) => string} t
 * @param {(key: string) => boolean} te
 * @param {string} [fallbackKey] i18n key when no code match
 */
export function messageFromAxiosError(e, t, te, fallbackKey = 'tasks.errors.loadFailed') {
    const code =
        e &&
        typeof e === 'object' &&
        'response' in e &&
        e.response &&
        typeof e.response === 'object' &&
        'data' in e.response &&
        e.response.data &&
        typeof e.response.data === 'object' &&
        'code' in e.response.data
            ? e.response.data.code
            : null;
    if (typeof code === 'string' && code && te(`tasks.errors.codes.${code}`)) {
        return t(`tasks.errors.codes.${code}`);
    }
    const msg =
        e &&
        typeof e === 'object' &&
        'response' in e &&
        e.response &&
        typeof e.response === 'object' &&
        'data' in e.response &&
        e.response.data &&
        typeof e.response.data === 'object' &&
        'message' in e.response.data &&
        typeof e.response.data.message === 'string'
            ? e.response.data.message
            : null;
    if (msg && msg.trim()) {
        return msg;
    }
    if (
        e &&
        typeof e === 'object' &&
        'message' in e &&
        typeof e.message === 'string' &&
        e.message
    ) {
        return e.message;
    }
    return t(fallbackKey);
}

/**
 * @param {unknown} e
 */
export function isRetryableReadError(e) {
    const rawStatus =
        e &&
        typeof e === 'object' &&
        'response' in e &&
        e.response &&
        typeof e.response === 'object'
            ? /** @type {{ status?: unknown }} */ (e.response).status
            : undefined;
    const statusNum =
        rawStatus === undefined || rawStatus === null
            ? NaN
            : Number(rawStatus);
    const status = Number.isFinite(statusNum) ? statusNum : null;
    const code =
        e &&
        typeof e === 'object' &&
        'response' in e &&
        e.response &&
        typeof e.response === 'object' &&
        'data' in e.response &&
        e.response.data &&
        typeof e.response.data === 'object' &&
        'code' in e.response.data
            ? e.response.data.code
            : undefined;
    if (status === 401 || code === 'auth_expired') {
        return false;
    }
    if (status === 403 || code === 'forbidden') {
        return false;
    }
    if (!e || typeof e !== 'object' || !('response' in e) || !e.response) {
        return true;
    }
    if (status === 429) {
        return true;
    }
    if (status !== null && status >= 500 && status < 600) {
        return true;
    }
    if (code === 'network') {
        return true;
    }
    return false;
}

/**
 * Bounded automatic retry for idempotent GET-style loads (lists, views).
 *
 * @param {() => Promise<void>} fn
 */
export async function withReadRetry(fn) {
    const max = 3;
    const delaysMs = [800, 1600];
    let lastErr;
    for (let i = 0; i < max; i++) {
        try {
            if (i > 0) {
                const prev = lastErr;
                let wait = delaysMs[i - 1] ?? 2000;
                if (
                    prev &&
                    typeof prev === 'object' &&
                    'response' in prev &&
                    prev.response &&
                    typeof prev.response === 'object' &&
                    'data' in prev.response &&
                    prev.response.data &&
                    typeof prev.response.data === 'object' &&
                    'retry_after' in prev.response.data
                ) {
                    const sec = prev.response.data.retry_after;
                    if (typeof sec === 'number' && sec > 0) {
                        wait = Math.min(sec * 1000, 30000);
                    }
                }
                await new Promise((r) => setTimeout(r, wait));
            }
            await fn();
            return;
        } catch (e) {
            lastErr = e;
            if (!isRetryableReadError(e) || i === max - 1) {
                throw e;
            }
        }
    }
}
