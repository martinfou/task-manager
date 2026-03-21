export const THEME_KEY = 'gt-theme';

export const DENSITY_KEY = 'gt-density';

export function readTheme() {
    if (typeof window === 'undefined') {
        return 'dark';
    }

    return window.localStorage.getItem(THEME_KEY) ?? 'dark';
}

export function readDensity() {
    if (typeof window === 'undefined') {
        return 'comfortable';
    }

    return window.localStorage.getItem(DENSITY_KEY) ?? 'comfortable';
}

/**
 * @param {'dark'|'light'} theme
 * @param {'compact'|'comfortable'} density
 */
export function applyAppearance(theme, density) {
    if (typeof document === 'undefined') {
        return;
    }
    document.documentElement.classList.toggle('dark', theme === 'dark');
    document.documentElement.setAttribute('data-density', density);
}

export function initAppearance() {
    applyAppearance(readTheme(), readDensity());
}
