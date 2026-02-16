import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { VDateInput } from 'vuetify/labs/VDateInput';

// Vuetify imports
import 'vuetify/styles'; // global CSS
import '@mdi/font/css/materialdesignicons.css'; // MDI icons
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import { aliases, mdi } from 'vuetify/iconsets/mdi';

const vuetify = createVuetify({
    components: {
        ...components,
        VDateInput,
    },
    directives,
    icons: {
        defaultSet: 'mdi',
        aliases,
        sets: {
            mdi,
        },
    },
    theme: {
        defaultTheme: 'toolLibraryLight',

        themes: {
            toolLibraryLight: {
                dark: false,
                colors: {
                    primary: '#2E7D32',
                    secondary: '#1565C0',
                    accent: '#F9A825',
                    background: '#F4F6F8',
                    surface: '#FFFFFF',
                    error: '#D32F2F',
                    info: '#0288D1',
                    success: '#2E7D32',
                    warning: '#ED6C02',
                },
            },

            toolLibraryDark: {
                dark: true,
                colors: {
                    primary: '#66BB6A',
                    secondary: '#42A5F5',
                    accent: '#FFD54F',
                    background: '#121212',
                    surface: '#1E1E1E',
                    error: '#EF5350',
                    info: '#29B6F6',
                    success: '#66BB6A',
                    warning: '#FFB74D',
                },
            },
        },
    },
});

const pinia = createPinia(); // <-- initialize Pinia

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(vuetify)
            .use(pinia)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
