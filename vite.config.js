import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import Vuetify from "vite-plugin-vuetify";

export default defineConfig(({ mode }) => {
    const standalone = mode === "vercel";
    const vuePlugin = vue({
        template: {
            transformAssetUrls: {
                base: null,
                includeAbsolute: false,
            },
        },
    });

    return {
        resolve: {
            alias: {
                vue: "vue/dist/vue.esm-bundler.js",
            },
        },
        plugins: standalone
            ? [vuePlugin, Vuetify()]
            : [
                vuePlugin,
                laravel({
                    input: ["resources/css/app.css", "resources/js/app.js"],
                    refresh: true,
                }),
                Vuetify(),
            ],
        ...(standalone ? { publicDir: false } : {}),
        build: standalone
            ? {
                outDir: "dist",
                emptyOutDir: true,
                rollupOptions: {
                    input: "vercel/index.html",
                },
            }
            : {
                chunkSizeWarningLimit: 10000,
            },
    };
});
