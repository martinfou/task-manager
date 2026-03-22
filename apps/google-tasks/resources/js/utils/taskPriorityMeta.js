/** Matches Google title prefix after TaskPriorityCodec (e.g. "[P1] Title"). */
const PRIORITY_BRACKET_PREFIX = /^\[\s*P[1-4]\s*\]/i;

/**
 * Whether the task title in Google included an explicit [P1]–[P4] prefix.
 * Uses `titleRaw` from the API; missing `titleRaw` is treated as implicit (default p3 for sort).
 *
 * @param {{ titleRaw?: string } | null | undefined} task
 * @returns {boolean}
 */
export function isPriorityExplicit(task) {
    const raw = task?.titleRaw;
    if (typeof raw !== 'string') {
        return false;
    }
    return PRIORITY_BRACKET_PREFIX.test(raw.trim());
}
