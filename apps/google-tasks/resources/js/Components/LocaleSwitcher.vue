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
        class="flex items-center gap-1 rounded-md border border-gt-border p-0.5"
        role="group"
        :aria-label="t('locale.label')"
    >
        <button
            type="button"
            class="rounded px-2 py-1 text-xs font-medium"
            :class="
                locale === 'en'
                    ? 'bg-gt-accent-tint/50 text-gt-accent dark:bg-gt-accent-tint/25 dark:text-gt-accent-hover'
                    : 'text-gt-muted'
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
                    ? 'bg-gt-accent-tint/50 text-gt-accent dark:bg-gt-accent-tint/25 dark:text-gt-accent-hover'
                    : 'text-gt-muted'
            "
            @click="setLocale('fr')"
        >
            FR
        </button>
    </div>
</template>
