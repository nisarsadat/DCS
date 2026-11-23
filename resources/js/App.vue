<template>
    <v-app>
        <v-main
            :style="{ backgroundColor: backgroundColor }"
        >
            <router-view :key="locale" />
        </v-main>
    </v-app>
</template>

<script setup>
import { updateGlobalOptions } from "vue3-toastify";
import { useTheme } from "vuetify";
import { computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { applyLocale } from "./i18n";

const theme = useTheme();
const { locale } = useI18n();
watch(
    locale,
    (lang) => {
        applyLocale(lang);
        updateGlobalOptions({
            position: "top-center",
            autoClose: 3500,
            rtl: lang === "fa" || lang === "pa",
        });
    },
    { immediate: true }
);
const backgroundColor = computed(
    () => theme.current.value.colors.lightSectionBg
);
import { useSettingRepository } from "./store/SettingRepository";
const SettingRepository = useSettingRepository();
if (localStorage.getItem("token")) {
    SettingRepository.FetchSystemSettings();
}
// import { ref, watch, computed } from "vue";
// import { useRoute } from "vue-router"; // Import to get the current route
// import NavigationDrawer from "./components/navigationDrawer.vue";
// import { useAuthRepository } from "@/store/AuthRepository";

// const authRepo = useAuthRepository();
// const drawer = ref(true);
// const rail = ref(authRepo.rail);

// watch(
//     () => authRepo.rail,
//     (newValue) => {
//         rail.value = newValue;
//     }
// );

// // Use Vue Router's `useRoute` to determine the current route
// const route = useRoute();
// const vCardStyle = computed(() => {
//     console.log(route.path);
//     return route.path === "/dashboard" // Replace 'dashboard' with the actual name of your route
//         ? "background-color:#f8f8f8"
//         : "background-color:white";
// });
</script>


