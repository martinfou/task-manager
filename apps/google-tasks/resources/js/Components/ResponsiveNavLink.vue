<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full border-l-4 border-gt-accent bg-gt-accent-tint/40 py-2 pe-4 ps-3 text-start text-base font-medium text-gt-accent transition duration-150 ease-in-out focus:bg-gt-accent-tint/60 focus:outline-none dark:bg-gt-accent-tint/20 dark:text-gt-accent-hover dark:focus:bg-gt-accent-tint/30'
        : 'block w-full border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-gt-muted transition duration-150 ease-in-out hover:border-gt-border hover:bg-gt-field-muted hover:text-gt-ink focus:bg-gt-field-muted focus:text-gt-ink focus:outline-none',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
