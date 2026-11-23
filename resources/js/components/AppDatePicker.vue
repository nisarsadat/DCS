<template>
    <div :class="isMiladi ? 'app-date-miladi' : 'app-date-shamsi'">
        <PersianDatePicker :key="dateType" v-bind="pickerAttrs" />
    </div>
</template>

<script setup>
import { computed, useAttrs } from "vue";
import PersianDatePicker from "@alireza-ab/vue3-persian-datepicker";
import { storeToRefs } from "pinia";
import { LocaleConfigs, MiladiLocaleConfigs } from "@/LocaleConfigs";
import { useSettingRepository } from "@/store/SettingRepository";

defineOptions({ inheritAttrs: false });

const attrs = useAttrs();
const { dateType } = storeToRefs(useSettingRepository());
const isMiladi = computed(() => dateType.value === "gregorian");

const toMiladiFormat = (format) => {
    if (typeof format !== "string") return format;
    return format
        .replace(/jYYYY/g, "YYYY")
        .replace(/jYY/g, "YY")
        .replace(/jMM/g, "MM")
        .replace(/jDD/g, "DD")
        .replace(/jM/g, "M")
        .replace(/jD/g, "D");
};

const pickerAttrs = computed(() => {
    const next = { ...attrs };
    delete next.localeConfig;
    delete next["locale-config"];
    delete next["input-format"];
    delete next.displayFormat;
    delete next["display-format"];

    const miladi = isMiladi.value;
    next.localeConfig = miladi ? MiladiLocaleConfigs : LocaleConfigs;

    const original = String(
        next.format || next.inputFormat || attrs["input-format"] || ""
    );
    const type = next.type || "date";
    const hasTime =
        type === "datetime" || type === "time" || /[Hh]/.test(original);

    if (type === "time") {
        next.inputFormat = original || "HH:mm";
        next.displayFormat = "HH:mm";
    } else if (miladi) {
        next.inputFormat = hasTime ? "YYYY/MM/DD HH:mm" : "YYYY/MM/DD";
        next.displayFormat = next.inputFormat;
        if (typeof next.format === "string") {
            next.format = toMiladiFormat(next.format);
        }
    } else {
        next.inputFormat = hasTime ? "jYYYY/jMM/jDD HH:mm" : "jYYYY/jMM/jDD";
        next.displayFormat = next.inputFormat;
    }

    return next;
});
</script>

<style scoped>
.app-date-miladi :deep(.pdp-picker),
.app-date-shamsi :deep(.pdp-picker) {
    direction: ltr;
}

.app-date-miladi :deep(.pdp-day.friday:not(.start-range):not(.end-range)) {
    color: inherit !important;
}
</style>
