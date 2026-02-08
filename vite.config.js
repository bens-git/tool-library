import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins: [
        laravel({
            input: "resources/js/app.js",
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                additionalData: `@use "vuetify/styles" as *;`,
            },
        },
    },
    server: {
        host: true, // listen on all interfaces
        port: 8088,
        strictPort: true,
        hmr: {
            host: "192.168.0.146", // your LAN IP
            port: 8088,
        },
        cors: true, // allow cross-origin requests
    },
});
