import { ref } from 'vue';

/** @type {import('vue').Ref<null | (() => void)>} */
const opener = ref(null);

export function registerTasksCommandPaletteOpener(fn) {
    opener.value = fn;
}

export function unregisterTasksCommandPaletteOpener() {
    opener.value = null;
}

export function commandPaletteRequestOpen() {
    opener.value?.();
}
