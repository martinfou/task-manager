<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

defineProps({
    hasGoogleTasksConnection: { type: Boolean, default: false },
});

const { t } = useI18n();

const primaryLinkClass =
    'inline-flex items-center rounded-md border border-transparent bg-gt-accent px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gt-accent-hover focus:outline-none focus:ring-2 focus:ring-gt-accent-ring focus:ring-offset-2 focus:ring-offset-gt-raised active:opacity-90';

const secondaryLinkClass =
    'inline-flex items-center rounded-md border border-gt-border bg-gt-raised px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gt-ink-secondary shadow-sm transition duration-150 ease-in-out hover:bg-gt-field-muted focus:outline-none focus:ring-2 focus:ring-gt-accent-ring focus:ring-offset-2 focus:ring-offset-gt-raised dark:bg-gt-field-muted dark:text-gt-ink dark:hover:bg-gt-field';
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gt-ink"
            >
                {{ t('dashboard.title') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="gt-surface overflow-hidden sm:rounded-lg">
                    <div
                        class="density-stack density-card-padding text-gt-ink"
                    >
                        <div>
                            <h3
                                class="text-lg font-semibold text-gt-ink"
                            >
                                {{ t('dashboard.welcomeTitle') }}
                            </h3>
                            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-gt-muted">
                                {{
                                    hasGoogleTasksConnection
                                        ? t('dashboard.connectedLead')
                                        : t('dashboard.disconnectedLead')
                                }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <Link
                                :href="route('tasks.index')"
                                :class="primaryLinkClass"
                            >
                                {{ t('dashboard.openTasks') }}
                            </Link>
                            <Link
                                v-if="!hasGoogleTasksConnection"
                                :href="route('google.redirect')"
                                :class="secondaryLinkClass"
                            >
                                {{ t('tasks.connectGoogle') }}
                            </Link>
                            <Link
                                :href="route('profile.edit')"
                                class="text-sm font-medium text-gt-accent underline decoration-gt-accent/40 underline-offset-2 hover:text-gt-accent-hover"
                            >
                                {{ t('dashboard.manageConnection') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
