<script setup>
import { useI18n } from 'vue-i18n';

defineProps({
    pollIntervalMs: {
        type: Number,
        required: true,
    },
    maxBackoffMs: {
        type: Number,
        required: true,
    },
});

const { t } = useI18n();
</script>

<template>
    <section>
        <header class="mb-1">
            <h3 class="text-lg font-medium text-gt-ink">
                {{ t('profile.syncHeading') }}
            </h3>
            <p class="mt-1 text-sm text-gt-muted">
                {{ t('profile.syncIntro') }}
            </p>
        </header>
        <dl class="mt-4 space-y-2 text-sm text-gt-ink-secondary">
            <div class="flex flex-wrap justify-between gap-2">
                <dt class="text-gt-muted">
                    {{ t('profile.syncPollTarget') }}
                </dt>
                <dd class="font-mono text-gt-ink">
                    {{ pollIntervalMs }} ms
                </dd>
            </div>
            <div class="flex flex-wrap justify-between gap-2">
                <dt class="text-gt-muted">
                    {{ t('profile.syncMaxBackoff') }}
                </dt>
                <dd class="font-mono text-gt-ink">
                    {{ maxBackoffMs }} ms
                </dd>
            </div>
        </dl>
        <p class="mt-4 text-xs text-gt-muted">
            {{ t('profile.syncNote429') }}
        </p>
    </section>
</template>
