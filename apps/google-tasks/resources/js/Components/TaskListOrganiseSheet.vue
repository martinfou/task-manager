<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import draggable from 'vuedraggable';

const { t } = useI18n();

const props = defineProps({
    show: { type: Boolean, default: false },
    lists: { type: Array, default: () => [] },
    autoSort: { type: String, default: null },
});

const emit = defineEmits(['close', 'save']);

// Local mutable copies for drag reordering
const localPinned = ref([]);
const localUnpinned = ref([]);
const localAutoSort = ref(props.autoSort);

watch(
    () => props.show,
    (open) => {
        if (open) {
            localPinned.value = props.lists
                .filter((l) => l.pinned)
                .map((l) => ({ ...l }));
            localUnpinned.value = props.lists
                .filter((l) => !l.pinned)
                .map((l) => ({ ...l }));
            localAutoSort.value = props.autoSort;
        }
    },
);

const dragDisabled = computed(
    () =>
        localAutoSort.value === 'alpha_asc' ||
        localAutoSort.value === 'alpha_desc',
);

function toggleLocalPin(list, section) {
    if (section === 'pinned') {
        // Move from pinned to unpinned
        localPinned.value = localPinned.value.filter(
            (l) => l.id !== list.id,
        );
        localUnpinned.value.push({ ...list, pinned: false });
    } else {
        // Move from unpinned to pinned
        localUnpinned.value = localUnpinned.value.filter(
            (l) => l.id !== list.id,
        );
        localPinned.value.push({ ...list, pinned: true });
    }
}

function done() {
    const pinned = localPinned.value.map((l) => ({ ...l, pinned: true }));
    const unpinned = localUnpinned.value.map((l) => ({
        ...l,
        pinned: false,
    }));
    emit('save', [...pinned, ...unpinned], localAutoSort.value);
    emit('close');
}
</script>

<template>
    <Modal :show="show" max-width="md" @close="$emit('close')">
        <div class="p-6">
            <h3
                class="text-lg font-semibold text-gt-ink"
            >
                {{ t('tasks.organiseLists') }}
            </h3>

            <!-- Auto-sort toggle -->
            <div class="mt-4 flex items-center gap-2 text-sm">
                <span class="text-gt-ink-secondary">{{
                    t('tasks.autoSortLabel')
                }}</span>
                <select
                    v-model="localAutoSort"
                    class="rounded-md border border-gt-border bg-gt-field px-2 py-1 text-sm text-gt-ink"
                >
                    <option :value="null">
                        {{ t('tasks.autoSortManual') }}
                    </option>
                    <option value="alpha_asc">
                        {{ t('tasks.autoSortAlphaAsc') }}
                    </option>
                    <option value="alpha_desc">
                        {{ t('tasks.autoSortAlphaDesc') }}
                    </option>
                </select>
            </div>

            <!-- Pinned section -->
            <div
                v-if="localPinned.length > 0"
                class="mt-4"
            >
                <div
                    class="mb-1 text-xs font-semibold uppercase tracking-wide text-gt-subtle"
                >
                    {{ t('tasks.pinnedLists') }}
                </div>
                <draggable
                    v-model="localPinned"
                    item-key="id"
                    handle=".drag-handle"
                    class="space-y-1"
                >
                    <template #item="{ element }">
                        <div
                            class="flex items-center gap-2 rounded-md border border-gt-border bg-gt-raised px-3 py-2"
                        >
                            <span
                                class="drag-handle cursor-grab text-gt-muted active:cursor-grabbing"
                                >&#x2630;</span
                            >
                            <span
                                class="flex-1 truncate text-sm text-gt-ink"
                                >{{ element.title }}</span
                            >
                            <button
                                type="button"
                                class="shrink-0 text-xs text-gt-accent hover:text-gt-accent-hover"
                                @click="toggleLocalPin(element, 'pinned')"
                            >
                                {{ t('tasks.unpin') }}
                            </button>
                        </div>
                    </template>
                </draggable>
            </div>

            <!-- Divider -->
            <div
                v-if="localPinned.length > 0 && localUnpinned.length > 0"
                class="my-3 border-t border-gt-border"
            />

            <!-- Unpinned section -->
            <div v-if="localUnpinned.length > 0" class="mt-2">
                <div
                    class="mb-1 text-xs font-semibold uppercase tracking-wide text-gt-subtle"
                >
                    {{ t('tasks.otherLists') }}
                </div>
                <draggable
                    v-model="localUnpinned"
                    item-key="id"
                    handle=".drag-handle"
                    :disabled="dragDisabled"
                    class="space-y-1"
                >
                    <template #item="{ element }">
                        <div
                            class="flex items-center gap-2 rounded-md border border-gt-border bg-gt-raised px-3 py-2"
                        >
                            <span
                                v-if="!dragDisabled"
                                class="drag-handle cursor-grab text-gt-muted active:cursor-grabbing"
                                >&#x2630;</span
                            >
                            <span
                                class="flex-1 truncate text-sm text-gt-ink"
                                >{{ element.title }}</span
                            >
                            <button
                                type="button"
                                class="shrink-0 text-xs text-gt-accent hover:text-gt-accent-hover"
                                @click="
                                    toggleLocalPin(element, 'unpinned')
                                "
                            >
                                {{ t('tasks.pinToTop') }}
                            </button>
                        </div>
                    </template>
                </draggable>
            </div>

            <!-- Done button -->
            <div class="mt-6 flex justify-end">
                <PrimaryButton @click="done">
                    {{ t('tasks.organiseDone') }}
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
