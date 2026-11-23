<template>
    <div>
        <AppBar :subTitle="t('navInsights')" :main-title="t('notifications')" />
        <v-card class="panel" rounded="xl" elevation="0">
            <div class="toolbar">
                <v-chip-group v-model="filter" mandatory selected-class="text-primary" @update:model-value="load">
                    <v-chip value="all" filter>{{ t("all") }}</v-chip>
                    <v-chip value="unread" filter>{{ t("unread") }} {{ unread }}</v-chip>
                    <v-chip value="urgent" filter>{{ t("urgent") }}</v-chip>
                </v-chip-group>
                <v-spacer />
                <v-btn variant="outlined" color="primary" @click="markAll">{{ t("markAllRead") }}</v-btn>
            </div>
            <v-list lines="two">
                <v-list-item v-if="!items.length" :title="t('noNotifications')" />
                <v-list-item
                    v-for="item in items"
                    :key="item.id"
                    :title="item.title"
                    :subtitle="item.body"
                    :class="{ unread: !item.read }"
                    @click="open(item)"
                >
                    <template #prepend>
                        <v-avatar :color="item.priority === 'high' ? 'error' : 'primary'" variant="tonal" size="36">
                            <v-icon size="18">{{ item.priority === "high" ? "mdi-alert-outline" : "mdi-bell-outline" }}</v-icon>
                        </v-avatar>
                    </template>
                    <template #append>
                        <v-chip size="x-small" variant="tonal">{{ t(priorityKey(item.priority)) }}</v-chip>
                    </template>
                </v-list-item>
            </v-list>
        </v-card>

        <v-card class="panel mt-4" rounded="xl" elevation="0">
            <div class="panel-title">{{ t("notifyPreferences") }}</div>
            <p class="hint">{{ t("notifyHint") }}</p>
            <v-table>
                <thead>
                    <tr>
                        <th>{{ t("type") }}</th>
                        <th>{{ t("inApp") }}</th>
                        <th>SMS</th>
                        <th>Email</th>
                        <th>WhatsApp</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in preferences" :key="row.type">
                        <td>{{ t(row.type) }}</td>
                        <td><v-checkbox-btn v-model="row.in_app" /></td>
                        <td><v-checkbox-btn v-model="row.sms" /></td>
                        <td><v-checkbox-btn v-model="row.email" /></td>
                        <td><v-checkbox-btn v-model="row.whatsapp" /></td>
                    </tr>
                </tbody>
            </v-table>
            <div class="d-flex justify-end pa-3">
                <v-btn color="primary" @click="savePreferences">{{ t("save") }}</v-btn>
            </div>
        </v-card>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import { toast } from "vue3-toastify";
import AppBar from "../../components/AppBar.vue";
import { axios } from "../../axios";

const { t } = useI18n();
const router = useRouter();
const items = ref([]);
const unread = ref(0);
const filter = ref("all");
const types = ["appointment", "missed", "treatment", "lab", "payment", "inventory", "backup"];
const preferences = ref(types.map((type) => ({ type, in_app: true, sms: false, email: false, whatsapp: false })));

const priorityKey = (priority) => ({ high: "priorityHigh", medium: "priorityMedium", low: "priorityLow" }[priority] || "priorityLow");

const load = async () => {
    const [notes, prefs] = await Promise.all([
        axios.get("notifications", { params: { filter: filter.value } }),
        axios.get("notification-preferences"),
    ]);
    items.value = notes.data.data || [];
    unread.value = notes.data.unread || 0;
    const saved = prefs.data || [];
    preferences.value = types.map((type) => saved.find((row) => row.type === type) || {
        type, in_app: true, sms: false, email: false, whatsapp: false,
    });
};

const markAll = async () => {
    await axios.post("notifications/read-all");
    await load();
};

const open = async (item) => {
    if (!item.read) await axios.post("notifications/read", { ids: [item.id] });
    router.push(item.link || "/dashboard");
};

const savePreferences = async () => {
    await axios.put("notification-preferences", { preferences: preferences.value });
    toast.success(t("saved"));
};

onMounted(load);
</script>

<style scoped>
.panel { border: 1px solid #e2e8f0; background: #fff; padding: 0.5rem 0.75rem 0.75rem; }
.toolbar { display: flex; align-items: center; gap: 0.5rem; }
.unread { background: #f0fdfa; }
.panel-title { font-weight: 700; padding: 0.75rem 0.5rem 0; }
.hint { color: #64748b; font-size: 0.85rem; padding: 0 0.5rem 0.5rem; }
</style>
