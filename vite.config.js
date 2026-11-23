import { fileURLToPath } from "node:url";
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import Vuetify from "vite-plugin-vuetify";

export default defineConfig(({ mode }) => {
    const standalone = mode === "vercel";
    const projectRoot = fileURLToPath(new URL(".", import.meta.url));
    const vuePlugin = vue({
        template: {
            transformAssetUrls: {
                base: null,
                includeAbsolute: false,
            },
        },
    });

    return {
        root: standalone ? fileURLToPath(new URL("./vercel", import.meta.url)) : projectRoot,
        envDir: projectRoot,
        resolve: {
            alias: {
                "@": fileURLToPath(new URL("./resources/js", import.meta.url)),
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
        ...(standalone ? { publicDir: fileURLToPath(new URL("./public", import.meta.url)) } : {}),
        build: standalone
            ? {
                outDir: fileURLToPath(new URL("./dist", import.meta.url)),
                emptyOutDir: true,
            }
            : {
                chunkSizeWarningLimit: 10000,
            },
    };
});
