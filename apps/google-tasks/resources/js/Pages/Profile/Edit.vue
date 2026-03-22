<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import DisconnectGoogleForm from './Partials/DisconnectGoogleForm.vue';
import SyncSettingsInfo from './Partials/SyncSettingsInfo.vue';
import UndoToastDelayForm from './Partials/UndoToastDelayForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    hasGoogleTasksConnection: {
        type: Boolean,
        default: false,
    },
    googleTasksPollIntervalMs: {
        type: Number,
        default: 5000,
    },
    googleTasksMaxBackoffMs: {
        type: Number,
        default: 120000,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gt-ink"
            >
                Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div v-if="status === 'google-disconnected'">
                    <p
                        class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:border-emerald-900/50 dark:bg-emerald-950/50 dark:text-emerald-100"
                        role="status"
                    >
                        {{ t('profile.disconnectGoogleSuccess') }}
                    </p>
                </div>

                <div
                    class="gt-surface p-4 sm:rounded-lg sm:p-8"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div
                    class="gt-surface p-4 sm:rounded-lg sm:p-8"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div
                    v-if="hasGoogleTasksConnection"
                    class="gt-surface p-4 sm:rounded-lg sm:p-8"
                >
                    <DisconnectGoogleForm class="max-w-xl" />
                </div>

                <div
                    v-if="hasGoogleTasksConnection"
                    class="gt-surface p-4 sm:rounded-lg sm:p-8"
                >
                    <SyncSettingsInfo
                        class="max-w-xl"
                        :poll-interval-ms="googleTasksPollIntervalMs"
                        :max-backoff-ms="googleTasksMaxBackoffMs"
                    />
                </div>

                <div
                    class="gt-surface p-4 sm:rounded-lg sm:p-8"
                >
                    <UndoToastDelayForm class="max-w-xl" />
                </div>

                <div
                    class="gt-surface p-4 sm:rounded-lg sm:p-8"
                >
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
