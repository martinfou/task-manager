import { useI18n } from 'vue-i18n';
import { isDueDateOnly } from '@/utils/taskFilters';

/**
 * Format ISO / RFC3339 timestamps for display using the active vue-i18n locale.
 */
export function useLocaleDate() {
    const { locale } = useI18n();

    function formatDateTime(raw) {
        if (!raw) {
            return '';
        }
        const d = new Date(raw);
        if (Number.isNaN(d.getTime())) {
            return String(raw);
        }

        return new Intl.DateTimeFormat(locale.value || 'en', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(d);
    }

    /**
     * US-038: Format a task due date for display.
     *
     * Date-only values (Google convention: T00:00:00.000Z) show date only
     * (e.g. "Mar 22, 2026") — no misleading time. Values with a real time
     * show date + local time.
     *
     * @param {string | undefined | null} raw — RFC3339 due value from Google Tasks API
     * @returns {string}
     */
    function formatDueDate(raw) {
        if (!raw) {
            return '';
        }

        if (isDueDateOnly(raw)) {
            // Parse the YYYY-MM-DD portion and format as date-only in local noon.
            const m = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (m) {
                const local = new Date(+m[1], +m[2] - 1, +m[3], 12, 0, 0);
                return new Intl.DateTimeFormat(locale.value || 'en', {
                    dateStyle: 'medium',
                }).format(local);
            }
        }

        const d = new Date(raw);
        if (Number.isNaN(d.getTime())) {
            return String(raw);
        }
        return new Intl.DateTimeFormat(locale.value || 'en', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(d);
    }

    return { formatDateTime, formatDueDate };
}
