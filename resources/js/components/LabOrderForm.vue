<template>
    <div class="lab-order" :dir="dir">
        <section class="lab-card">
            <AppBar :mainTitle="mainTitle" :subTitle="subTitle" />

            <v-form ref="formRef" class="lab-form" @submit.prevent="emit('submit')">
                <div class="lab-grid">
                    <div class="lab-field">
                        <span class="lab-label">{{ t("issueAt") }}</span>
                        <date-picker
                            mode="single"
                            :column="1"
                            v-model="form.issueAt"
                            locale="fa"
                            type="date"
                            format="YYYY-MM-DD"
                            :locale-config="LocaleConfigs"
                        />
                    </div>
                    <div class="lab-field">
                        <span class="lab-label">{{ t("returnDate") }}</span>
                        <date-picker
                            mode="single"
                            :column="1"
                            v-model="form.returnDate"
                            locale="fa"
                            type="date"
                            format="YYYY-MM-DD"
                            :locale-config="LocaleConfigs"
                        />
                    </div>
                    <slot name="parties" />
                </div>

                <section class="lab-lines">
                    <div class="lab-lines-head">
                        <div>
                            <h3>{{ t("teethType") }}</h3>
                            <p>{{ t("searchTeethHint") }}</p>
                        </div>
                        <span class="count-pill">{{ lines.length }}</span>
                    </div>

                    <v-text-field
                        v-model="toothQuery"
                        :placeholder="t('search')"
                        prepend-inner-icon="mdi-magnify"
                        variant="outlined"
                        density="compact"
                        hide-details
                        clearable
                        class="tooth-search"
                        @keydown.enter.prevent
                    />

                    <div class="tooth-tray">
                        <div
                            v-for="service in filteredTeeth"
                            :key="service.id"
                            class="tooth-chip"
                            :class="{ on: isSelected(service.id) }"
                            role="button"
                            tabindex="0"
                            @click="emit('toggle', service)"
                            @keydown.enter.prevent="emit('toggle', service)"
                        >
                            <span>{{ service.name }}</span>
                            <DentalImages
                                :images="service.images || []"
                                :title="service.name"
                            />
                        </div>
                        <p v-if="toothQuery && filteredTeeth.length === 0" class="tooth-empty">
                            {{ t("noResults") }}
                        </p>
                    </div>

                    <div class="table-wrap">
                        <table class="lab-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ t("teethType") }}</th>
                                    <th>{{ t("qty") }}</th>
                                    <th>{{ t("cost") }}</th>
                                    <th>{{ t("subTotal") }}</th>
                                    <th>{{ t("action") }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="lines.length === 0">
                                    <td colspan="6" class="table-empty">
                                        {{ t("noTeethAdded") }}
                                    </td>
                                </tr>
                                <tr v-for="(line, index) in lines" :key="line.id || index">
                                    <td class="num">{{ index + 1 }}</td>
                                    <td>
                                        <span class="line-name">
                                            {{ line.name }}
                                            <DentalImages
                                                :images="line.images || []"
                                                :title="line.name"
                                            />
                                        </span>
                                    </td>
                                    <td class="qty">
                                        <v-text-field
                                            v-model.number="line.quantity"
                                            type="number"
                                            min="0"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            :rules="[rules.required, rules.positive]"
                                        />
                                    </td>
                                    <td class="cost">
                                        <v-text-field
                                            v-model.number="line.cost"
                                            type="number"
                                            min="0"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            :rules="[rules.required, rules.positive]"
                                        >
                                            <template #append-inner>
                                                <span class="unit">{{ t("afn") }}</span>
                                            </template>
                                        </v-text-field>
                                    </td>
                                    <td class="sub">{{ money(lineTotal(line)) }}</td>
                                    <td class="act">
                                        <v-btn
                                            icon="mdi-trash-can-outline"
                                            variant="text"
                                            color="error"
                                            size="small"
                                            @click="emit('remove', index)"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="lab-bottom">
                    <v-textarea
                        v-model="form.description"
                        :label="t('details')"
                        variant="outlined"
                        density="compact"
                        rows="5"
                        auto-grow
                        class="lab-notes"
                    />

                    <aside class="lab-summary">
                        <div class="sum-total">
                            <span>{{ t("total") }}</span>
                            <strong>{{ money(total) }} <small>{{ t("afn") }}</small></strong>
                        </div>

                        <v-select
                            v-model="form.moneyAccountId"
                            :items="accounts"
                            item-title="name"
                            item-value="id"
                            :label="t('moneyAccount') + ' *'"
                            variant="outlined"
                            density="compact"
                            hide-details="auto"
                            :rules="[rules.required]"
                        />

                        <v-text-field
                            v-model.number="form.paid"
                            type="number"
                            min="0"
                            :label="t('paid')"
                            variant="outlined"
                            density="compact"
                            hide-details="auto"
                            :rules="[rules.positive, rules.maxPaid]"
                        >
                            <template #append-inner>
                                <span class="unit">{{ t("afn") }}</span>
                            </template>
                        </v-text-field>

                        <div class="sum-due">
                            <span>{{ t("due") }}</span>
                            <strong>{{ money(due) }} <small>{{ t("afn") }}</small></strong>
                        </div>

                        <v-btn
                            class="save-btn"
                            type="submit"
                            block
                            :loading="saving"
                        >
                            {{ t("submit") }}
                        </v-btn>
                    </aside>
                </div>
            </v-form>
        </section>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import AppBar from "./AppBar.vue";
import DentalImages from "./DentalImages.vue";
import { LocaleConfigs } from "../LocaleConfigs";

const props = defineProps({
    mainTitle: { type: String, default: "" },
    subTitle: { type: String, default: "" },
    form: { type: Object, required: true },
    lines: { type: Array, default: () => [] },
    teeth: { type: Array, default: () => [] },
    accounts: { type: Array, default: () => [] },
    rules: { type: Object, required: true },
    total: { type: Number, default: 0 },
    due: { type: Number, default: 0 },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(["toggle", "remove", "submit"]);

const { t, locale } = useI18n();
const formRef = ref(null);
const toothQuery = ref("");

const dir = computed(() =>
    locale.value === "fa" || locale.value === "pa" ? "rtl" : "ltr"
);

const filteredTeeth = computed(() => {
    const query = String(toothQuery.value || "").trim().toLowerCase();
    const teeth = Array.isArray(props.teeth) ? props.teeth : [];
    if (!query) return teeth;
    return teeth.filter((tooth) =>
        String(tooth?.name || "").toLowerCase().includes(query)
    );
});

const isSelected = (id) => props.lines.some((line) => line.id === id);

const lineTotal = (line) => {
    const total = (Number(line?.quantity) || 0) * (Number(line?.cost) || 0);
    return Number.isFinite(total) ? total : 0;
};

const money = (value) =>
    (Number(value) || 0).toLocaleString(undefined, {
        maximumFractionDigits: 2,
    });

defineExpose({
    validate: () => formRef.value?.validate(),
});
</script>
