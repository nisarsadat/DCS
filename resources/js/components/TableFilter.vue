<template>
    <v-expand-transition>
        <div v-if="open" class="filter-bar">
            <div class="filter-head">
                <div class="filter-title">
                    <v-icon size="18" color="primary">mdi-filter-variant</v-icon>
                    <span>{{ t("filter") }}</span>
                    <span v-if="activeCount" class="filter-badge">{{ activeCount }}</span>
                </div>
                <div class="filter-head-side">
                    <span class="filter-meta">{{ t("filterMatches", { count: filtered.length }) }}</span>
                    <button type="button" class="filter-clear" :disabled="!hasSelection" @click="clear">
                        {{ t("clear") }}
                    </button>
                </div>
            </div>

            <div class="filter-grid">
                <label
                    v-for="field in fields"
                    :key="field.key"
                    class="filter-control"
                    :class="{ 'is-set': selected[field.key] }"
                >
                    <span>{{ t(field.label) }}</span>
                    <select v-model="selected[field.key]">
                        <option value="">{{ t("all") }}</option>
                        <option v-for="option in optionsFor(field.key)" :key="option.value" :value="option.value">
                            {{ option.title }}
                        </option>
                    </select>
                </label>
            </div>

            <div v-if="chips.length" class="filter-chips">
                <button
                    v-for="chip in chips"
                    :key="chip.key"
                    type="button"
                    class="filter-chip"
                    @click="selected[chip.key] = ''"
                >
                    <span>{{ chip.label }}: {{ chip.value }}</span>
                    <v-icon size="14">mdi-close</v-icon>
                </button>
            </div>
        </div>
    </v-expand-transition>
</template>

<script setup>
import { computed, reactive, watch } from "vue";
import { useI18n } from "vue-i18n";
import { formatDisplayDate } from "@/formatDate";
import { useSettingRepository } from "@/store/SettingRepository";
import { storeToRefs } from "pinia";

const open = defineModel({ type: Boolean, default: false });
const result = defineModel("result", { default: null });

const props = defineProps({
    source: { type: Array, default: () => [] },
    fields: { type: Array, default: () => [] },
});

const { t } = useI18n();
const { dateType } = storeToRefs(useSettingRepository());
const selected = reactive({});

const dateKeys = new Set(["date", "start_date", "issueAt", "returnDate"]);

watch(
    () => props.fields,
    (fields) => {
        for (const field of fields || []) {
            if (selected[field.key] == null) selected[field.key] = "";
        }
    },
    { immediate: true }
);

const readPath = (row, key) =>
    key.split(".").reduce((current, part) => current?.[part], row);

const cellLabel = (row, key) => {
    dateType.value;
    const value = readPath(row, key);
    if (value == null || value === "") return "";
    if (typeof value === "boolean" || key === "status") {
        const active = value === true || value === 1 || value === "1";
        const inactive = value === false || value === 0 || value === "0";
        if (active || inactive) return active ? t("active") : t("inactive");
    }
    if (typeof value === "object") {
        return value.name ? String(value.name) : "";
    }
    if (dateKeys.has(key)) {
        return String(formatDisplayDate(value) ?? value);
    }
    return String(value);
};

const optionsFor = (key) => {
    const seen = new Set();
    const options = [];
    for (const row of props.source || []) {
        const label = cellLabel(row, key);
        if (!label || seen.has(label)) continue;
        seen.add(label);
        options.push({ title: label, value: label });
    }
    return options.sort((a, b) => a.title.localeCompare(b.title));
};

const chips = computed(() =>
    (props.fields || [])
        .filter((field) => selected[field.key])
        .map((field) => ({
            key: field.key,
            label: t(field.label),
            value: selected[field.key],
        }))
);

const activeCount = computed(() => chips.value.length);
const hasSelection = computed(() => activeCount.value > 0);

const filtered = computed(() => {
    const rows = Array.isArray(props.source) ? props.source : [];
    if (!chips.value.length) return rows;
    return rows.filter((row) =>
        chips.value.every((chip) => cellLabel(row, chip.key) === chip.value)
    );
});

watch(
    filtered,
    (rows) => {
        result.value = rows;
    },
    { immediate: true }
);

const clear = () => {
    Object.keys(selected).forEach((key) => {
        selected[key] = "";
    });
};
</script>

<style scoped>
.filter-bar {
    margin: 0 0 0.9rem;
    padding: 0.85rem 1rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #f8fafc;
}
.filter-head,
.filter-head-side,
.filter-title,
.filter-chips {
    display: flex;
    align-items: center;
}
.filter-head {
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}
.filter-title {
    gap: 0.4rem;
    font-size: 0.92rem;
    font-weight: 700;
    color: #0f172a;
}
.filter-badge {
    min-width: 1.25rem;
    height: 1.25rem;
    padding: 0 0.35rem;
    border-radius: 999px;
    background: #0f766e;
    color: #fff;
    font-size: 0.72rem;
    display: inline-grid;
    place-items: center;
}
.filter-head-side {
    gap: 0.75rem;
}
.filter-meta {
    color: #64748b;
    font-size: 0.78rem;
}
.filter-clear {
    border: 0;
    background: transparent;
    color: #0f766e;
    font: inherit;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
}
.filter-clear:disabled {
    color: #cbd5e1;
    cursor: default;
}
.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.75rem;
}
.filter-control {
    display: flex;
    flex-direction: column;
    gap: 0.28rem;
    min-width: 0;
}
.filter-control span {
    font-size: 0.74rem;
    font-weight: 700;
    color: #64748b;
}
.filter-control select {
    height: 38px;
    width: 100%;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%2364748b' d='M1.4.6 6 5.2 10.6.6 12 2 6 8 0 2z'/%3E%3C/svg%3E") no-repeat right 0.7rem center;
    padding: 0 1.7rem 0 0.75rem;
    color: #0f172a;
    font: inherit;
    font-size: 0.86rem;
    outline: none;
    appearance: none;
}
.filter-control select:dir(rtl) {
    background-position: left 0.7rem center;
    padding: 0 0.75rem 0 1.7rem;
}
.filter-control select:focus {
    border-color: #0f766e;
    box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.12);
}
.filter-control.is-set select {
    border-color: #99f6e4;
    background-color: #f0fdfa;
}
.filter-chips {
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-top: 0.8rem;
}
.filter-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    border: 0;
    border-radius: 999px;
    background: #ccfbf1;
    color: #115e59;
    padding: 0.28rem 0.55rem 0.28rem 0.7rem;
    font: inherit;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
}
.filter-chip:dir(rtl) {
    padding: 0.28rem 0.7rem 0.28rem 0.55rem;
}
:global(.v-theme--dark) .filter-bar {
    background: #1e293b;
    border-color: #334155;
}
:global(.v-theme--dark) .filter-title,
:global(.v-theme--dark) .filter-control select {
    color: #e2e8f0;
}
:global(.v-theme--dark) .filter-control select,
:global(.v-theme--dark) .filter-control.is-set select {
    background-color: #0f172a;
    border-color: #334155;
}
:global(.v-theme--dark) .filter-chip {
    background: #134e4a;
    color: #99f6e4;
}
</style>
