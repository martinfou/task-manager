<script setup>
import { useI18n } from 'vue-i18n';

defineProps({
    /** When true, omit the outer summary-style title (e.g. inside a modal that already has a heading). */
    hideTitle: { type: Boolean, default: false },
});

const { t } = useI18n();
</script>

<template>
    <div class="text-sm text-gt-ink-secondary">
        <p
            v-if="!hideTitle"
            class="mb-2 text-xs font-medium uppercase tracking-wide text-gt-subtle"
        >
            {{ t('tasks.helpTitle') }}
        </p>
        <ul class="list-disc space-y-2 ps-5">
            <li>
                {{
                    t('tasks.helpBullet1', {
                        tz: Intl.DateTimeFormat().resolvedOptions().timeZone,
                    })
                }}
            </li>
            <li>
                {{ t('tasks.helpBullet2') }}
            </li>
            <li>
                {{ t('tasks.helpBullet3') }}
            </li>
            <li>
                {{ t('tasks.helpBulletAll') }}
            </li>
            <li>
                {{ t('tasks.helpBulletBulk') }}
            </li>
            <li>
                {{ t('tasks.helpBulletFilters') }}
            </li>
        </ul>
    </div>
</template>
