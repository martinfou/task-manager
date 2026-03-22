<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const confirming = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const page = usePage();

const openConfirm = () => {
    confirming.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const submit = () => {
    form.post(route('profile.google.disconnect'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset('password'),
    });
};

const closeModal = () => {
    confirming.value = false;
    form.clearErrors();
    form.reset('password');
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gt-ink">
                {{ t('profile.disconnectGoogleTitle') }}
            </h2>

            <p class="mt-1 text-sm text-gt-muted">
                {{ t('profile.disconnectGoogleDescription') }}
            </p>
        </header>

        <DangerButton @click="openConfirm">
            {{ t('profile.disconnectGoogleButton') }}
        </DangerButton>

        <InputError :message="page.props.errors.google" class="mt-2" />

        <Modal :show="confirming" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gt-ink">
                    {{ t('profile.disconnectGoogleConfirmTitle') }}
                </h2>

                <p class="mt-1 text-sm text-gt-muted">
                    {{ t('profile.disconnectGoogleConfirmBody') }}
                </p>

                <div class="mt-6">
                    <InputLabel
                        for="disconnect-google-password"
                        :value="t('profile.disconnectGooglePasswordLabel')"
                        class="sr-only"
                    />

                    <TextInput
                        id="disconnect-google-password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-3/4"
                        :placeholder="t('profile.disconnectGooglePasswordPlaceholder')"
                        @keyup.enter="submit"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">
                        {{ t('profile.disconnectGoogleCancel') }}
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        {{ t('profile.disconnectGoogleConfirm') }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
