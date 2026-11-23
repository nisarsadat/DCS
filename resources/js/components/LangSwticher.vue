<template>
    <v-menu transition="scale-transition">
        <template #activator="{ props }">
            <v-btn
                v-bind="props"
                variant="text"
                class="d-flex align-center text-capitalize font-medium"
            >
                <v-icon left>mdi-web</v-icon>
                <span class="mr-1">{{ $t("selectLanguage") }}</span>
                <v-icon size="small">mdi-chevron-down</v-icon>
            </v-btn>
        </template>

        <v-list>
            <v-list-item
                v-for="item in languages"
                :key="item.lang"
                @click="changeLanguage(item.lang)"
            >
                <v-list-item-title>{{ item.title }}</v-list-item-title>
            </v-list-item>
        </v-list>
    </v-menu>
</template>

<script setup>
import { useI18n } from "vue-i18n";
const { t } = useI18n();
import { applyLocale } from "../i18n";

const languages = [
    { title: "English", lang: "en" },
    { title: "دری", lang: "fa" },
    { title: "پښتو", lang: "pa" },
];

const changeLanguage = (lang) => {
    applyLocale(lang);
};
</script>

<style scoped>
.icon-wrapper {
    padding: 0.4rem;
    border-radius: 0.5rem;
    display: flex;
    justify-content: center;
    align-items: center;
}
</style>
