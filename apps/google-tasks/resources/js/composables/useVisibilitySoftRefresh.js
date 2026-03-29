import { onMounted, onUnmounted } from 'vue';

const DEFAULT_MIN_INTERVAL_MS = 60_000;

/**
 * When the document becomes visible again, run onRefresh at most once per minIntervalMs.
 * Skips if shouldRun() is false or a run is already in flight.
 *
 * @param {() => boolean} shouldRun
 * @param {() => void | Promise<void>} onRefresh
 * @param {{ minIntervalMs?: number }} [options]
 */
export function useVisibilitySoftRefresh(shouldRun, onRefresh, options = {}) {
    const minIntervalMs = options.minIntervalMs ?? DEFAULT_MIN_INTERVAL_MS;
    let lastRunAt = 0;
    let inFlight = false;

    async function handleVisibility() {
        if (document.visibilityState !== 'visible') {
            return;
        }
        if (!shouldRun()) {
            return;
        }
        if (inFlight) {
            return;
        }
        const now = Date.now();
        if (now - lastRunAt < minIntervalMs) {
            return;
        }
        lastRunAt = now;
        inFlight = true;
        try {
            await onRefresh();
        } finally {
            inFlight = false;
        }
    }

    onMounted(() => {
        document.addEventListener('visibilitychange', handleVisibility);
    });

    onUnmounted(() => {
        document.removeEventListener('visibilitychange', handleVisibility);
    });
}
