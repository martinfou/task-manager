<script setup>
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { locale, t } = useI18n();

function setLocale(code) {
    if (locale.value === code) {
        return;
    }
    router.post(
        route('locale.update'),
        { locale: code },
        { preserveScroll: true },
    );
}
</script>

<template>
    <div
        class="flex items-center gap-1 rounded-md border border-gray-200 p-0.5 dark:border-slate-600"
        role="group"
        :aria-label="t('locale.label')"
    >
        <button
            type="button"
            class="rounded px-2 py-1 text-xs font-medium"
            :class="
                locale === 'en'
                    ? 'bg-indigo-100 text-indigo-900 dark:bg-indigo-900/50 dark:text-indigo-100'
                    : 'text-gray-600 dark:text-slate-400'
            "
            @click="setLocale('en')"
        >
            EN
        </button>
        <button
            type="button"
            class="rounded px-2 py-1 text-xs font-medium"
            :class="
                locale === 'fr'
                    ? 'bg-indigo-100 text-indigo-900 dark:bg-indigo-900/50 dark:text-indigo-100'
                    : 'text-gray-600 dark:text-slate-400'
            "
            @click="setLocale('fr')"
        >
            FR
        </button>
    </div>
</template>
