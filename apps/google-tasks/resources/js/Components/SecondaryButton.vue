<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-md border border-gt-border bg-gt-raised px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gt-ink-secondary shadow-sm transition duration-150 ease-in-out hover:bg-gt-field-muted focus:outline-none focus:ring-2 focus:ring-gt-accent-ring focus:ring-offset-2 focus:ring-offset-gt-raised disabled:opacity-25 dark:bg-gt-field-muted dark:text-gt-ink dark:hover:bg-gt-field dark:focus:ring-offset-gt-raised"
    >
        <slot />
    </button>
</template>
