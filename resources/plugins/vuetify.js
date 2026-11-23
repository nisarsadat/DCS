import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";
import { createVueI18nAdapter } from "vuetify/locale/adapters/vue-i18n";
import { useI18n } from "vue-i18n";
import i18n from "../js/i18n";

const savedTheme =
    typeof localStorage !== "undefined" && localStorage.getItem("theme") === "dark"
        ? "myCustomDarkTheme"
        : "myCustomLightTheme";

export default createVuetify({
    components,
    directives,
    locale: {
        adapter: createVueI18nAdapter({ i18n, useI18n }),
        rtl: { fa: true, pa: true },
    },
    theme: {
        defaultTheme: savedTheme,
        themes: {
            myCustomLightTheme: {
                dark: false,
                colors: {
                    primary: "#0F766E",
                    secondary: "#CCFBF1",
                    accent: "#0F172A",
                    success: "#059669",
                    info: "#0284C7",
                    warning: "#D97706",
                    error: "#DC2626",
                    primaryOld: "#0F172A",
                    tealColor: "#0F766E",
                    tealAccent: "#F0FDFA",
                    tealDark: "#115E59",
                    danger: "#DC2626",
                    background: "#F8FAFC",
                    lightSectionBg: "#F8FAFC",
                    surface: "#FFFFFF",
                    tableRowBg: "#F8FAFC",
                    iconWrapperBg: "#F1F5F9",
                },
            },
            myCustomDarkTheme: {
                dark: true,
                colors: {
                    primary: "#2DD4BF",
                    secondary: "#134E4A",
                    accent: "#99F6E4",
                    success: "#34D399",
                    info: "#38BDF8",
                    warning: "#FBBF24",
                    error: "#F87171",
                    primaryOld: "#E2E8F0",
                    tealColor: "#2DD4BF",
                    tealAccent: "#134E4A",
                    tealDark: "#99F6E4",
                    danger: "#F87171",
                    background: "#0F172A",
                    lightSectionBg: "#0F172A",
                    surface: "#1E293B",
                    tableRowBg: "#1E293B",
                    iconWrapperBg: "#334155",
                },
            },
        },
    },
});
