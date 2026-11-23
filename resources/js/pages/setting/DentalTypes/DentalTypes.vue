<template>
    <CreateDental v-if="SettingRepository.createDialog" />
    <div class="types-page" :dir="dir">
        <AppBar :mainTitle="$t('dentalTypes')" :sub-title="$t('setting')" />

        <div class="types-bar">
            <v-text-field
                color="primaryOld"
                density="compact"
                variant="outlined"
                :label="$t('search')"
                append-inner-icon="mdi-magnify"
                hide-details
                v-model="SettingRepository.dentalSearch"
            ></v-text-field>
            <div class="types-actions">
                <v-btn variant="outlined" color="primaryOld" class="px-6" @click="showFilters = !showFilters">
                    {{ t("filter") }}
                </v-btn>
                <v-btn @click="CreateDialogShow" color="primaryOld" variant="flat" class="px-6">
                    {{ t("create") }}
                </v-btn>
            </div>
        </div>

        <TableFilter
            v-model="showFilters"
            :source="SettingRepository.dentals"
            v-model:result="filteredRows"
            :fields="filterFields"
        />

        <p v-if="!loading && !rows.length" class="empty">{{ t("noDentalTypes") }}</p>

        <article v-for="item in rows" :key="item.id" class="type-card">
            <DentalImages variant="slide" :images="item.images || []" :title="item.name" />
            <div class="type-copy">
                <h3>{{ item.name }}</h3>
                <p>{{ item.description }}</p>
            </div>
            <div class="type-menu">
                <button type="button" @click="edit(item)">{{ t("edit") }}</button>
                <button type="button" class="danger" @click="deleteItem(item)">{{ t("delete") }}</button>
            </div>
        </article>

        <v-pagination
            v-if="pages > 1"
            v-model="page"
            :length="pages"
            density="compact"
            class="types-pages"
            @update:model-value="load"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import AppBar from "../../../components/AppBar.vue";
import CreateDental from "./CreateDental.vue";
import DentalImages from "../../../components/DentalImages.vue";
import { useSettingRepository } from "@/store/SettingRepository";
import { useI18n } from "vue-i18n";

const showFilters = ref(false);
const filteredRows = ref(null);
const page = ref(1);
const perPage = 8;
const filterFields = [
    { key: "name", label: "name" },
    { key: "description", label: "details" },
];

const { t, locale } = useI18n();
const dir = computed(() => (locale.value === "fa" || locale.value === "pa" ? "rtl" : "ltr"));
const SettingRepository = useSettingRepository();
const loading = computed(() => SettingRepository.loading);
const rows = computed(() => filteredRows.value ?? SettingRepository.dentals);
const pages = computed(() => Math.ceil((SettingRepository.totalItems || 0) / perPage));

const load = () => {
    SettingRepository.FetchDentals({ page: page.value, itemsPerPage: perPage });
};

const CreateDialogShow = () => {
    SettingRepository.dental = {};
    SettingRepository.isEditMode = false;
    SettingRepository.createDialog = true;
};

const edit = (item) => {
    SettingRepository.isEditMode = true;
    SettingRepository.dental = {};
    SettingRepository.fetchDental(item.id)
        .then(() => {
            SettingRepository.createDialog = true;
        })
        .catch((error) => {
            console.error("Error fetching data:", error);
        });
};

const deleteItem = async (item) => {
    await SettingRepository.DeleteDental(item.id);
};

watch(
    () => SettingRepository.dentalSearch,
    () => {
        page.value = 1;
        load();
    }
);

onMounted(load);
</script>

<style scoped>
.types-page { padding: 0.25rem 0.4rem 2rem; }
.types-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 1.2rem;
}
.types-bar .v-input { max-width: 280px; }
.types-actions { display: flex; gap: 0.6rem; }
.type-card {
    display: grid;
    grid-template-columns: 220px 1fr auto;
    gap: 1rem;
    align-items: center;
    padding: 0.9rem 0.1rem;
    border-top: 1px solid #eef2f6;
}
.type-copy h3 { margin: 0; font-size: 1.02rem; color: #0f172a; }
.type-copy p { margin: 0.3rem 0 0; color: #64748b; font-size: 0.9rem; }
.type-menu { display: flex; gap: 0.4rem; }
.type-menu button {
    border: 1px solid #ccfbf1;
    background: #f0fdfa;
    color: #0f766e;
    border-radius: 12px;
    padding: 0.4rem 0.85rem;
    font-weight: 600;
    cursor: pointer;
}
.type-menu button:hover { background: #ccfbf1; }
.type-menu .danger {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
}
.type-menu .danger:hover { background: #fee2e2; }
.empty { color: #64748b; margin-top: 1.4rem; }
.types-pages { margin-top: 1rem; }
:global(.v-theme--dark) .type-copy h3 { color: #e2e8f0; }
:global(.v-theme--dark) .type-card { border-color: #1e293b; }
:global(.v-theme--dark) .type-menu button {
    background: #134e4a;
    color: #99f6e4;
    border-color: #115e59;
}
:global(.v-theme--dark) .type-menu .danger {
    background: #3f1d1d;
    color: #fca5a5;
    border-color: #7f1d1d;
}
@media (max-width: 720px) {
    .types-bar, .type-card { display: grid; grid-template-columns: 1fr; }
    .types-bar .v-input { max-width: none; }
}
</style>
