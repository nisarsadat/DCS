<template>{{ text }}</template>

<script setup>
import { computed } from "vue";
import { storeToRefs } from "pinia";
import { formatDisplayDate } from "@/formatDate";
import { useSettingRepository } from "@/store/SettingRepository";

const props = defineProps({
    value: { type: [String, Number], default: "" },
});

const { dateType } = storeToRefs(useSettingRepository());

const text = computed(() => {
    dateType.value;
    return formatDisplayDate(props.value);
});
</script>
