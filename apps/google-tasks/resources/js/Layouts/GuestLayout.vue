<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LocaleSwitcher from '@/Components/LocaleSwitcher.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gt-canvas pt-6 sm:justify-center sm:pt-0"
    >
        <div class="flex w-full max-w-md justify-end px-6 sm:px-0">
            <LocaleSwitcher />
        </div>
        <div>
            <Link href="/">
                <ApplicationLogo
                    class="h-20 w-20 fill-current text-gt-muted"
                />
            </Link>
        </div>

        <div
            class="gt-surface mt-6 w-full overflow-hidden px-6 py-4 sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
