<template>
    <div class="settings-page">
        <AppBar :mainTitle="t('systemSetting')" :subTitle="t('setting')" />

        <section class="settings-block">
            <h2>{{ t("clinicProfile") }}</h2>
            <form class="profile-layout" @submit.prevent="CreateComponySetting">
                <button type="button" class="logo-button" @click="logoInput?.click()">
                    <v-icon
                        v-if="!SettingRepository.systemSettings.photo"
                        size="36"
                        color="primary"
                    >
                        mdi-camera-outline
                    </v-icon>
                    <img
                        v-else
                        :src="SettingRepository.systemSettings.photo"
                        :alt="t('image')"
                    />
                </button>
                <input
                    ref="logoInput"
                    type="file"
                    accept="image/*"
                    class="hidden"
                    @change="handleFileChange"
                />

                <div class="profile-fields">
                    <v-text-field
                        v-model="SettingRepository.systemSettings.name"
                        :rules="[rules.required, rules.name]"
                        :label="t('companyName')"
                        variant="outlined"
                        density="compact"
                        hide-details="auto"
                    />
                    <v-text-field
                        v-model="SettingRepository.systemSettings.phone"
                        :rules="[rules.required, rules.number]"
                        :label="t('phone')"
                        variant="outlined"
                        density="compact"
                        hide-details="auto"
                    />
                    <v-text-field
                        v-model="SettingRepository.systemSettings.address"
                        :rules="[rules.required]"
                        :label="t('address')"
                        variant="outlined"
                        density="compact"
                        hide-details="auto"
                    />
                    <v-text-field
                        v-model="SettingRepository.systemSettings.email"
                        :rules="[rules.required, rules.email]"
                        :label="t('email')"
                        variant="outlined"
                        density="compact"
                        type="email"
                        hide-details="auto"
                    />
                </div>
            </form>
        </section>

        <section class="settings-block">
            <h2>{{ t("preferences") }}</h2>
            <div class="pref-grid">
                <v-select
                    v-model="dateType"
                    :items="dateTypes"
                    item-title="title"
                    item-value="value"
                    :label="t('dateType')"
                    variant="outlined"
                    density="compact"
                    hide-details
                />
                <v-select
                    v-model="defaultLanguage"
                    :items="languages"
                    item-title="title"
                    item-value="value"
                    :label="t('defaultLanguage')"
                    variant="outlined"
                    density="compact"
                    hide-details
                />
                <v-select
                    v-model="defaultTheme"
                    :items="themes"
                    item-title="title"
                    item-value="value"
                    :label="t('defaultTheme')"
                    variant="outlined"
                    density="compact"
                    hide-details
                />
            </div>
            <div class="save-row">
                <v-btn color="primary" variant="flat" @click="CreateComponySetting">
                    {{ t("save") }}
                </v-btn>
            </div>
        </section>

        <section class="settings-block">
            <h2>{{ t("dataCare") }}</h2>
            <p class="block-hint">{{ t("dataCareHint") }}</p>
            <div class="data-actions">
                <v-btn color="primary" variant="flat" :loading="busy === 'backup'" @click="saveBackup">
                    <v-icon start>mdi-content-save-outline</v-icon>
                    {{ t("backupNow") }}
                </v-btn>
                <v-btn variant="tonal" color="primary" @click="dataInput?.click()">
                    <v-icon start>mdi-upload-outline</v-icon>
                    {{ t("uploadData") }}
                </v-btn>
                <v-btn variant="tonal" color="error" @click="resetOpen = true">
                    <v-icon start>mdi-backup-restore</v-icon>
                    {{ t("resetData") }}
                </v-btn>
            </div>
            <input
                ref="dataInput"
                type="file"
                accept=".sql,text/plain"
                class="hidden"
                @change="chooseUpload"
            />

            <div class="backup-list">
                <p v-if="!backups.length" class="empty">{{ t("noBackups") }}</p>
                <div v-for="file in backups" :key="file.name" class="backup-row">
                    <div>
                        <div class="backup-name">{{ file.name }}</div>
                        <div class="backup-meta">{{ file.createdAt }} · {{ file.size }}</div>
                    </div>
                    <v-btn size="small" variant="text" color="primary" @click="downloadBackup(file.name)">
                        {{ t("download") }}
                    </v-btn>
                </div>
            </div>
        </section>

        <v-dialog v-model="restoreOpen" max-width="460">
            <v-card class="pa-5">
                <h3>{{ t("restoreTitle") }}</h3>
                <p class="block-hint">{{ t("restoreHint") }}</p>
                <p v-if="pendingFile" class="backup-name">{{ pendingFile.name }}</p>
                <div class="dialog-actions">
                    <v-btn variant="text" @click="cancelUpload">{{ t("close") }}</v-btn>
                    <v-btn color="primary" variant="flat" :loading="busy === 'restore'" @click="uploadData">
                        {{ t("continueAction") }}
                    </v-btn>
                </div>
            </v-card>
        </v-dialog>

        <v-dialog v-model="resetOpen" max-width="460">
            <v-card class="pa-5">
                <h3>{{ t("resetTitle") }}</h3>
                <p class="block-hint">{{ t("resetHint") }}</p>
                <div class="dialog-actions">
                    <v-btn variant="text" @click="resetOpen = false">{{ t("close") }}</v-btn>
                    <v-btn color="error" variant="flat" :loading="busy === 'reset'" @click="resetData">
                        {{ t("continueAction") }}
                    </v-btn>
                </div>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue3-toastify";
import AppBar from "@/components/AppBar.vue";
import { axios } from "../../../axios";
import { useSettingRepository } from "@/store/SettingRepository";

const { t } = useI18n();
const SettingRepository = useSettingRepository();
const logoInput = ref(null);
const dataInput = ref(null);
const backups = ref([]);
const busy = ref("");
const restoreOpen = ref(false);
const resetOpen = ref(false);
const pendingFile = ref(null);

SettingRepository.FetchSystemSettings();

const dateTypes = computed(() => [
    { title: t("shamsi"), value: "jalali" },
    { title: t("miladi"), value: "gregorian" },
]);
const dateType = computed({
    get() {
        return SettingRepository.systemSettings.date_type || SettingRepository.dateType || "jalali";
    },
    set(value) {
        SettingRepository.systemSettings.date_type = value;
    },
});
const languages = computed(() => [
    { title: t("english"), value: "en" },
    { title: t("dari"), value: "fa" },
    { title: t("pashto"), value: "pa" },
]);
const themes = computed(() => [
    { title: t("light"), value: "light" },
    { title: t("dark"), value: "dark" },
]);
const defaultLanguage = computed({
    get() {
        return SettingRepository.systemSettings.default_language || SettingRepository.defaultLanguage || "en";
    },
    set(value) {
        SettingRepository.systemSettings.default_language = value;
    },
});
const defaultTheme = computed({
    get() {
        return SettingRepository.systemSettings.default_theme || SettingRepository.defaultTheme || "light";
    },
    set(value) {
        SettingRepository.systemSettings.default_theme = value;
    },
});

const rules = {
    required: (value) => !!value || t("validation.required"),
    name: (value) => /^[a-zA-Z\s\u0600-\u06FF.]*$/.test(value) || t("validation.onlyLetters"),
    email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || t("validation.email"),
    number: (value) => /^\d+$/.test(value) || t("validation.required"),
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    SettingRepository.systemSettings.photo = URL.createObjectURL(file);
    SettingRepository.systemSettings.logo = file;
};

const CreateComponySetting = async () => {
    const formData = reactive({
        id: 1,
        name: SettingRepository.systemSettings.name,
        phone: SettingRepository.systemSettings.phone,
        email: SettingRepository.systemSettings.email,
        address: SettingRepository.systemSettings.address,
        image: SettingRepository.systemSettings.logo,
        date_type: dateType.value || "jalali",
        default_language: defaultLanguage.value || "en",
        default_theme: defaultTheme.value || "light",
    });
    await SettingRepository.UpdateSystemSetting(formData);
};

const messageFrom = (error, fallback) => error?.response?.data?.message || fallback;

const loadBackups = async () => {
    try {
        const response = await axios.get("system/backups");
        backups.value = response.data.data || [];
    } catch {
        backups.value = [];
    }
};

const saveBackup = async () => {
    busy.value = "backup";
    try {
        const response = await axios.post("system/backups");
        backups.value = response.data.data || [];
        toast.success(t("backupSaved"));
    } catch (error) {
        toast.error(messageFrom(error, t("backupFailed")));
    } finally {
        busy.value = "";
    }
};

const chooseUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    pendingFile.value = file;
    restoreOpen.value = true;
};

const cancelUpload = () => {
    restoreOpen.value = false;
    pendingFile.value = null;
    if (dataInput.value) dataInput.value.value = "";
};

const uploadData = async () => {
    if (!pendingFile.value) return;
    busy.value = "restore";
    try {
        const body = new FormData();
        body.append("file", pendingFile.value);
        const response = await axios.post("system/restore", body, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        backups.value = response.data.data || backups.value;
        toast.success(t("backupSaved"));
        cancelUpload();
    } catch (error) {
        toast.error(messageFrom(error, t("backupFailed")));
    } finally {
        busy.value = "";
    }
};

const resetData = async () => {
    busy.value = "reset";
    try {
        const response = await axios.post("system/reset", { confirm: "RESET" });
        backups.value = response.data.data || [];
        resetOpen.value = false;
        toast.success(t("backupSaved"));
    } catch (error) {
        toast.error(messageFrom(error, t("backupFailed")));
    } finally {
        busy.value = "";
    }
};

const downloadBackup = async (name) => {
    const response = await axios.get(`system/backups/${name}`, { responseType: "blob" });
    const url = URL.createObjectURL(response.data);
    const link = document.createElement("a");
    link.href = url;
    link.download = name;
    link.click();
    URL.revokeObjectURL(url);
};

onMounted(loadBackups);
</script>

<style scoped>
.settings-page {
    padding: 0.25rem 0.4rem 2rem;
}
.settings-block {
    margin-top: 1.35rem;
}
.settings-block h2,
.settings-block h3 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f172a;
}
.block-hint,
.empty,
.backup-meta {
    color: #64748b;
    font-size: 0.86rem;
}
.block-hint {
    margin: 0.35rem 0 0.9rem;
    max-width: 640px;
}
.profile-layout {
    display: flex;
    gap: 1.25rem;
    align-items: flex-start;
    margin-top: 0.9rem;
}
.logo-button {
    width: 112px;
    height: 112px;
    flex: 0 0 112px;
    border: 0;
    border-radius: 18px;
    background: #f1f5f9;
    overflow: hidden;
    cursor: pointer;
    padding: 0;
}
.logo-button img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.profile-fields,
.pref-grid {
    display: grid;
    gap: 0.85rem;
}
.profile-fields {
    flex: 1;
    grid-template-columns: 1fr 1fr;
}
.pref-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    margin-top: 0.9rem;
}
.save-row,
.data-actions,
.dialog-actions {
    display: flex;
    gap: 0.6rem;
    align-items: center;
}
.save-row {
    margin-top: 1rem;
}
.data-actions {
    flex-wrap: wrap;
}
.backup-list {
    margin-top: 1rem;
}
.backup-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.7rem 0;
}
.backup-row + .backup-row {
    border-top: 1px solid #eef2f6;
}
.backup-name {
    font-weight: 650;
    color: #0f172a;
}
.dialog-actions {
    justify-content: flex-end;
    margin-top: 1rem;
}
.hidden {
    display: none;
}
:global(.v-theme--dark) .settings-block h2,
:global(.v-theme--dark) .backup-name {
    color: #e2e8f0;
}
:global(.v-theme--dark) .logo-button {
    background: #1e293b;
}
@media (max-width: 800px) {
    .profile-layout,
    .profile-fields,
    .pref-grid {
        display: grid;
        grid-template-columns: 1fr;
    }
}
</style>
