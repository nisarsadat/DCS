<template>
  <div ref="printContent" class="appt-slip" :dir="dir">
    <component :is="'style'">
      .appt-slip{width:118mm;margin:0 auto;padding:5mm 5.5mm 4.5mm;box-sizing:border-box;background:#fff;color:#1b3144;border:1px solid #d7e3ec;border-radius:4mm;font-family:Tahoma,"Segoe UI",sans-serif;font-size:11px;line-height:1.4}
      .appt-head{display:flex;align-items:center;gap:3mm;padding-bottom:3mm;border-bottom:1px solid #e6eef3}
      .appt-logo{width:11mm;height:11mm;object-fit:contain;flex:0 0 auto}
      .appt-clinic{font-size:14px;font-weight:700;line-height:1.3}
      .appt-contact{margin-top:.6mm;color:#607484;font-size:10px}
      .appt-kicker{margin:3.2mm 0 2.6mm;text-align:center;color:#0f5f86;font-size:11px;font-weight:700}
      .appt-when{display:grid;grid-template-columns:1fr 1fr;gap:2mm;margin-bottom:2.5mm}
      .appt-when > div{background:#f4f8fb;border-radius:2mm;padding:2mm 2.5mm}
      .appt-when span,.appt-rows span{display:block;color:#6b7f8e;font-size:9px;font-weight:400}
      .appt-when strong{display:block;margin-top:.4mm;font-size:13px}
      .appt-rows > div{display:flex;justify-content:space-between;align-items:baseline;gap:4mm;padding:1.7mm 0;border-top:1px solid #eef3f6}
      .appt-rows strong{font-size:12px;text-align:end}
      .appt-status{color:#0f5f86}
    </component>
    <header class="appt-head">
      <img v-if="logo" :src="logo" class="appt-logo" alt="" />
      <div>
        <div class="appt-clinic">{{ clinicName }}</div>
        <div v-if="clinicPhone || clinicAddress" class="appt-contact">
          <span v-if="clinicPhone">{{ clinicPhone }}</span>
          <span v-if="clinicPhone && clinicAddress"> · </span>
          <span v-if="clinicAddress">{{ clinicAddress }}</span>
        </div>
      </div>
    </header>

    <div class="appt-kicker">{{ t("appointment") }}</div>

    <div class="appt-when">
      <div>
        <span>{{ t("date") }}</span>
        <strong>{{ formatDisplayDate(appointment?.date) || "—" }}</strong>
      </div>
      <div>
        <span>{{ t("time") }}</span>
        <strong>{{ appointment?.time || "—" }}</strong>
      </div>
    </div>

    <div class="appt-rows">
      <div>
        <span>{{ t("patient") }}</span>
        <strong>{{ appointment?.patients?.name || "—" }}</strong>
      </div>
      <div>
        <span>{{ t("doctor") }}</span>
        <strong>{{ appointment?.dentists?.name || "—" }}</strong>
      </div>
      <div>
        <span>{{ t("status") }}</span>
        <strong class="appt-status">{{ statusText }}</strong>
      </div>
      <div v-if="appointment?.userName">
        <span>{{ t("addedBy") }}</span>
        <strong>{{ appointment.userName }}</strong>
      </div>
    </div>
  </div>
</template>

<script setup>
import { formatDisplayDate } from "@/formatDate";
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useSettingRepository } from "@/store/SettingRepository";

const props = defineProps({
  appointment: Object,
});

const { t, locale } = useI18n();
const settings = useSettingRepository();
const printContent = ref(null);

const clinicName = computed(
  () => settings.systemSettings?.name || t("clinicName")
);
const logo = computed(() => settings.systemSettings?.photo || "");
const clinicPhone = computed(() => settings.systemSettings?.phone || "");
const clinicAddress = computed(() => settings.systemSettings?.address || "");
const dir = computed(() => (locale.value === "en" ? "ltr" : "rtl"));
const statusText = computed(() => {
  const value = props.appointment?.status;
  if (!value) return "—";
  const label = t(value);
  return label === value ? value : label;
});

defineExpose({ printContent });
</script>
