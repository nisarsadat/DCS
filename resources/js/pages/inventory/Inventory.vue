<template>
    <div>
        <AppBar :subTitle="t('navFinance')" :main-title="t('inventory')" />

        <v-row>
            <v-col v-for="card in summaryCards" :key="card.label" cols="6" md="2">
                <v-card class="kpi" rounded="lg" elevation="0">
                    <div class="kpi-value">{{ card.value }}</div>
                    <div class="kpi-label">{{ card.label }}</div>
                </v-card>
            </v-col>
        </v-row>

        <v-card class="panel mt-2" rounded="xl" elevation="0">
            <div class="toolbar">
                <v-text-field v-model="search" :placeholder="t('searchPlaceholder')" density="compact" hide-details variant="outlined" prepend-inner-icon="mdi-magnify" style="max-width: 280px" @update:model-value="load" />
                <v-select v-model="status" :items="statusItems" item-title="title" item-value="value" density="compact" hide-details variant="outlined" style="max-width: 180px" @update:model-value="load" />
                <v-spacer />
                <v-btn variant="tonal" @click="downloadCsv">{{ t("exportCsv") }}</v-btn>
                <v-btn color="primary" prepend-icon="mdi-plus" @click="openItem()">{{ t("addItem") }}</v-btn>
            </div>

            <v-data-table
                :headers="headers"
                :items="items"
                :loading="loading"
                :items-per-page="perPage"
                hide-default-footer
            >
                <template #item.category.name="{ item }">{{ row(item).category?.name }}</template>
                <template #item.status="{ item }">
                    <v-chip size="small" :color="statusColor(row(item).status)" variant="tonal">{{ t(statusKey(row(item).status)) }}</v-chip>
                </template>
                <template #item.quantity="{ item }">{{ row(item).quantity }} {{ row(item).unit }}</template>
                <template #item.value="{ item }">{{ money(row(item).value) }}</template>
                <template #item.expires_at="{ item }">{{ row(item).expires_at ? String(row(item).expires_at).slice(0, 10) : "—" }}</template>
                <template #item.actions="{ item }">
                    <v-btn size="small" variant="text" icon="mdi-plus-box-outline" @click="openMove(row(item), 'in')" />
                    <v-btn size="small" variant="text" icon="mdi-minus-box-outline" @click="openMove(row(item), 'out')" />
                    <v-btn size="small" variant="text" icon="mdi-pencil-outline" @click="openItem(row(item))" />
                </template>
            </v-data-table>
            <div class="d-flex justify-end pa-3">
                <v-pagination v-model="page" :length="pages" density="compact" @update:model-value="load" />
            </div>
        </v-card>

        <v-card class="panel mt-4" rounded="xl" elevation="0">
            <div class="panel-title mb-3">{{ t("consumption") }}</div>
            <v-table>
                <thead>
                    <tr>
                        <th>{{ t("doctor") }}</th>
                        <th>{{ t("products") }}</th>
                        <th>{{ t("quantity") }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in consumption" :key="(row.dentist_id || 0) + '-' + row.inventory_item_id">
                        <td>{{ row.dentist?.name || "—" }}</td>
                        <td>{{ row.item?.name }}</td>
                        <td>{{ row.quantity }}</td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>

        <v-dialog v-model="itemDialog" max-width="640">
            <v-card class="pa-4">
                <v-card-title>{{ editing ? t("editItem") : t("addItem") }}</v-card-title>
                <v-row>
                    <v-col cols="12" md="6"><v-text-field v-model="form.name" :label="t('name')" /></v-col>
                    <v-col cols="12" md="6"><v-text-field v-model="form.unit" :label="t('unit')" /></v-col>
                    <v-col cols="12" md="4"><v-text-field v-model="form.sku" label="SKU" /></v-col>
                    <v-col cols="12" md="4"><v-text-field v-model="form.barcode" :label="t('barcode')" /></v-col>
                    <v-col cols="12" md="4"><v-text-field v-model="form.batch_number" :label="t('batch')" /></v-col>
                    <v-col cols="12" md="6">
                        <v-select v-model="form.inventory_category_id" :items="categories" item-title="name" item-value="id" :label="t('categoryName')" clearable />
                    </v-col>
                    <v-col cols="12" md="6"><v-text-field v-model="newCategory" :label="t('newCategory')" @keydown.enter.prevent="addCategory" /></v-col>
                    <v-col cols="6" md="3"><v-text-field v-model.number="form.quantity" type="number" :label="t('quantity')" :disabled="editing" /></v-col>
                    <v-col cols="6" md="3"><v-text-field v-model.number="form.min_stock" type="number" :label="t('minStock')" /></v-col>
                    <v-col cols="6" md="3"><v-text-field v-model.number="form.reorder_level" type="number" :label="t('reorderLevel')" /></v-col>
                    <v-col cols="6" md="3"><v-text-field v-model.number="form.purchase_price" type="number" :label="t('purchasePrice')" /></v-col>
                    <v-col cols="12" md="6"><v-text-field v-model="form.expires_at" type="date" :label="t('expires')" /></v-col>
                </v-row>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="itemDialog = false">{{ t("close") }}</v-btn>
                    <v-btn color="primary" :loading="saving" @click="saveItem">{{ t("save") }}</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="moveDialog" max-width="420">
            <v-card class="pa-4">
                <v-card-title>{{ moveForm.type }} · {{ moveForm.name }}</v-card-title>
                <v-text-field v-model.number="moveForm.quantity" type="number" :label="t('quantity')" />
                <v-text-field v-model="moveForm.note" :label="t('note')" />
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="moveDialog = false">{{ t("close") }}</v-btn>
                    <v-btn color="primary" :loading="saving" @click="saveMove">{{ t("save") }}</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue3-toastify";
import AppBar from "../../components/AppBar.vue";
import { axios } from "../../axios";

const { t } = useI18n();
const summary = ref({});
const items = ref([]);
const categories = ref([]);
const consumption = ref([]);
const search = ref("");
const status = ref("all");
const page = ref(1);
const pages = ref(1);
const perPage = 10;
const loading = ref(false);
const saving = ref(false);
const itemDialog = ref(false);
const moveDialog = ref(false);
const editing = ref(false);
const newCategory = ref("");
const form = reactive(blankItem());
const moveForm = reactive({ id: null, name: "", type: "in", quantity: 1, note: "" });

const headers = computed(() => [
    { title: t("name"), key: "name" },
    { title: "SKU", key: "sku" },
    { title: t("categoryName"), key: "category.name" },
    { title: t("quantity"), key: "quantity" },
    { title: t("status"), key: "status" },
    { title: t("purchasePrice"), key: "purchase_price" },
    { title: t("inventoryValue"), key: "value" },
    { title: t("expires"), key: "expires_at" },
    { title: "", key: "actions", sortable: false },
]);
const statusItems = computed(() => [
    { title: t("all"), value: "all" },
    { title: t("inStock"), value: "in" },
    { title: t("lowStock"), value: "low" },
    { title: t("outOfStock"), value: "out" },
    { title: t("expiringSoon"), value: "expiring" },
    { title: t("expired"), value: "expired" },
]);
const summaryCards = computed(() => [
    { label: t("items"), value: summary.value.total_items || 0 },
    { label: t("lowStock"), value: summary.value.low_stock || 0 },
    { label: t("outOfStock"), value: summary.value.out_of_stock || 0 },
    { label: t("expiringSoon"), value: summary.value.expiring || 0 },
    { label: t("expired"), value: summary.value.expired || 0 },
    { label: t("inventoryValue"), value: money(summary.value.inventory_value) },
]);

function blankItem() {
    return {
        id: null, name: "", sku: "", barcode: "", batch_number: "", unit: "unit",
        quantity: 0, min_stock: 0, reorder_level: 0, purchase_price: 0,
        expires_at: "", inventory_category_id: null, notes: "",
    };
}
const row = (item) => item?.raw || item;
const money = (value) => `${new Intl.NumberFormat("en-US").format(Number(value || 0))} AFN`;
const statusKey = (value) => ({ in: "inStock", low: "lowStock", out: "outOfStock" }[value] || "inStock");
const statusColor = (value) => ({ in: "success", low: "warning", out: "error" }[value] || "primary");

const load = async () => {
    loading.value = true;
    try {
        const [list, sum, cats, report] = await Promise.all([
            axios.get("inventory/items", { params: { search: search.value, status: status.value === "all" ? undefined : status.value, page: page.value, per_page: perPage } }),
            axios.get("inventory/summary"),
            axios.get("inventory/categories"),
            axios.get("inventory/report"),
        ]);
        items.value = list.data.data || [];
        pages.value = list.data.last_page || 1;
        summary.value = sum.data || {};
        categories.value = cats.data || [];
        consumption.value = report.data.consumption || [];
    } finally {
        loading.value = false;
    }
};

const openItem = (item = null) => {
    editing.value = Boolean(item);
    Object.assign(form, blankItem(), item || {});
    if (item?.expires_at) form.expires_at = String(item.expires_at).slice(0, 10);
    itemDialog.value = true;
};

const saveItem = async () => {
    saving.value = true;
    try {
        if (editing.value) await axios.put(`inventory/items/${form.id}`, form);
        else await axios.post("inventory/items", form);
        itemDialog.value = false;
        toast.success(t("saved"));
        await load();
    } catch (error) {
        toast.error(error.response?.data?.message || t("saveFailed"));
    } finally {
        saving.value = false;
    }
};

const addCategory = async () => {
    if (!newCategory.value.trim()) return;
    const response = await axios.post("inventory/categories", { name: newCategory.value.trim() });
    categories.value.push(response.data);
    form.inventory_category_id = response.data.id;
    newCategory.value = "";
};

const openMove = (item, type) => {
    Object.assign(moveForm, { id: item.id, name: item.name, type, quantity: 1, note: "" });
    moveDialog.value = true;
};

const saveMove = async () => {
    saving.value = true;
    try {
        await axios.post(`inventory/items/${moveForm.id}/move`, {
            type: moveForm.type,
            quantity: moveForm.quantity,
            note: moveForm.note,
        }, { headers: { "Idempotency-Key": `${moveForm.id}-${Date.now()}` } });
        moveDialog.value = false;
        toast.success(t("saved"));
        await load();
    } catch (error) {
        const message = error.response?.data?.errors?.quantity?.[0] || error.response?.data?.message || t("saveFailed");
        toast.error(message);
    } finally {
        saving.value = false;
    }
};

const downloadCsv = async () => {
    const report = await axios.get("inventory/report");
    const rows = report.data.items || [];
    const header = Object.keys(rows[0] || { name: "" });
    const csv = [header.join(","), ...rows.map((row) => header.map((key) => `"${row[key] ?? ""}"`).join(","))].join("\n");
    const blob = new Blob([csv], { type: "text/csv;charset=utf-8" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "inventory.csv";
    link.click();
};

onMounted(load);
</script>

<style scoped>
.kpi, .panel { border: 1px solid #e2e8f0; background: #fff; }
.kpi { padding: 0.85rem 1rem; }
.kpi-value { font-weight: 700; font-size: 1.15rem; color: #0f172a; }
.kpi-label { color: #64748b; font-size: 0.78rem; }
.panel { padding: 0.75rem; }
.toolbar { display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap; padding: 0.4rem 0.4rem 0.8rem; }
.panel-title { font-weight: 700; }
</style>
