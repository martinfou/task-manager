<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LocaleSwitcher from '@/Components/LocaleSwitcher.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 dark:bg-slate-950 sm:justify-center sm:pt-0"
    >
        <div class="flex w-full max-w-md justify-end px-6 sm:px-0">
            <LocaleSwitcher />
        </div>
        <div>
            <Link href="/">
                <ApplicationLogo
                    class="h-20 w-20 fill-current text-gray-500 dark:text-slate-400"
                />
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md dark:bg-slate-900 dark:shadow-slate-900/50 sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
