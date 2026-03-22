<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const page = usePage();

const optionsMs = [3000, 5000, 10000, 15000, 30000];

const currentMs = computed(
    () => page.props.tasks?.undoToastDelayMs ?? 5000,
);

const form = useForm({
    undo_toast_delay_ms: currentMs.value,
});

watch(currentMs, (ms) => {
    form.undo_toast_delay_ms = ms;
});

function labelForMs(ms) {
    const sec = ms / 1000;
    return t('profile.undoToastSeconds', { n: sec });
}
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gt-ink">
                {{ t('profile.undoToastHeading') }}
            </h2>
            <p class="mt-1 text-sm text-gt-muted">
                {{ t('profile.undoToastIntro') }}
            </p>
        </header>

        <form
            class="mt-6 space-y-6"
            @submit.prevent="
                form.patch(route('profile.tasks-preferences.update'), {
                    preserveScroll: true,
                })
            "
        >
            <div>
                <InputLabel
                    for="undo_toast_delay_ms"
                    :value="t('profile.undoToastLabel')"
                />

                <select
                    id="undo_toast_delay_ms"
                    v-model="form.undo_toast_delay_ms"
                    class="mt-1 block w-full rounded-md border-gt-border bg-gt-raised px-3 py-2 text-sm text-gt-ink shadow-sm focus:border-gt-accent focus:outline-none focus:ring-1 focus:ring-gt-accent"
                >
                    <option
                        v-for="ms in optionsMs"
                        :key="ms"
                        :value="ms"
                    >
                        {{ labelForMs(ms) }}
                    </option>
                </select>

                <InputError
                    class="mt-2"
                    :message="form.errors.undo_toast_delay_ms"
                />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton
                    :disabled="form.processing"
                >
                    {{ t('profile.undoToastSave') }}
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-show="form.recentlySuccessful"
                        class="text-sm text-gt-muted"
                    >
                        {{ t('profile.undoToastSaved') }}
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
