<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useI18n } from 'vue-i18n';

defineProps({
    title: { type: String, required: true },
    due: { type: String, required: true },
    recurrence: { type: String, required: true },
    priority: { type: String, required: true },
    notes: { type: String, required: true },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits([
    'update:title',
    'update:due',
    'update:recurrence',
    'update:priority',
    'update:notes',
    'save',
    'close',
    'delete',
    'notes-paste',
]);

const { t } = useI18n();
</script>

<template>
    <div class="space-y-3">
        <div>
            <InputLabel
                for="detail-edit-title"
                :value="t('tasks.titleField')"
            />
            <TextInput
                id="detail-edit-title"
                :model-value="title"
                type="text"
                class="mt-1 block w-full"
                @update:model-value="emit('update:title', $event)"
            />
        </div>
        <div>
            <InputLabel
                for="detail-edit-due"
                :value="t('tasks.dueOptional')"
            />
            <TextInput
                id="detail-edit-due"
                :model-value="due"
                type="datetime-local"
                class="mt-1 block w-full"
                @update:model-value="emit('update:due', $event)"
            />
        </div>
        <div>
            <InputLabel
                for="detail-edit-recurrence"
                :value="t('tasks.recurrenceOptional')"
            />
            <TextInput
                id="detail-edit-recurrence"
                :model-value="recurrence"
                type="text"
                class="mt-1 block w-full"
                :placeholder="t('tasks.recurrencePlaceholder')"
                @update:model-value="emit('update:recurrence', $event)"
            />
        </div>
        <div>
            <InputLabel
                for="detail-edit-priority"
                :value="t('tasks.priorityLabel')"
            />
            <select
                id="detail-edit-priority"
                :value="priority"
                class="mt-1 block w-full rounded-md border border-gt-border-strong bg-gt-field text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring"
                @change="emit('update:priority', $event.target.value)"
            >
                <option value="p1">
                    {{ t('tasks.priorityP1') }}
                </option>
                <option value="p2">
                    {{ t('tasks.priorityP2') }}
                </option>
                <option value="p3">
                    {{ t('tasks.priorityP3') }}
                </option>
                <option value="p4">
                    {{ t('tasks.priorityP4') }}
                </option>
            </select>
        </div>
        <div>
            <InputLabel
                for="detail-edit-notes"
                :value="t('tasks.notesOptional')"
            />
            <textarea
                id="detail-edit-notes"
                :value="notes"
                rows="4"
                class="mt-1 block w-full rounded-md border border-gt-border-strong bg-gt-field text-sm text-gt-ink shadow-sm focus:border-gt-accent focus:ring-gt-accent-ring"
                :placeholder="t('tasks.notesPlaceholder')"
                @input="emit('update:notes', $event.target.value)"
                @paste="emit('notes-paste', $event)"
            />
        </div>
        <div class="flex flex-wrap gap-2 pt-1">
            <PrimaryButton
                type="button"
                :disabled="saving"
                @click="emit('save')"
            >
                {{ t('tasks.saveChanges') }}
            </PrimaryButton>
            <SecondaryButton
                type="button"
                :disabled="saving"
                @click="emit('close')"
            >
                {{ t('tasks.closeInspector') }}
            </SecondaryButton>
            <button
                type="button"
                class="ms-auto inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-25"
                :disabled="saving"
                @click="emit('delete')"
            >
                {{ t('tasks.delete') }}
            </button>
        </div>
    </div>
</template>
