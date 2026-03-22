<script setup>
import { useAppearance } from '@/composables/useAppearance';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { theme, density, toggleTheme, setDensity } = useAppearance();
</script>

<template>
    <div
        class="flex flex-wrap items-center gap-2 sm:gap-3"
        role="group"
        :aria-label="t('appearance.display')"
    >
        <button
            type="button"
            class="rounded-md border border-gt-border bg-gt-raised p-2 text-gt-muted shadow-sm hover:bg-gt-field-muted dark:bg-gt-field-muted dark:hover:bg-gt-field"
            :title="theme === 'dark' ? t('appearance.switchToLight') : t('appearance.switchToDark')"
            :aria-label="theme === 'dark' ? t('appearance.switchToLight') : t('appearance.switchToDark')"
            @click="toggleTheme"
        >
            <svg
                v-if="theme === 'dark'"
                class="h-5 w-5"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"
                />
            </svg>
            <svg
                v-else
                class="h-5 w-5"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"
                />
            </svg>
        </button>
        <div
            class="flex rounded-md border border-gt-border p-0.5"
            role="radiogroup"
            :aria-label="t('appearance.density')"
        >
            <button
                type="button"
                class="rounded px-2 py-1 text-xs font-medium"
                :class="
                    density === 'comfortable'
                        ? 'bg-gt-accent-tint/50 text-gt-accent dark:bg-gt-accent-tint/25 dark:text-gt-accent-hover'
                        : 'text-gt-muted'
                "
                @click="setDensity('comfortable')"
            >
                Comfort
            </button>
            <button
                type="button"
                class="rounded px-2 py-1 text-xs font-medium"
                :class="
                    density === 'compact'
                        ? 'bg-gt-accent-tint/50 text-gt-accent dark:bg-gt-accent-tint/25 dark:text-gt-accent-hover'
                        : 'text-gt-muted'
                "
                @click="setDensity('compact')"
            >
                {{ t('appearance.compact') }}
            </button>
        </div>
    </div>
</template>
