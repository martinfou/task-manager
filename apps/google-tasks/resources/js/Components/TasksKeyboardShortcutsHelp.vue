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

/** Stable contrast inside <dialog> top layer (CSS variable inheritance can fail in some browsers). */
const kbdClass =
    'rounded-md border border-slate-300 bg-slate-100 px-2 py-1 font-mono text-xs font-semibold text-slate-800 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100';
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="$emit('close')">
        <div
            class="p-6 text-slate-900 antialiased dark:text-slate-100"
        >
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-50">
                {{ t('shortcuts.title') }}
            </h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                {{ t('shortcuts.intro') }}
            </p>
            <table
                class="mt-5 w-full border-collapse text-left text-sm"
            >
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-600">
                        <th
                            class="py-2 pe-4 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                        >
                            {{ t('shortcuts.colKeys') }}
                        </th>
                        <th
                            class="py-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                        >
                            {{ t('shortcuts.colAction') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-600">
                    <tr>
                        <td class="py-2.5 pe-4 align-top">
                            <kbd :class="kbdClass">{{
                                t('shortcuts.keysSearch')
                            }}</kbd>
                        </td>
                        <td class="py-2.5 text-slate-800 dark:text-slate-200">
                            {{ t('shortcuts.actionSearch') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2.5 pe-4 align-top">
                            <kbd :class="kbdClass">{{
                                t('shortcuts.keysNewTask')
                            }}</kbd>
                        </td>
                        <td class="py-2.5 text-slate-800 dark:text-slate-200">
                            {{ t('shortcuts.actionNewTask') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2.5 pe-4 align-top">
                            <kbd :class="kbdClass">{{
                                t('shortcuts.keysToday')
                            }}</kbd>
                        </td>
                        <td class="py-2.5 text-slate-800 dark:text-slate-200">
                            {{ t('shortcuts.actionToday') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2.5 pe-4 align-top">
                            <kbd :class="kbdClass">{{
                                t('shortcuts.keysInbox')
                            }}</kbd>
                        </td>
                        <td class="py-2.5 text-slate-800 dark:text-slate-200">
                            {{ t('shortcuts.actionInbox') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2.5 pe-4 align-top">
                            <kbd :class="kbdClass">{{
                                t('shortcuts.keysAll')
                            }}</kbd>
                        </td>
                        <td class="py-2.5 text-slate-800 dark:text-slate-200">
                            {{ t('shortcuts.actionAll') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2.5 pe-4 align-top">
                            <kbd :class="kbdClass">{{
                                t('shortcuts.keysList')
                            }}</kbd>
                        </td>
                        <td class="py-2.5 text-slate-800 dark:text-slate-200">
                            {{ t('shortcuts.actionList') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2.5 pe-4 align-top">
                            <kbd :class="kbdClass">{{
                                t('shortcuts.keysArrows')
                            }}</kbd>
                        </td>
                        <td class="py-2.5 text-slate-800 dark:text-slate-200">
                            {{ t('shortcuts.actionArrows') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2.5 pe-4 align-top">
                            <kbd :class="kbdClass">{{
                                t('shortcuts.keysToggle')
                            }}</kbd>
                        </td>
                        <td class="py-2.5 text-slate-800 dark:text-slate-200">
                            {{ t('shortcuts.actionToggle') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2.5 pe-4 align-top">
                            <kbd :class="kbdClass">{{
                                t('shortcuts.keysInspect')
                            }}</kbd>
                        </td>
                        <td class="py-2.5 text-slate-800 dark:text-slate-200">
                            {{ t('shortcuts.actionInspect') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2.5 pe-4 align-top">
                            <kbd :class="kbdClass">{{
                                t('shortcuts.keysDblClick')
                            }}</kbd>
                        </td>
                        <td class="py-2.5 text-slate-800 dark:text-slate-200">
                            {{ t('shortcuts.actionDblClick') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2.5 pe-4 align-top">
                            <kbd :class="kbdClass">{{
                                t('shortcuts.keysHelp')
                            }}</kbd>
                        </td>
                        <td class="py-2.5 text-slate-800 dark:text-slate-200">
                            {{ t('shortcuts.actionHelp') }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <p
                class="mt-5 text-xs leading-relaxed text-slate-500 dark:text-slate-400"
            >
                {{ t('shortcuts.conflictsNote') }}
            </p>
        </div>
    </Modal>
</template>
