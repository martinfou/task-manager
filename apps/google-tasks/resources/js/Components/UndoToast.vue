<script setup>
import { useI18n } from 'vue-i18n';

defineProps({
    message: { type: String, required: true },
});

const emit = defineEmits(['undo']);

const { t } = useI18n();
</script>

<template>
    <div
        class="pointer-events-auto flex max-w-lg items-center justify-between gap-4 rounded-lg border border-gt-border bg-gt-raised px-4 py-3 text-sm text-gt-ink shadow-lg dark:shadow-black/40"
        role="status"
        aria-live="polite"
    >
        <span class="min-w-0 flex-1">{{ message }}</span>
        <button
            type="button"
            class="shrink-0 rounded-md bg-gt-accent px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-white hover:bg-gt-accent-hover focus:outline-none focus:ring-2 focus:ring-gt-accent-ring focus:ring-offset-2 focus:ring-offset-gt-raised"
            @click="emit('undo')"
        >
            {{ t('tasks.undo.button') }}
        </button>
    </div>
</template>
