import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { VDateInput } from 'vuetify/labs/VDateInput';
import { useUserStore } from '@/Stores/user';

// Vuetify imports
import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import { aliases, mdi } from 'vuetify/iconsets/mdi';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,

    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        ),

    setup({ el, App, props, plugin }) {
        // ✅ Create Pinia FIRST
        const pinia = createPinia();

        // ✅ Create Vuetify inside setup (cleaner lifecycle)
        const vuetify = createVuetify({
            components: {
                ...components,
                VDateInput,
            },
            directives,
            icons: {
                defaultSet: 'mdi',
                aliases,
                sets: { mdi },
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

        const app = createApp({
            render: () => h(App, props),
        });

        // Share CSRF token from Inertia page props to window object
        if (props.initialPage.props.csrfToken) {
            window.csrfToken = props.initialPage.props.csrfToken;
        }

        // ✅ Register Pinia FIRST before using any stores
        app
            .use(pinia)
            .use(plugin)
            .use(ZiggyVue)
            .use(vuetify);

        // ✅ NOW it's safe to use Pinia stores - after pinia is registered
        const userStore = useUserStore();
        if (props.initialPage.props.auth?.user) {
            userStore.syncWithInertia(props.initialPage.props.auth.user);
        } else {
            // User not authenticated - clear any stale data
            userStore.syncWithInertia(null);
        }

        app.mount(el);

        return app;
    },

    progress: {
        color: '#4B5563',
    },
});
