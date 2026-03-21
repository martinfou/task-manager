<script setup>
import Modal from '@/Components/Modal.vue';
import { useI18n } from 'vue-i18n';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['close']);

const { t } = useI18n();
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="$emit('close')">
        <div class="p-6">
            <h2
                class="text-lg font-semibold text-gray-900 dark:text-slate-100"
            >
                {{ t('shortcuts.title') }}
            </h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-slate-400">
                {{ t('shortcuts.intro') }}
            </p>
            <table
                class="mt-4 w-full border-collapse text-left text-sm text-gray-800 dark:text-slate-200"
            >
                <thead>
                    <tr class="border-b border-gray-200 dark:border-slate-700">
                        <th class="py-2 pe-4 font-medium">
                            {{ t('shortcuts.colKeys') }}
                        </th>
                        <th class="py-2 font-medium">
                            {{ t('shortcuts.colAction') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                    <tr>
                        <td class="py-2 pe-4 align-top">
                            <kbd
                                class="rounded border border-gray-300 bg-gray-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-gray-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                >{{ t('shortcuts.keysSearch') }}</kbd
                            >
                        </td>
                        <td class="py-2">{{ t('shortcuts.actionSearch') }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 pe-4 align-top">
                            <kbd
                                class="rounded border border-gray-300 bg-gray-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-gray-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                >{{ t('shortcuts.keysNewTask') }}</kbd
                            >
                        </td>
                        <td class="py-2">{{ t('shortcuts.actionNewTask') }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 pe-4 align-top">
                            <kbd
                                class="rounded border border-gray-300 bg-gray-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-gray-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                >{{ t('shortcuts.keysToday') }}</kbd
                            >
                        </td>
                        <td class="py-2">{{ t('shortcuts.actionToday') }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 pe-4 align-top">
                            <kbd
                                class="rounded border border-gray-300 bg-gray-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-gray-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                >{{ t('shortcuts.keysInbox') }}</kbd
                            >
                        </td>
                        <td class="py-2">{{ t('shortcuts.actionInbox') }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 pe-4 align-top">
                            <kbd
                                class="rounded border border-gray-300 bg-gray-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-gray-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                >{{ t('shortcuts.keysList') }}</kbd
                            >
                        </td>
                        <td class="py-2">{{ t('shortcuts.actionList') }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 pe-4 align-top">
                            <kbd
                                class="rounded border border-gray-300 bg-gray-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-gray-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                >{{ t('shortcuts.keysArrows') }}</kbd
                            >
                        </td>
                        <td class="py-2">{{ t('shortcuts.actionArrows') }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 pe-4 align-top">
                            <kbd
                                class="rounded border border-gray-300 bg-gray-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-gray-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                >{{ t('shortcuts.keysToggle') }}</kbd
                            >
                        </td>
                        <td class="py-2">{{ t('shortcuts.actionToggle') }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 pe-4 align-top">
                            <kbd
                                class="rounded border border-gray-300 bg-gray-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-gray-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                >{{ t('shortcuts.keysHelp') }}</kbd
                            >
                        </td>
                        <td class="py-2">{{ t('shortcuts.actionHelp') }}</td>
                    </tr>
                </tbody>
            </table>
            <p
                class="mt-4 text-xs text-gray-500 dark:text-slate-500"
            >
                {{ t('shortcuts.conflictsNote') }}
            </p>
        </div>
    </Modal>
</template>
