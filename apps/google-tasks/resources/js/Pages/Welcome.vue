<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
});

const { t } = useI18n();

const features = [
    { key: 'priority', icon: 'flag' },
    { key: 'kanban', icon: 'columns' },
    { key: 'search', icon: 'search' },
    { key: 'dashboard', icon: 'chart' },
    { key: 'mobile', icon: 'mobile' },
];
</script>

<template>
    <Head :title="t('landing.pageTitle')" />

    <div class="min-h-screen bg-gt-canvas text-gt-ink selection:bg-gt-accent-tint selection:text-gt-ink">
        <!-- Nav -->
        <nav class="mx-auto flex max-w-5xl items-center justify-between px-6 py-5">
            <span class="font-display text-lg font-bold tracking-tight text-gt-ink">
                {{ t('landing.brand') }}
            </span>
            <div v-if="canLogin" class="flex items-center gap-3">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('tasks.index')"
                    class="rounded-md px-4 py-2 text-sm font-medium text-gt-accent transition hover:text-gt-accent-hover"
                >
                    {{ t('landing.goToTasks') }}
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="rounded-md px-4 py-2 text-sm font-medium text-gt-ink-secondary transition hover:text-gt-ink"
                    >
                        {{ t('landing.logIn') }}
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="rounded-md bg-gt-accent-strong px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gt-accent-strong-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-gt-accent-ring"
                    >
                        {{ t('landing.signUp') }}
                    </Link>
                </template>
            </div>
        </nav>

        <!-- Hero -->
        <section class="mx-auto max-w-5xl px-6 pb-16 pt-12 sm:pb-24 sm:pt-20 text-center">
            <h1 class="font-display text-4xl font-extrabold leading-tight tracking-tight text-gt-ink sm:text-5xl lg:text-6xl">
                {{ t('landing.heroTitle') }}
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg leading-relaxed text-gt-muted sm:mt-6 sm:text-xl">
                {{ t('landing.heroSubtitle') }}
            </p>
            <div class="mt-8 flex flex-col items-center gap-3 sm:mt-10 sm:flex-row sm:justify-center sm:gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('tasks.index')"
                    class="inline-flex min-h-12 items-center rounded-lg bg-gt-accent-strong px-8 text-base font-semibold text-white shadow-md transition hover:bg-gt-accent-strong-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-gt-accent-ring sm:min-h-14 sm:text-lg"
                >
                    {{ t('landing.ctaLoggedIn') }}
                </Link>
                <template v-else>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="inline-flex min-h-12 items-center rounded-lg bg-gt-accent-strong px-8 text-base font-semibold text-white shadow-md transition hover:bg-gt-accent-strong-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-gt-accent-ring sm:min-h-14 sm:text-lg"
                    >
                        {{ t('landing.ctaSignUp') }}
                    </Link>
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="inline-flex min-h-12 items-center rounded-lg border border-gt-border-strong px-8 text-base font-medium text-gt-ink transition hover:bg-gt-field-muted focus:outline-none focus-visible:ring-2 focus-visible:ring-gt-accent-ring sm:min-h-14 sm:text-lg"
                    >
                        {{ t('landing.ctaLogIn') }}
                    </Link>
                </template>
            </div>
        </section>

        <!-- Features -->
        <section class="border-t border-gt-border bg-gt-raised/50 dark:bg-gt-raised/30">
            <div class="mx-auto max-w-5xl px-6 py-16 sm:py-24">
                <h2 class="text-center font-display text-2xl font-bold tracking-tight text-gt-ink sm:text-3xl">
                    {{ t('landing.featuresTitle') }}
                </h2>
                <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="f in features"
                        :key="f.key"
                        class="rounded-xl bg-gt-raised p-6 shadow-sm ring-1 ring-gt-border"
                    >
                        <!-- Icon -->
                        <div class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-gt-accent-tint text-gt-accent">
                            <!-- Flag -->
                            <svg v-if="f.icon === 'flag'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18M9 3h10l-4 4 4 4H9" /></svg>
                            <!-- Columns -->
                            <svg v-else-if="f.icon === 'columns'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125Z" /></svg>
                            <!-- Search -->
                            <svg v-else-if="f.icon === 'search'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                            <!-- Chart -->
                            <svg v-else-if="f.icon === 'chart'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                            <!-- Mobile -->
                            <svg v-else-if="f.icon === 'mobile'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg>
                        </div>
                        <h3 class="text-base font-semibold text-gt-ink">
                            {{ t(`landing.feature.${f.key}.title`) }}
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-gt-muted">
                            {{ t(`landing.feature.${f.key}.desc`) }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Google Tasks callout -->
        <section class="border-t border-gt-border">
            <div class="mx-auto max-w-3xl px-6 py-16 text-center sm:py-20">
                <p class="text-base leading-relaxed text-gt-muted sm:text-lg">
                    {{ t('landing.googleCallout') }}
                </p>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-gt-border bg-gt-raised/30">
            <div class="mx-auto flex max-w-5xl flex-col items-center gap-3 px-6 py-8 text-sm text-gt-muted sm:flex-row sm:justify-between">
                <span>&copy; {{ new Date().getFullYear() }} {{ t('landing.brand') }}</span>
                <div class="flex gap-4">
                    <Link
                        v-if="canLogin"
                        :href="$page.props.auth.user ? route('tasks.index') : route('login')"
                        class="transition hover:text-gt-ink"
                    >
                        {{ $page.props.auth.user ? t('landing.goToTasks') : t('landing.logIn') }}
                    </Link>
                    <Link
                        v-if="canRegister && !$page.props.auth.user"
                        :href="route('register')"
                        class="transition hover:text-gt-ink"
                    >
                        {{ t('landing.signUp') }}
                    </Link>
                </div>
            </div>
        </footer>
    </div>
</template>
