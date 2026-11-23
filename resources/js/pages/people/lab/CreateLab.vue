<template>
    <LabOrderForm
        ref="orderForm"
        :main-title="t('createLaboratory')"
        :sub-title="t('outboundLaboratory')"
        :form="formData"
        :lines="lines"
        :teeth="PeopleRepository.dentalsFor"
        :accounts="accounts"
        :rules="rules"
        :total="totalSum"
        :due="due"
        :saving="saving"
        @toggle="toggleService"
        @remove="removeProduct"
        @submit="createEarning"
    >
        <template #parties>
            <div class="lab-field">
                <span class="lab-label">{{ t("supplier") }} *</span>
                <v-autocomplete
                    v-model="formData.supplierId"
                    :items="PeopleRepository.suppliersFor"
                    :return-object="false"
                    variant="outlined"
                    density="compact"
                    hide-details="auto"
                    item-value="id"
                    item-title="name"
                    :rules="[rules.required]"
                />
            </div>
        </template>
    </LabOrderForm>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue3-toastify";
import LabOrderForm from "../../../components/LabOrderForm.vue";
import { usePeopleRepository } from "@/store/PeopleRepository";

const { t } = useI18n();
const notify = (message) =>
    toast.error(message, {
        position: "top-center",
        rtl: document.documentElement.dir === "rtl",
    });
const PeopleRepository = usePeopleRepository();
const orderForm = ref(null);
const saving = ref(false);

const formData = reactive({
    tooths: [],
    grandTotal: 0,
    supplierId: null,
    returnDate: "",
    issueAt: "",
    description: "",
    paid: 0,
    moneyAccountId: null,
    type: "out",
});

const lines = computed(() =>
    Array.isArray(PeopleRepository.services) ? PeopleRepository.services : []
);

const accounts = computed(() =>
    Array.isArray(PeopleRepository.account) ? PeopleRepository.account : []
);

const lineTotal = (line) => {
    const total = (Number(line?.quantity) || 0) * (Number(line?.cost) || 0);
    return Number.isFinite(total) ? total : 0;
};

const totalSum = computed(() =>
    lines.value.reduce((sum, line) => sum + lineTotal(line), 0)
);

const due = computed(() =>
    Math.max(totalSum.value - (Number(formData.paid) || 0), 0)
);

const rules = {
    required: (value) =>
        (value !== null && value !== undefined && value !== "") ||
        t("validation.required"),
    positive: (value) =>
        value === "" ||
        value === null ||
        value === undefined ||
        Number(value) >= 0 ||
        t("validation.number"),
    maxPaid: (value) =>
        Number(value || 0) <= totalSum.value || t("validation.payMoreThanTotal"),
};

const toggleService = (service) => {
    const index = PeopleRepository.services.findIndex(
        (item) => item.id === service.id
    );
    if (index === -1) {
        PeopleRepository.services.push({
            ...service,
            quantity: Number(service.quantity) || 1,
            cost: Number(service.cost) || 0,
            toothId: service.toothId || service.id,
        });
        return;
    }
    PeopleRepository.services.splice(index, 1);
};

const removeProduct = (index) => {
    PeopleRepository.services.splice(index, 1);
};

const createEarning = async () => {
    if (!lines.value.length) {
        notify(t("noTeethAdded"));
        return;
    }
    if (Number(formData.paid || 0) > totalSum.value) {
        notify(t("validation.payMoreThanTotal"));
        return;
    }

    const result = await orderForm.value?.validate();
    if (result?.valid === false) {
        notify(t("validation.fillAll"));
        return;
    }

    formData.tooths = lines.value.map((item) => ({
        ...item,
        toothId: item.toothId || item.id,
        quantity: Number(item.quantity) || 0,
        cost: Number(item.cost) || 0,
        total: lineTotal(item),
    }));
    formData.grandTotal = totalSum.value;
    formData.paid = Number(formData.paid) || 0;

    saving.value = true;
    const ok = await PeopleRepository.CreateLaboratory(formData);
    saving.value = false;
    if (!ok) return;

    PeopleRepository.services = [];
    formData.description = "";
    formData.paid = 0;
    formData.grandTotal = 0;
    formData.tooths = [];
};

watch(
    accounts,
    (list) => {
        if (!formData.moneyAccountId && list.length) {
            formData.moneyAccountId = list[0].id;
        }
    },
    { immediate: true }
);

PeopleRepository.services = [];
formData.returnDate = PeopleRepository.getTodaysDate();
formData.issueAt = PeopleRepository.getTodaysDate();
PeopleRepository.FetchSuppliersFor();
PeopleRepository.FetchDentals();
PeopleRepository.fetchAccountDataForCreate();
</script>
