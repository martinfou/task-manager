import { onMounted, onUnmounted } from 'vue';

/**
 * Global keyboard shortcuts for the Tasks page (desktop).
 * Skips handling when the user is typing in form fields (input/textarea/select/contenteditable).
 * Uses capture phase for Space/Arrows so task-row actions win over nested controls where needed.
 *
 * @param {object} options
 * @param {import('vue').Ref<boolean>} options.connected
 * @param {import('vue').Ref<boolean>} options.showHelp
 * @param {import('vue').Ref<unknown[]>} options.tasks
 * @param {import('vue').Ref<number>} options.focusedTaskIndex
 * @param {() => void} options.onOpenHelp
 * @param {() => void} options.onFocusSearch
 * @param {() => void} options.onFocusNewTask
 * @param {() => void | Promise<void>} options.onGoToday
 * @param {() => void | Promise<void>} options.onGoInbox
 * @param {() => void | Promise<void>} options.onGoAll
 * @param {() => void | Promise<void>} options.onGoList
 * @param {(task: object) => void | Promise<void>} options.onToggleComplete
 * @param {(() => void) | undefined} options.onInspectFocusedTask
 */
export function useTasksKeyboardShortcuts(options) {
    const {
        connected,
        showHelp,
        tasks,
        focusedTaskIndex,
        onOpenHelp,
        onFocusSearch,
        onFocusNewTask,
        onGoToday,
        onGoInbox,
        onGoAll,
        onGoList,
        onToggleComplete,
        onInspectFocusedTask,
    } = options;

    let gChordTimer = null;
    let gChordPending = false;

    function clearGChord() {
        gChordPending = false;
        if (gChordTimer !== null) {
            clearTimeout(gChordTimer);
            gChordTimer = null;
        }
    }

    function scheduleGChord() {
        clearGChord();
        gChordPending = true;
        gChordTimer = window.setTimeout(() => {
            gChordPending = false;
            gChordTimer = null;
        }, 1000);
    }

    function isTypingContext(el) {
        if (!el || !(el instanceof HTMLElement)) {
            return false;
        }
        const tag = el.tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
            return true;
        }
        if (el.isContentEditable) {
            return true;
        }
        if (el.closest('[contenteditable="true"]')) {
            return true;
        }
        if (el.getAttribute('role') === 'textbox') {
            return true;
        }
        return false;
    }

    function clampFocusIndex() {
        const len = tasks.value.length;
        if (len === 0) {
            focusedTaskIndex.value = -1;
            return;
        }
        if (focusedTaskIndex.value >= len) {
            focusedTaskIndex.value = len - 1;
        }
    }

    /**
     * @param {KeyboardEvent} e
     */
    function handleKeydown(e) {
        if (!connected.value) {
            return;
        }

        if (showHelp.value) {
            return;
        }

        const typing = isTypingContext(e.target);

        if ((e.ctrlKey || e.metaKey) && (e.key === '/' || e.code === 'Slash')) {
            e.preventDefault();
            onOpenHelp();
            return;
        }

        if (!typing && e.shiftKey && e.key === '?') {
            e.preventDefault();
            onOpenHelp();
            return;
        }

        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            onFocusSearch();
            return;
        }

        if (!typing && e.key === '/' && !e.ctrlKey && !e.metaKey && !e.altKey) {
            e.preventDefault();
            onFocusSearch();
            return;
        }

        if (!typing && e.key === 'n' && !e.ctrlKey && !e.metaKey && !e.altKey) {
            e.preventDefault();
            focusedTaskIndex.value = -1;
            onFocusNewTask();
            return;
        }

        if (!typing && e.key === 'g' && !e.ctrlKey && !e.metaKey && !e.altKey) {
            e.preventDefault();
            scheduleGChord();
            return;
        }

        if (
            gChordPending &&
            !typing &&
            ['t', 'i', 'l', 'a'].includes(e.key) &&
            !e.ctrlKey &&
            !e.metaKey &&
            !e.altKey
        ) {
            e.preventDefault();
            clearGChord();
            if (e.key === 't') {
                void onGoToday();
            } else if (e.key === 'i') {
                void onGoInbox();
            } else if (e.key === 'a') {
                void onGoAll();
            } else {
                void onGoList();
            }
            focusedTaskIndex.value = -1;
            return;
        }

        if (typing) {
            return;
        }

        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            if (tasks.value.length === 0) {
                return;
            }
            e.preventDefault();
            clampFocusIndex();
            if (e.key === 'ArrowDown') {
                if (focusedTaskIndex.value < 0) {
                    focusedTaskIndex.value = 0;
                } else {
                    focusedTaskIndex.value = Math.min(
                        tasks.value.length - 1,
                        focusedTaskIndex.value + 1,
                    );
                }
            } else {
                if (focusedTaskIndex.value <= 0) {
                    focusedTaskIndex.value = -1;
                } else {
                    focusedTaskIndex.value -= 1;
                }
            }
            return;
        }

        if (e.key === ' ' || e.key === 'Enter') {
            const idx = focusedTaskIndex.value;
            if (idx < 0) {
                return;
            }
            const task = tasks.value[idx];
            if (!task || task._optimistic) {
                return;
            }
            e.preventDefault();
            void onToggleComplete(task);
            return;
        }

        if (
            onInspectFocusedTask &&
            e.key === 'i' &&
            !e.ctrlKey &&
            !e.metaKey &&
            !e.altKey &&
            !e.shiftKey
        ) {
            const idx = focusedTaskIndex.value;
            if (idx < 0) {
                return;
            }
            const task = tasks.value[idx];
            if (!task || task._optimistic) {
                return;
            }
            e.preventDefault();
            onInspectFocusedTask();
        }
    }

    onMounted(() => {
        window.addEventListener('keydown', handleKeydown, true);
    });

    onUnmounted(() => {
        window.removeEventListener('keydown', handleKeydown, true);
        clearGChord();
    });
}
