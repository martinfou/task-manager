import { onMounted, onUnmounted, ref } from 'vue';
import {
    DENSITY_KEY,
    THEME_KEY,
    applyAppearance,
    readDensity,
    readTheme,
} from '../appearance';

const APPEARANCE_EVENT = 'gt-appearance';

export function useAppearance() {
    const theme = ref('dark');
    const density = ref('comfortable');

    function loadFromStorage() {
        theme.value = readTheme();
        density.value = readDensity();
    }

    function commit() {
        window.localStorage.setItem(THEME_KEY, theme.value);
        window.localStorage.setItem(DENSITY_KEY, density.value);
        applyAppearance(theme.value, density.value);
        window.dispatchEvent(new CustomEvent(APPEARANCE_EVENT));
    }

    function toggleTheme() {
        theme.value = theme.value === 'dark' ? 'light' : 'dark';
        commit();
    }

    function setDensity(value) {
        density.value = value;
        commit();
    }

    onMounted(() => {
        loadFromStorage();
        applyAppearance(theme.value, density.value);
        const sync = () => loadFromStorage();
        window.addEventListener(APPEARANCE_EVENT, sync);
        onUnmounted(() =>
            window.removeEventListener(APPEARANCE_EVENT, sync),
        );
    });

    return {
        theme,
        density,
        toggleTheme,
        setDensity,
    };
}
