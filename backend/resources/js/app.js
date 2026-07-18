import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createI18n } from 'vue-i18n';
import { ZiggyVue } from 'ziggy-js';
import messages from './Locales/messages';

// Helper to read lang cookie
const getCookie = (name) => {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(';').shift();
  return 'en';
};

const initialLocale = getCookie('lang') || 'en';

// Sync layout parameters to prevent HMR misalignment
document.documentElement.lang = initialLocale;
document.documentElement.dir = initialLocale === 'ar' ? 'rtl' : 'ltr';

const i18n = createI18n({
    legacy: false, // Composition API mode
    locale: initialLocale,
    fallbackLocale: 'en',
    messages,
});

createInertiaApp({
    title: (title) => title ? `${title} - GlobalRental` : 'GlobalRental',
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#6366f1',
        showSpinner: true,
    },
});
