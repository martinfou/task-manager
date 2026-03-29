import { computed, ref } from 'vue';
import axios from 'axios';

/**
 * Composable for managing custom task list ordering (pin, reorder, auto-sort).
 *
 * The server returns lists already ordered via TaskListOrder::applyOrder(),
 * so for most consumers the ordered `taskLists` ref is sufficient.
 * This composable handles optimistic reorder/pin updates and persistence.
 */
export function useListOrder(taskLists) {
    const autoSort = ref(null); // null | 'alpha_asc' | 'alpha_desc'
    const showOrganiseSheet = ref(false);
    const contextMenu = ref({ visible: false, x: 0, y: 0, list: null });

    /** Pinned lists (already ordered by server). */
    const pinnedLists = computed(() =>
        taskLists.value.filter((l) => l.pinned),
    );

    /** Unpinned lists (already ordered by server). */
    const unpinnedLists = computed(() =>
        taskLists.value.filter((l) => !l.pinned),
    );

    /** Whether to show a visual divider between pinned and unpinned sections. */
    const hasDivider = computed(
        () => pinnedLists.value.length > 0 && unpinnedLists.value.length > 0,
    );

    /** Persist the current order of lists to the server. */
    async function saveOrder(orderedItems, sortMode) {
        const items = orderedItems.map((list) => ({
            id: list.id,
            pinned: !!list.pinned,
        }));

        try {
            await axios.put(route('tasks.data.list-order.save'), {
                items,
                autoSort: sortMode ?? autoSort.value,
            });
        } catch (e) {
            console.warn('Failed to save list order', e);
        }
    }

    /** Toggle pin state for a single list. Returns the new pinned state. */
    async function togglePin(listId) {
        // Optimistic update
        const list = taskLists.value.find((l) => l.id === listId);
        if (list) {
            list.pinned = !list.pinned;
        }

        try {
            const { data } = await axios.patch(
                route('tasks.data.list-order.toggle-pin', {
                    listId,
                }),
            );
            // Reconcile with server response
            if (list) {
                list.pinned = data.pinned;
            }
            return data.pinned;
        } catch (e) {
            // Revert optimistic update
            if (list) {
                list.pinned = !list.pinned;
            }
            console.warn('Failed to toggle pin', e);
            return list?.pinned ?? false;
        }
    }

    /** Show right-click context menu for a list. */
    function showContextMenu(event, list) {
        event.preventDefault();
        contextMenu.value = {
            visible: true,
            x: event.clientX,
            y: event.clientY,
            list,
        };
    }

    /** Hide the context menu. */
    function hideContextMenu() {
        contextMenu.value = { visible: false, x: 0, y: 0, list: null };
    }

    /** Update auto-sort preference. */
    function setAutoSort(mode) {
        autoSort.value = mode || null;
    }

    return {
        autoSort,
        pinnedLists,
        unpinnedLists,
        hasDivider,
        showOrganiseSheet,
        contextMenu,
        saveOrder,
        togglePin,
        showContextMenu,
        hideContextMenu,
        setAutoSort,
    };
}
