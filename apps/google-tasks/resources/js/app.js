import '../css/app.css';
import './bootstrap';
import { initAppearance } from './appearance';

initAppearance();

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createVueI18n } from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const initialLocale = props.initialPage?.props?.locale ?? 'en';
        const i18n = createVueI18n(initialLocale);

        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n)
            .use(ZiggyVue);

        router.on('navigate', (event) => {
            const loc = event.detail.page.props.locale;
            if (loc === 'en' || loc === 'fr') {
                i18n.global.locale.value = loc;
            }
        });

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

if (import.meta.env.PROD && typeof navigator !== 'undefined' && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}
