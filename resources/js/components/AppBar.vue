<template>
    <div class="clinic-header">
        <div class="clinic-header-title">
            <v-btn icon="mdi-menu" variant="text" size="small" @click="toggleSidebar" />
            <div>
                <div class="clinic-kicker">{{ subTitle }}</div>
                <div class="clinic-heading">{{ mainTitle }}</div>
            </div>
        </div>

        <div class="clinic-header-tools">
            <div ref="searchWrap" class="clinic-search-wrap">
                <label class="clinic-search">
                    <v-icon size="18">mdi-magnify</v-icon>
                    <input
                        id="clinic-search"
                        v-model="query"
                        type="search"
                        :placeholder="t('searchPlaceholder')"
                        @input="onSearch"
                        @keydown.enter="goFirst"
                    />
                </label>
                <div v-if="searchOpen && hasResults" class="search-panel">
                    <div v-for="group in groups" :key="group.key" class="search-group">
                        <div class="search-group-label">{{ group.label }}</div>
                        <button
                            v-for="item in group.items"
                            :key="group.key + item.id"
                            type="button"
                            class="search-hit"
                            @click="openResult(item)"
                        >
                            <span>{{ item.title }}</span>
                            <small v-if="item.subtitle">{{ item.subtitle }}</small>
                        </button>
                    </div>
                </div>
            </div>
            <NotificationBell />
            <v-menu>
                <template #activator="{ props }">
                    <v-btn v-bind="props" icon="mdi-translate" variant="text" size="small" />
                </template>
                <v-list>
                    <v-list-item v-for="item in languages" :key="item.lang" :title="item.title" @click="changeLanguage(item.lang)" />
                </v-list>
            </v-menu>
            <v-btn :icon="isDarkTheme ? 'mdi-weather-night' : 'mdi-white-balance-sunny'" variant="text" size="small" @click="toggleTheme" />
            <v-btn icon="mdi-fullscreen" variant="text" size="small" @click="toggleFullscreen" />
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import { useTheme } from "vuetify";
import { axios } from "../axios";
import { applyLocale } from "../i18n";
import { useAuthRepository } from "@/store/AuthRepository";
import NotificationBell from "./NotificationBell.vue";

defineProps(["mainTitle", "subTitle"]);

const AuthRepository = useAuthRepository();
const { t } = useI18n();
const theme = useTheme();
const router = useRouter();
const query = ref("");
const results = ref({ patients: [], appointments: [], inventory: [] });
const searchOpen = ref(false);
const searchWrap = ref(null);
let timer = null;

const languages = [
    { title: "English", lang: "en" },
    { title: "دری", lang: "fa" },
    { title: "پښتو", lang: "pa" },
];

const isDarkTheme = computed(() => theme.global.current.value.dark);
const groups = computed(() => [
    {
        key: "patients",
        label: t("patients"),
        items: (results.value.patients || []).map((item) => ({
            id: item.id,
            title: item.name,
            subtitle: item.phone,
            to: "/patients",
        })),
    },
    {
        key: "appointments",
        label: t("appointment"),
        items: (results.value.appointments || []).map((item) => ({
            id: item.id,
            title: item.name,
            subtitle: item.status,
            to: "/appointments",
        })),
    },
    {
        key: "inventory",
        label: t("inventory"),
        items: (results.value.inventory || []).map((item) => ({
            id: item.id,
            title: item.name,
            subtitle: item.sku,
            to: "/inventory",
        })),
    },
].filter((group) => group.items.length));

const hasResults = computed(() => groups.value.length > 0);

const onSearch = () => {
    clearTimeout(timer);
    timer = setTimeout(async () => {
        if (query.value.trim().length < 2) {
            results.value = { patients: [], appointments: [], inventory: [] };
            searchOpen.value = false;
            return;
        }
        const response = await axios.get("search", { params: { q: query.value.trim() } });
        results.value = response.data;
        searchOpen.value = hasResults.value;
    }, 250);
};

const openResult = (item) => {
    searchOpen.value = false;
    query.value = "";
    router.push(item.to);
};

const goFirst = () => {
    const first = groups.value[0]?.items[0];
    if (first) openResult(first);
};

const onDocClick = (event) => {
    if (!searchWrap.value?.contains(event.target)) searchOpen.value = false;
};

const changeLanguage = (lang) => applyLocale(lang);
const toggleSidebar = () => AuthRepository.toggleRail();
const toggleTheme = () => {
    const next = theme.global.name.value === "myCustomLightTheme" ? "myCustomDarkTheme" : "myCustomLightTheme";
    theme.global.name.value = next;
    localStorage.setItem("theme", next === "myCustomDarkTheme" ? "dark" : "light");
};
const toggleFullscreen = async () => {
    if (!document.fullscreenElement) await document.documentElement.requestFullscreen();
    else await document.exitFullscreen();
};

const onKey = (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "k") {
        event.preventDefault();
        document.getElementById("clinic-search")?.focus();
    }
};

onMounted(() => {
    window.addEventListener("keydown", onKey);
    document.addEventListener("click", onDocClick);
});
onBeforeUnmount(() => {
    window.removeEventListener("keydown", onKey);
    document.removeEventListener("click", onDocClick);
});
</script>

<style scoped>
.clinic-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    min-height: 64px;
    margin-bottom: 0.75rem;
}
.clinic-header-title {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    min-width: 0;
}
.clinic-kicker {
    font-size: 0.75rem;
    color: #64748b;
}
.clinic-heading {
    font-size: 1.15rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.clinic-header-tools {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin-inline-start: auto;
}
.clinic-search-wrap {
    position: relative;
    margin-inline-end: 0.35rem;
}
.clinic-search {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    width: 260px;
    height: 40px;
    padding: 0 0.8rem;
    background: #f1f5f9;
    border-radius: 12px;
    color: #94a3b8;
}
.clinic-search input {
    flex: 1;
    min-width: 0;
    border: 0;
    outline: 0;
    background: transparent;
    color: #0f172a;
    font: inherit;
    font-size: 0.84rem;
}
.clinic-search input::placeholder {
    color: #94a3b8;
}
.search-panel {
    position: absolute;
    z-index: 30;
    top: calc(100% + 6px);
    inset-inline-end: 0;
    width: 320px;
    max-height: 320px;
    overflow: auto;
    padding: 0.35rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12);
}
.search-group-label {
    padding: 0.35rem 0.55rem 0.15rem;
    font-size: 0.72rem;
    font-weight: 700;
    color: #94a3b8;
}
.search-hit {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    width: 100%;
    border: 0;
    background: transparent;
    border-radius: 10px;
    padding: 0.45rem 0.55rem;
    cursor: pointer;
    color: #0f172a;
    font: inherit;
    text-align: start;
}
.search-hit small {
    color: #64748b;
    font-size: 0.75rem;
}
.search-hit:hover {
    background: #f0fdfa;
}
:global(.v-theme--dark) .clinic-heading,
:global(.v-theme--dark) .clinic-search input,
:global(.v-theme--dark) .search-hit {
    color: #e2e8f0;
}
:global(.v-theme--dark) .clinic-search {
    background: #1e293b;
}
:global(.v-theme--dark) .search-panel {
    background: #1e293b;
    border-color: #334155;
}
@media (max-width: 900px) {
    .clinic-header {
        flex-wrap: wrap;
    }
    .clinic-header-tools {
        width: 100%;
    }
    .clinic-search {
        width: min(260px, 46vw);
    }
}
</style>
