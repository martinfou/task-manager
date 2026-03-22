<script setup>
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useI18n } from 'vue-i18n';

defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
    showResetFilters: { type: Boolean, default: false },
    /** Use `div` inside board layout; default `li` for list `<ul>` rows. */
    tag: { type: String, default: 'li' },
});

const emit = defineEmits(['reset-filters']);
const { t } = useI18n();
</script>

<template>
    <component
        :is="tag"
        :class="tag === 'li' ? 'list-none' : undefined"
    >
        <div
            class="density-card-padding mx-auto max-w-lg text-center"
            role="status"
        >
            <div
                class="mb-4 flex justify-center text-gt-accent/70 dark:text-gt-accent/80"
                aria-hidden="true"
            >
                <svg
                    class="h-11 w-11"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                    />
                </svg>
            </div>
            <p
                class="font-display text-base font-semibold tracking-tight text-gt-ink"
            >
                {{ title }}
            </p>
            <p class="mt-2 text-sm leading-relaxed text-gt-muted">
                {{ description }}
            </p>
            <SecondaryButton
                v-if="showResetFilters"
                type="button"
                class="mt-5"
                @click="emit('reset-filters')"
            >
                {{ t('tasks.resetFilters') }}
            </SecondaryButton>
        </div>
    </component>
</template>
