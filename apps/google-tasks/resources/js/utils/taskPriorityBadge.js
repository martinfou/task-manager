/**
 * @param {string} priority
 * @returns {string}
 */
export function priorityBadgeClass(priority) {
    if (priority === 'p1') {
        return 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300';
    }
    if (priority === 'p2') {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300';
    }
    if (priority === 'p4') {
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300';
    }

    return 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300';
}
