import { createI18n } from "vue-i18n";
import { en as vuetifyEn, fa as vuetifyFa } from "vuetify/locale";
import en from "./locales/en.json";
import fa from "./locales/fa.json";
import pa from "./locales/pa.json";

const vuetifyPa = {
    ...vuetifyFa,
    open: "پرانیستل",
    close: "تړل",
    noDataText: "معلومات نشته",
    dataIterator: {
        noResultsText: "هیڅ پایله ونه موندل شوه",
        loadingText: "بارېږي...",
    },
    dataFooter: {
        itemsPerPageText: "توکي په هره پاڼه:",
        itemsPerPageAll: "ټول",
        nextPage: "بل مخ",
        prevPage: "شاته",
        firstPage: "لومړی مخ",
        lastPage: "وروستی مخ",
        pageText: "{0}-{1} له {2}",
    },
    dataTable: {
        ...vuetifyFa.dataTable,
        itemsPerPageText: "کړښونه په هره پاڼه:",
    },
};

const savedLocale =
    typeof localStorage !== "undefined" ? localStorage.getItem("locale") : null;

const i18n = createI18n({
    legacy: false,
    locale: ["en", "fa", "pa"].includes(savedLocale) ? savedLocale : "en",
    fallbackLocale: "en",
    messages: {
        en: { ...en, $vuetify: vuetifyEn },
        fa: { ...fa, $vuetify: vuetifyFa },
        pa: { ...pa, $vuetify: vuetifyPa },
    },
});

export function applyLocale(lang) {
    const locale = ["en", "fa", "pa"].includes(lang) ? lang : "en";
    i18n.global.locale.value = locale;

    if (typeof localStorage !== "undefined") {
        localStorage.setItem("locale", locale);
    }

    if (typeof document !== "undefined") {
        const rtl = locale === "fa" || locale === "pa";
        document.documentElement.lang = locale === "pa" ? "ps" : locale;
        document.documentElement.dir = rtl ? "rtl" : "ltr";
    }

    return locale;
}

applyLocale(i18n.global.locale.value);

export default i18n;
