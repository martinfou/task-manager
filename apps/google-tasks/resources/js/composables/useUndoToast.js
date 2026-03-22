import { usePage } from '@inertiajs/vue3';
import { computed, onUnmounted, ref } from 'vue';

const FALLBACK_MS = 5000;

/**
 * Non-blocking undo toast (US-029). Supports:
 * - Simple: timer only dismisses the message; onUndo runs when the user clicks Undo.
 * - Deferred commit: onCommit runs when the timer fires unless the user undoes first.
 *
 * Default duration comes from Profile → Tasks preferences (shared Inertia prop
 * `tasks.undoToastDelayMs`), falling back to {@link FALLBACK_MS}.
 */
export function useUndoToast() {
    const page = usePage();
    const resolvedDelayMs = computed(() => {
        const v = page.props.tasks?.undoToastDelayMs;
        return typeof v === 'number' && v > 0 ? v : FALLBACK_MS;
    });

    const toast = ref(null);
    /** When true, background task polling should skip fetches (deferred delete not yet committed). */
    const holdPolling = ref(false);
    let timer = null;
    /** @type {null | (() => void | Promise<void>)} */
    let pendingCommit = null;

    function clearTimer() {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
    }

    async function flushPendingCommit() {
        const fn = pendingCommit;
        pendingCommit = null;
        clearTimer();
        if (fn) {
            try {
                await fn();
            } catch {
                // Caller’s onCommit should set loadError; never block the next toast
            }
        }
    }

    async function show(opts) {
        await flushPendingCommit();
        holdPolling.value = false;
        toast.value = null;

        const { message, onUndo, onCommit, delayMs } = opts;
        const ms =
            typeof delayMs === 'number' && delayMs > 0
                ? delayMs
                : resolvedDelayMs.value;

        pendingCommit = onCommit ?? null;
        if (pendingCommit) {
            holdPolling.value = true;
        }

        timer = setTimeout(async () => {
            timer = null;
            toast.value = null;
            if (pendingCommit) {
                const fn = pendingCommit;
                pendingCommit = null;
                try {
                    await fn();
                } catch {
                    /* same as flush */
                } finally {
                    holdPolling.value = false;
                }
            } else {
                holdPolling.value = false;
            }
        }, ms);

        toast.value = { message, onUndo };
    }

    async function undo() {
        holdPolling.value = false;
        const t = toast.value;
        const fn = t?.onUndo;
        clearTimer();
        pendingCommit = null;
        toast.value = null;
        if (fn) {
            await fn();
        }
    }

    function dismiss() {
        clearTimer();
        pendingCommit = null;
        toast.value = null;
        holdPolling.value = false;
    }

    onUnmounted(() => {
        clearTimer();
        pendingCommit = null;
        toast.value = null;
        holdPolling.value = false;
    });

    return {
        toast,
        show,
        undo,
        dismiss,
        flushPendingCommit,
        holdPolling,
    };
}
