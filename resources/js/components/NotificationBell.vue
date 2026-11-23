<template>
    <v-menu :close-on-content-click="false" location="bottom end" width="380">
        <template #activator="{ props }">
            <v-btn v-bind="props" icon variant="text" size="small" @click="load">
                <v-badge :content="unread" :model-value="unread > 0" color="error">
                    <v-icon>mdi-bell-outline</v-icon>
                </v-badge>
            </v-btn>
        </template>
        <v-card class="pa-2">
            <div class="d-flex align-center justify-space-between px-2 py-1">
                <strong>{{ t("notifications") }}</strong>
                <v-btn size="small" variant="text" @click="markAll">{{ t("markAllRead") }}</v-btn>
            </div>
            <v-list density="compact" class="notify-list">
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
                        <v-icon :color="item.priority === 'high' ? 'error' : 'primary'" size="18">
                            {{ item.priority === "high" ? "mdi-alert-circle-outline" : "mdi-bell-outline" }}
                        </v-icon>
                    </template>
                </v-list-item>
            </v-list>
            <v-btn block variant="text" color="primary" :to="'/notifications'">{{ t("viewAll") }}</v-btn>
        </v-card>
    </v-menu>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import { axios } from "../axios";

const { t } = useI18n();
const router = useRouter();
const items = ref([]);
const unread = ref(0);

const load = async () => {
    const response = await axios.get("notifications", { params: { limit: 6 } });
    items.value = response.data.data || [];
    unread.value = response.data.unread || 0;
};

const markAll = async () => {
    await axios.post("notifications/read-all");
    await load();
};

const open = async (item) => {
    if (!item.read) {
        await axios.post("notifications/read", { ids: [item.id] });
    }
    router.push(item.link || "/notifications");
};

onMounted(load);
</script>

<style scoped>
.notify-list {
    max-height: 360px;
    overflow: auto;
}
.unread {
    background: #f0fdfa;
}
</style>
