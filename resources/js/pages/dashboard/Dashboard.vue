<template>
    <div>
        <AppBar :subTitle="t('dashboard')" :main-title="greeting" />

        <div class="dash-toolbar">
            <div class="period-bar" role="tablist">
                <button
                    v-for="item in periods"
                    :key="item.value"
                    type="button"
                    class="period-item"
                    :class="{ 'is-active': range === item.value }"
                    @click="range = item.value"
                >
                    {{ t(item.label) }}
                </button>
            </div>
            <div v-if="range === 'custom'" class="custom-range">
                <v-text-field v-model="from" type="date" density="compact" hide-details variant="outlined" />
                <v-text-field v-model="to" type="date" density="compact" hide-details variant="outlined" />
                <v-btn color="primary" variant="flat" @click="load">{{ t("view") }}</v-btn>
            </div>
        </div>

        <v-row>
            <v-col v-for="card in cards" :key="card.label" cols="12" sm="6" lg="3">
                <v-card class="stat-card" rounded="xl" elevation="0">
                    <div class="stat-icon">
                        <v-icon color="primary">{{ card.icon }}</v-icon>
                    </div>
                    <div>
                        <div class="stat-value">{{ card.value }}</div>
                        <div class="stat-label">{{ card.label }}</div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <v-row>
            <v-col cols="12" lg="8">
                <v-card class="panel" rounded="xl" elevation="0">
                    <div class="panel-head">
                        <div>
                            <div class="panel-title">{{ t("financialSnapshot") }}</div>
                            <div class="stat-label">{{ money(report.periodProfit) }} {{ t("profit") }}</div>
                        </div>
                    </div>
                    <div ref="chartEl" class="chart"></div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="4">
                <v-card class="panel" rounded="xl" elevation="0">
                    <div class="panel-head">
                        <div class="panel-title">{{ t("quickActions") }}</div>
                    </div>
                    <div class="quick-grid">
                        <v-btn v-for="action in actions" :key="action.to" :to="action.to" variant="tonal" color="primary">
                            <v-icon start>{{ action.icon }}</v-icon>{{ action.label }}
                        </v-btn>
                    </div>
                    <v-divider class="my-4" />
                    <div class="panel-title mb-2">{{ t("recentReminders") }}</div>
                    <v-list density="compact">
                        <v-list-item v-if="!reminders.length" :title="t('noNotifications')" />
                        <v-list-item
                            v-for="item in reminders"
                            :key="item.id"
                            :title="item.title"
                            :subtitle="item.body"
                            :to="item.link"
                        />
                    </v-list>
                </v-card>
            </v-col>
        </v-row>

        <v-row>
            <v-col cols="12" md="7">
                <v-card class="panel" rounded="xl" elevation="0">
                    <div class="panel-title mb-3">{{ t("upcomingAppointment") }}</div>
                    <v-table v-if="report.upcomingAppointments?.length">
                        <thead>
                            <tr>
                                <th>{{ t("patient") }}</th>
                                <th>{{ t("time") }}</th>
                                <th>{{ t("phone") }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in report.upcomingAppointments" :key="item.name + item.time">
                                <td>{{ item.name }}</td>
                                <td>{{ item.time }}</td>
                                <td>{{ item.phone }}</td>
                            </tr>
                        </tbody>
                    </v-table>
                    <div v-else class="empty">{{ t("noNotifications") }}</div>
                </v-card>
            </v-col>
            <v-col cols="12" md="5">
                <v-card class="panel" rounded="xl" elevation="0">
                    <div class="panel-title mb-3">{{ t("expensesBasedOnAmountAndPercentage") }}</div>
                    <div v-if="!expenses.length" class="empty">{{ t("noNotifications") }}</div>
                    <div v-for="expense in expenses" :key="expense.categoryName" class="mb-3">
                        <div class="d-flex justify-space-between text-caption mb-1">
                            <span>{{ expense.categoryName }}</span>
                            <span>{{ money(expense.totalExpense) }}</span>
                        </div>
                        <v-progress-linear :model-value="Number(expense.percentage) || 0" color="primary" rounded height="8" />
                    </div>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import * as echarts from "echarts";
import AppBar from "../../components/AppBar.vue";
import { axios } from "../../axios";
import { useDashboardRepository } from "@/store/DashboardRepository";
import { useAuthRepository } from "@/store/AuthRepository";

const { t } = useI18n();
const DashboardRepository = useDashboardRepository();
const AuthRepository = useAuthRepository();
const periods = [
    { value: "today", label: "today" },
    { value: "week", label: "periodWeek" },
    { value: "month", label: "thisMonth" },
    { value: "year", label: "thisYear" },
    { value: "custom", label: "periodCustom" },
];
const range = ref("month");
const from = ref("");
const to = ref("");
const reminders = ref([]);
const chartEl = ref(null);
let chart;

const report = computed(() => DashboardRepository.dashboardReport || {});
const greeting = computed(() => {
    const name = AuthRepository.user?.name || "";
    return name ? `${t("welcomeBack")}, ${name}` : t("welcomeBack");
});

const money = (value) => {
    const amount = Number(value || 0);
    return `${new Intl.NumberFormat("en-US", { maximumFractionDigits: 0 }).format(amount)} AFN`;
};

const cards = computed(() => [
    { label: t("patients"), value: report.value.totalPatients || 0, icon: "mdi-account-group-outline" },
    { label: t("newPatients"), value: report.value.monthNewPatients || report.value.newPatients || 0, icon: "mdi-account-plus-outline" },
    { label: t("appointmentsToday"), value: report.value.todayAppointments || 0, icon: "mdi-calendar-check-outline" },
    { label: t("waitingPatients"), value: report.value.waitingPatients || 0, icon: "mdi-account-clock-outline" },
    { label: t("activeTreatments"), value: report.value.activeTreatments || 0, icon: "mdi-tooth-outline" },
    { label: t("outstanding"), value: money(report.value.outstanding), icon: "mdi-cash-clock" },
    { label: t("pendingLabs"), value: report.value.pendingLabs || 0, icon: "mdi-flask-outline" },
    { label: t("lowStock"), value: `${report.value.lowStock || 0} / ${report.value.outOfStock || 0}`, icon: "mdi-package-variant" },
]);

const actions = computed(() => [
    { to: "/patients", label: t("patients"), icon: "mdi-account-plus" },
    { to: "/appointments", label: t("appointment"), icon: "mdi-calendar-plus" },
    { to: "/createCure", label: t("cureCycle"), icon: "mdi-tooth" },
    { to: "/inventory", label: t("inventory"), icon: "mdi-package-variant-closed" },
]);

const expenses = computed(() => {
    const rows = report.value.monthlyExpenses || [];
    const total = rows.reduce((sum, row) => sum + Number(row.totalExpense || 0), 0) || 1;
    return rows.map((row) => ({
        ...row,
        percentage: row.percentage || (Number(row.totalExpense || 0) / total) * 100,
    }));
});

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

const draw = () => {
    if (!chartEl.value) return;
    chart = chart || echarts.init(chartEl.value);
    chart.setOption({
        tooltip: { trigger: "axis" },
        legend: { data: [t("sales"), t("expense")], top: 0 },
        grid: { left: 16, right: 16, bottom: 8, top: 36, containLabel: true },
        xAxis: { type: "category", data: months },
        yAxis: { type: "value" },
        series: [
            { name: t("sales"), type: "bar", data: report.value.monthIncomes || [], itemStyle: { color: "#0F766E", borderRadius: [6, 6, 0, 0] } },
            { name: t("expense"), type: "bar", data: report.value.monthExpenses || [], itemStyle: { color: "#94A3B8", borderRadius: [6, 6, 0, 0] } },
        ],
    });
    chart.resize();
};

const load = async () => {
    const params = { range: range.value };
    if (range.value === "custom") {
        params.from = from.value;
        params.to = to.value;
    }
    await DashboardRepository.fetchDashboardData(params);
    const notes = await axios.get("notifications", { params: { limit: 4, filter: "urgent" } });
    reminders.value = notes.data.data || [];
    await nextTick();
    draw();
};

watch(range, (value) => {
    if (value !== "custom") load();
});
watch(() => report.value.monthIncomes, draw);

onMounted(() => {
    load();
    window.addEventListener("resize", draw);
});
</script>

<style scoped>
.dash-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
    margin-bottom: 1rem;
}
.period-bar {
    display: inline-flex;
    align-items: center;
    gap: 0.15rem;
    padding: 0.25rem;
    background: #f1f5f9;
    border-radius: 14px;
}
.period-item {
    border: 0;
    background: transparent;
    color: #64748b;
    font: inherit;
    font-size: 0.84rem;
    font-weight: 600;
    line-height: 1;
    white-space: nowrap;
    padding: 0.55rem 0.95rem;
    border-radius: 11px;
    cursor: pointer;
}
.period-item.is-active {
    background: #fff;
    color: #0f766e;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
}
.custom-range {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.custom-range :deep(.v-input) {
    width: 160px;
}
:global(.v-theme--dark) .period-bar {
    background: #1e293b;
}
:global(.v-theme--dark) .period-item {
    color: #cbd5e1;
}
:global(.v-theme--dark) .period-item.is-active {
    background: #0f766e;
    color: #fff;
}
.stat-card,
.panel {
    border: 1px solid #e2e8f0;
    background: #fff;
}
.stat-card {
    display: flex;
    gap: 0.85rem;
    align-items: center;
    padding: 1rem 1.1rem;
}
.stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: #f0fdfa;
    display: grid;
    place-items: center;
}
.stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
}
.stat-label,
.empty {
    color: #64748b;
    font-size: 0.82rem;
}
.panel {
    padding: 1rem 1.1rem 1.1rem;
    height: 100%;
}
.panel-title {
    font-weight: 700;
    color: #0f172a;
}
.chart {
    height: 280px;
}
.quick-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}
.gap-2 { gap: 0.5rem; }
</style>
