import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import fr from './locales/fr.json';

const supported = new Set(['en', 'fr']);

export function createVueI18n(initialLocale) {
    const locale = supported.has(initialLocale) ? initialLocale : 'en';

    return createI18n({
        legacy: false,
        locale,
        fallbackLocale: 'en',
        messages: { en, fr },
    });
}
