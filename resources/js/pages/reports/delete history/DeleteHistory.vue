<template>
    <div class="history-page">
        <AppBar :mainTitle="t('deleteHistory')" :subTitle="t('reports')" />

        <div class="history-bar">
            <label class="type-field">
                <span>{{ t("type") }}</span>
                <select v-model="type" @change="load">
                    <option value="">{{ t("all") }}</option>
                    <option v-for="item in types" :key="item" :value="item">
                        {{ t(typeLabel[item]) }}
                    </option>
                </select>
            </label>
            <span class="count">{{ t("filterMatches", { count: rows.length }) }}</span>
        </div>

        <p v-if="!loading && !rows.length" class="empty">{{ t("noDeleted") }}</p>

        <div v-else class="history-table">
            <div class="history-head">
                <span>{{ t("type") }}</span>
                <span>{{ t("record") }}</span>
                <span>{{ t("deletedAt") }}</span>
                <span></span>
            </div>
            <div v-for="row in rows" :key="`${row.type}-${row.id}`" class="history-row">
                <span>{{ t(typeLabel[row.type] || row.type) }}</span>
                <strong>{{ row.label }}</strong>
                <span>{{ formatDisplayDate(row.deletedAt) }}</span>
                <button type="button" :disabled="busyId === row.id" @click="restore(row)">
                    {{ t("restore") }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue3-toastify";
import AppBar from "@/components/AppBar.vue";
import { axios } from "../../../axios";
import { formatDisplayDate } from "@/formatDate";

const { t } = useI18n();
const type = ref("");
const rows = ref([]);
const loading = ref(false);
const busyId = ref(null);

const typeLabel = {
    people: "people",
    appointment: "appointment",
    lead: "leads",
    cureCycle: "cureCycle",
    cure: "treatment",
    curePayment: "payment",
    expense: "expense",
    billExpense: "billExpense",
    payment: "payment",
    ownerPickup: "pickup",
    inboundLaboratory: "inBoundLaboratory",
    outboundLaboratory: "outboundLaboratory",
    moneyAccount: "moneyAccount",
    moneyTransfer: "transfers",
    peopleAccount: "peopleAccount",
    category: "category",
    leadStage: "leadStage",
    service: "service",
    serviceGroup: "serviceGroup",
    expenseProduct: "expenseProduct",
    expenseCategory: "expenseCategory",
    salary: "salary",
    accountTransaction: "accounts",
    peopleTransaction: "peopleAccount",
};

const types = Object.keys(typeLabel);

const load = async () => {
    loading.value = true;
    try {
        const response = await axios.get("deleted-history", {
            params: type.value ? { type: type.value } : {},
        });
        rows.value = response.data.data || [];
    } finally {
        loading.value = false;
    }
};

const restore = async (row) => {
    busyId.value = row.id;
    try {
        await axios.post("deleted-history/restore", { type: row.type, id: row.id });
        rows.value = rows.value.filter((item) => !(item.type === row.type && item.id === row.id));
        toast.success(t("restored"));
    } catch {
        toast.error(t("restoreFailed"));
    } finally {
        busyId.value = null;
    }
};

onMounted(load);
</script>

<style scoped>
.history-page {
    padding: 0.25rem 0.4rem 2rem;
}
.history-bar {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 1.2rem;
}
.type-field {
    display: grid;
    gap: 0.35rem;
    min-width: 220px;
    font-size: 0.78rem;
    color: #64748b;
}
.type-field select {
    height: 40px;
    border: 0;
    border-radius: 12px;
    background: #f1f5f9;
    padding: 0 0.8rem;
    color: #0f172a;
    font: inherit;
    font-size: 0.92rem;
}
.count,
.empty {
    color: #64748b;
    font-size: 0.86rem;
}
.history-table {
    margin-top: 0.9rem;
}
.history-head,
.history-row {
    display: grid;
    grid-template-columns: 1.1fr 1.4fr 1fr auto;
    gap: 0.75rem;
    align-items: center;
    padding: 0.75rem 0.2rem;
}
.history-head {
    color: #64748b;
    font-size: 0.78rem;
}
.history-row {
    border-top: 1px solid #eef2f6;
    color: #0f172a;
}
.history-row strong {
    font-weight: 650;
}
.history-row button {
    border: 1.5px solid #0f766e;
    border-radius: 12px;
    background: #0f766e;
    color: white;
    font-weight: 600;
    padding: 0.45rem 1rem;
    cursor: pointer;
}
.history-row button:hover { background: #115e59; border-color: #115e59; }
.history-row button:disabled {
    opacity: 0.6;
    cursor: default;
}
:global(.v-theme--dark) .type-field select {
    background: #1e293b;
    color: #e2e8f0;
}
:global(.v-theme--dark) .history-row,
:global(.v-theme--dark) .history-row strong {
    color: #e2e8f0;
    border-color: #1e293b;
}
@media (max-width: 720px) {
    .history-head {
        display: none;
    }
    .history-row {
        grid-template-columns: 1fr;
    }
}
</style>
