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
        ? 'inline-flex items-center border-b-2 border-gt-accent px-1 pt-1 text-sm font-medium leading-5 text-gt-ink transition duration-150 ease-in-out focus:border-gt-accent-hover focus:outline-none dark:text-gt-ink'
        : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium leading-5 text-gt-muted transition duration-150 ease-in-out hover:border-gt-border hover:text-gt-ink focus:border-gt-border focus:text-gt-ink focus:outline-none',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
