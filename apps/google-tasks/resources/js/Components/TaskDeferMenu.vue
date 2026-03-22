<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const emit = defineEmits(['pick']);

defineProps({
    /** When true, summary uses slightly smaller text (kanban cards). */
    compact: { type: Boolean, default: false },
    /** Larger tap target for list rows (min height). */
    touchComfortable: { type: Boolean, default: false },
    /** Match disabled row actions (e.g. optimistic tasks). */
    disabled: { type: Boolean, default: false },
});

const { t } = useI18n();
const root = ref(null);

function choose(preset) {
    emit('pick', preset);
    root.value?.removeAttribute('open');
}
</script>

<template>
    <details
        ref="root"
        class="relative"
        :class="
            disabled
                ? 'pointer-events-none opacity-40'
                : ''
        "
        @click.stop
    >
        <summary
            class="cursor-pointer list-none touch-manipulation rounded-md px-2 py-1 text-gt-accent hover:bg-gt-accent-tint/25 focus:outline-none focus:ring-2 focus:ring-gt-accent-ring [&::-webkit-details-marker]:hidden"
            :class="[
                compact
                    ? 'text-[10px] font-medium'
                    : 'text-xs font-medium sm:text-sm',
                touchComfortable
                    ? 'inline-flex min-h-11 items-center'
                    : '',
            ]"
            :title="t('tasks.defer.summaryTooltip')"
        >
            {{ t('tasks.defer.menuSummary') }}
        </summary>
        <div
            class="absolute end-0 z-30 mt-1 min-w-[12rem] rounded-md border border-gt-border bg-gt-raised py-1 text-start shadow-lg dark:shadow-black/40"
            role="menu"
            :aria-label="t('tasks.defer.menuAria')"
        >
            <button
                type="button"
                role="menuitem"
                class="block w-full px-3 py-2 text-start text-sm text-gt-ink hover:bg-gt-field-muted focus:bg-gt-field-muted focus:outline-none"
                @click="choose('tomorrow')"
            >
                {{ t('tasks.defer.tomorrow') }}
            </button>
            <button
                type="button"
                role="menuitem"
                class="block w-full px-3 py-2 text-start text-sm text-gt-ink hover:bg-gt-field-muted focus:bg-gt-field-muted focus:outline-none"
                @click="choose('nextWeek')"
            >
                {{ t('tasks.defer.nextWeek') }}
            </button>
            <button
                type="button"
                role="menuitem"
                class="block w-full px-3 py-2 text-start text-sm text-gt-ink hover:bg-gt-field-muted focus:bg-gt-field-muted focus:outline-none"
                @click="choose('weekend')"
            >
                {{ t('tasks.defer.weekend') }}
            </button>
            <div
                class="my-1 border-t border-gt-border"
                role="presentation"
            />
            <button
                type="button"
                role="menuitem"
                class="block w-full px-3 py-2 text-start text-sm text-gt-ink hover:bg-gt-field-muted focus:bg-gt-field-muted focus:outline-none"
                @click="choose('pickDate')"
            >
                {{ t('tasks.defer.pickDate') }}
            </button>
        </div>
    </details>
</template>
