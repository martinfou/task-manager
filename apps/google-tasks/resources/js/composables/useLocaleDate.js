import { useI18n } from 'vue-i18n';

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

    return { formatDateTime };
}
