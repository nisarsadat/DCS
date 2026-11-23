<template>
  <div
    ref="printContent"
    class="p-6 bg-white text-black min-h-[100vh] flex flex-col justify-between"
   
  >
    <!-- Content Wrapper (Header + Patient Info + Records) -->
    <div>
      <!-- Header -->
      <div class="flex flex-col items-center border-b pb-4 text-center">
        <img src="/assets/logo.jpg" class="h-16 mb-2" alt="Logo" />
        <div>
          <h2 class="font-bold text-xl">{{ $t('clinicName') }}</h2>
        </div>
      </div>

      <!-- Title -->
      <div class="flex justify-center pb-2">
        <h2 class="font-bold text-xl text-primaryOld">
          {{ $t('patientForm') }}
        </h2>
      </div>

      <!-- Patient Info -->
      <div class="grid grid-cols-2 gap-4 mb-6 text-sm text-primaryOld">
        <div><strong>{{ $t('name') }}:</strong> {{ patient.name }}</div>
        <div><strong>{{ $t('phone') }}:</strong> {{ patient.phone }}</div>
        <div><strong>{{ $t('address') }}:</strong> {{ patient.address }}</div>
        <div><strong>{{ $t('dateOfBirth') }}:</strong> {{ patient.dateOfBirth }}</div>
        <div><strong>{{ $t('gender') }}:</strong> {{ patient.gender }}</div>
      </div>

      <!-- Medical Record -->
      <div class="mb-4 border-t pt-4">
        <h3 class="font-semibold mb-2 text-primaryOld">{{ $t('medicalRecord') }}</h3>
        <div class="grid grid-cols-2 gap-2 text-primary">
          <label
            v-for="item in medicalLabels"
            :key="item.key"
            class="flex items-center gap-2"
          >
            <input type="checkbox" disabled :checked="parsedMedical[item.key]" />
            {{ item.label }}
          </label>
        </div>
      </div>

      <!-- Dental Record -->
      <div class="mb-4 border-t pt-4">
        <h3 class="font-semibold mb-2 text-primaryOld">{{ $t('dentalRecords') }}</h3>
        <div class="grid grid-cols-2 gap-2 text-primary">
          <label
            v-for="item in dentalLabels"
            :key="item.key"
            class="flex items-center gap-2"
          >
            <input type="checkbox" disabled :checked="parsedDental[item.key]" />
            {{ item.label }}
          </label>
        </div>
      </div>

      <!-- Consent -->
      <div class="mb-4 border-t pt-4">
        <h3 class="font-semibold mb-2 text-primaryOld">{{ $t('consent') }}</h3>
        <div class="grid grid-cols-2 gap-2 text-primary">
          <div>
            <p class="mb-4">__________ {{ $t('consentText') }}</p>
            <p class="mb-4">__________ {{ $t('consentDate') }}</p>
            <p>1/1</p>
          </div>
          <div><!-- space for signature --></div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="border-t pt-4 mt-6 text-xs">
      <div class="w-full bg-primaryOld text-white p-2 pb-2">
        <footer>
          <span class="mdi mdi-map-marker">{{ patient.systemAddress }}</span>
          &nbsp; &nbsp; &nbsp;
          <span class="mdi mdi-phone-in-talk-outline">{{ patient.phone }}</span>
        </footer>
      </div>
    </div>
  </div>
</template>


<script setup>

import { ref, computed, defineExpose } from 'vue';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();
const printContent = ref(null);

// expose it so parent can access
defineExpose({ printContent });;
const props = defineProps({
    patient: Object,
});

// These match the same key names created in your form
const medicalLabels = computed(() => [
    { key: "diabetes", label: t("diabetes") },
    { key: "blood_pressure", label: t("bloodPressure") },
    { key: "heart_disease", label: t("heartDisease") },
    { key: "asthma", label: t("asthma") },
    { key: "allergies", label: t("allergies") },
    { key: "others", label: t("others") },
]);

const dentalLabels = computed(() => [
    { key: "have_a_toothache?", label: t("toothache") },
    { key: "bleeding_gums?", label: t("bleedingGums") },
]);

// Parse JSON fields (with fallback if null)
const parsedMedical = computed(() => {
    try {
        return JSON.parse(props.patient.medicalRecord || "{}");
    } catch {
        return {};
    }
});
const parsedDental = computed(() => {
    try {
        return JSON.parse(props.patient.dentalRecord || "{}");
    } catch {
        return {};
    }
});
</script>

<style scoped>
input[type="checkbox"]:disabled {
    accent-color: #0d9488;
    cursor: not-allowed;
}

/* RTL overrides if needed */
[] {
    text-align: right;
}

[] .text-center {
    text-align: center;
}
</style>
