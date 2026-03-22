<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { computed, nextTick, ref, watch, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';

/**
 * @typedef {{ id: string, sectionKey: string, label: string, keywords?: string }} PaletteItem
 */

const props = defineProps({
    show: { type: Boolean, default: false },
    /** @type {import('vue').PropType<PaletteItem[]>} */
    items: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'select', 'quick-add']);

const { t } = useI18n();

const query = ref('');
const mode = ref('browse');
const quickTitle = ref('');
const highlightedIndex = ref(0);
const filterInputRef = ref(null);
const quickInputRef = ref(null);

/** @type {Element | null} */
let focusBeforeOpen = null;

function sectionLabel(key) {
    const map = {
        actions: 'tasks.commandPalette.sectionActions',
        views: 'tasks.commandPalette.sectionViews',
        lists: 'tasks.commandPalette.sectionLists',
    };
    return t(map[key] || key);
}

function normalize(s) {
    return (s || '').toLowerCase().trim();
}

const selectableItems = computed(() => {
    const q = normalize(query.value);
    if (!q) {
        return props.items;
    }
    return props.items.filter((it) => {
        const hay = normalize(`${it.label} ${it.keywords || ''}`);
        return hay.includes(q);
    });
});

const listRows = computed(() => {
    const rows = [];
    let lastSection = null;
    let flatIdx = 0;
    for (const it of selectableItems.value) {
        if (it.sectionKey !== lastSection) {
            lastSection = it.sectionKey;
            rows.push({
                kind: 'header',
                key: `h-${lastSection}`,
                label: sectionLabel(lastSection),
            });
        }
        rows.push({ kind: 'item', item: it, flatIdx: flatIdx++ });
    }
    return rows;
});

watch(
    () => props.show,
    async (v) => {
        if (v) {
            focusBeforeOpen = document.activeElement;
            query.value = '';
            mode.value = 'browse';
            quickTitle.value = '';
            highlightedIndex.value = 0;
            await nextTick();
            filterInputRef.value?.focus?.();
        } else {
            await nextTick();
            if (
                focusBeforeOpen instanceof HTMLElement &&
                document.contains(focusBeforeOpen)
            ) {
                focusBeforeOpen.focus();
            }
            focusBeforeOpen = null;
        }
    },
);

watch([query, () => props.items], () => {
    highlightedIndex.value = 0;
});

watchEffect((onCleanup) => {
    if (!props.show) {
        return;
    }
    const onKey = (e) => {
        if (e.key !== 'Escape') {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        if (mode.value === 'quickAdd') {
            mode.value = 'browse';
            quickTitle.value = '';
            nextTick(() => filterInputRef.value?.focus?.());
        } else {
            emit('close');
        }
    };
    window.addEventListener('keydown', onKey, true);
    onCleanup(() => window.removeEventListener('keydown', onKey, true));
});

function close() {
    emit('close');
}

function runItem(item) {
    if (item.id === 'quick-add') {
        mode.value = 'quickAdd';
        query.value = '';
        highlightedIndex.value = 0;
        nextTick(() => quickInputRef.value?.focus?.());
        return;
    }
    emit('select', item.id);
    emit('close');
}

function onFilterKeydown(e) {
    if (e.key === 'Escape') {
        return;
    }
    const len = selectableItems.value.length;
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (len === 0) {
            return;
        }
        highlightedIndex.value = Math.min(len - 1, highlightedIndex.value + 1);
        return;
    }
    if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightedIndex.value = Math.max(0, highlightedIndex.value - 1);
        return;
    }
    if (e.key === 'Enter') {
        e.preventDefault();
        const item = selectableItems.value[highlightedIndex.value];
        if (item) {
            runItem(item);
        }
    }
}

function submitQuickAdd() {
    const title = quickTitle.value.trim();
    if (!title) {
        return;
    }
    emit('quick-add', title);
    emit('close');
}

function backFromQuickAdd() {
    mode.value = 'browse';
    quickTitle.value = '';
    nextTick(() => filterInputRef.value?.focus?.());
}

function onQuickKeydown(e) {
    if (e.key === 'Escape') {
        e.preventDefault();
        backFromQuickAdd();
        return;
    }
    if (e.key === 'Enter') {
        e.preventDefault();
        submitQuickAdd();
    }
}

function onBackdropPointerDown(e) {
    if (e.target === e.currentTarget) {
        close();
    }
}
</script>

<template>
    <Teleport to="body">
        <div
            v-show="show"
            class="fixed inset-0 z-[10000] flex items-start justify-center overflow-y-auto bg-gt-ink/50 px-3 py-8 sm:py-16 dark:bg-black/60"
            role="presentation"
            @pointerdown="onBackdropPointerDown"
        >
            <div
                v-show="show"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="'tasks-command-palette-title'"
                class="pointer-events-auto mt-0 w-full max-w-lg rounded-xl border border-gt-border bg-gt-raised shadow-2xl dark:shadow-black/50"
                @pointerdown.stop
            >
                <h2
                    id="tasks-command-palette-title"
                    class="border-b border-gt-border px-4 py-3 text-base font-semibold text-gt-ink"
                >
                    {{ t('tasks.commandPalette.title') }}
                </h2>

                <div
                    v-if="mode === 'browse'"
                    class="p-4"
                >
                    <TextInput
                        ref="filterInputRef"
                        v-model="query"
                        type="search"
                        class="block w-full"
                        :placeholder="t('tasks.commandPalette.filterPlaceholder')"
                        autocomplete="off"
                        @keydown="onFilterKeydown"
                    />
                    <ul
                        class="mt-3 max-h-[min(50vh,24rem)] overflow-y-auto rounded-md border border-gt-border"
                        role="listbox"
                        :aria-label="t('tasks.commandPalette.title')"
                    >
                        <template v-if="listRows.length === 0">
                            <li
                                class="px-4 py-6 text-center text-sm text-gt-muted"
                            >
                                {{ t('tasks.commandPalette.noResults') }}
                            </li>
                        </template>
                        <template
                            v-for="row in listRows"
                            :key="
                                row.kind === 'header'
                                    ? row.key
                                    : row.item.id
                            "
                        >
                            <li
                                v-if="row.kind === 'header'"
                                class="border-b border-gt-border bg-gt-field-muted/40 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gt-subtle first:border-t-0 dark:bg-gt-field/30"
                            >
                                {{ row.label }}
                            </li>
                            <li v-else>
                                <button
                                    type="button"
                                    role="option"
                                    :aria-selected="
                                        row.flatIdx === highlightedIndex
                                    "
                                    class="flex w-full px-3 py-2.5 text-left text-sm text-gt-ink hover:bg-gt-field-muted focus:bg-gt-field-muted focus:outline-none dark:hover:bg-gt-field/40 dark:focus:bg-gt-field/40"
                                    :class="{
                                        'bg-gt-accent-tint/50 dark:bg-gt-accent-tint/20':
                                            row.flatIdx === highlightedIndex,
                                    }"
                                    @click="runItem(row.item)"
                                >
                                    {{ row.item.label }}
                                </button>
                            </li>
                        </template>
                    </ul>
                    <p class="mt-3 text-xs text-gt-muted">
                        {{ t('tasks.commandPalette.footerHint') }}
                    </p>
                </div>

                <div
                    v-else
                    class="space-y-3 p-4"
                >
                    <p class="text-sm text-gt-muted">
                        {{ t('tasks.commandPalette.quickAddHint') }}
                    </p>
                    <TextInput
                        ref="quickInputRef"
                        v-model="quickTitle"
                        type="text"
                        class="block w-full"
                        :placeholder="t('tasks.titlePlaceholder')"
                        @keydown="onQuickKeydown"
                    />
                    <div class="flex flex-wrap gap-2">
                        <PrimaryButton
                            type="button"
                            @click="submitQuickAdd"
                        >
                            {{ t('tasks.add') }}
                        </PrimaryButton>
                        <SecondaryButton
                            type="button"
                            @click="backFromQuickAdd"
                        >
                            {{ t('tasks.commandPalette.back') }}
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
