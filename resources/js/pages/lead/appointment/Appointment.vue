<template>
    <CreateAppointment v-if="LeadRepository.createDialog" />
    <div class="all-expense rounded-xl" :dir="dir">
        <div class="card rounded-xl">
            <AppBar
                :mainTitle="$t('appointment')"
                :sub-title="$t('appointment')"
            />
            <v-divider
                :thickness="1"
                class="border-opacity-100"
                color="success"
            ></v-divider>

            <div class="btn-search pt-12 pb-6">
                <div class="text-field w-25">
                    <v-text-field
                        :loading="loading"
                        color="primaryOld"
                        density="compact"
                        variant="outlined"
                        :label="$t('search')"
                        append-inner-icon="mdi-magnify"
                        hide-details
                        v-model="LeadRepository.appointmentSearch"
                    ></v-text-field>
                </div>
                <div class="btn d-flex">
                    <v-btn variant="outlined" color="primaryOld" class="px-6" @click="showFilters = !showFilters">{{ $t("filter") }}</v-btn>
                    &nbsp;
                    <div
                        
                    >
                        <v-btn
                            @click="CreateDialogShow"
                            color="primaryOld"
                            variant="flat"
                            :text="$t('create')"
                            class="px-6"
                        >
                        </v-btn>
                    </div>
                </div>
            </div>

            <!-- v-table server -->
            
            <TableFilter
                v-model="showFilters"
                :source="LeadRepository.appointments"
                v-model:result="filteredRows"
                :fields="filterFields"
            />
<div class="overflow-x-hidden">
                <v-app>
                    <v-main class="main">
                        <v-row>
                            <v-col>
                                <v-data-table-server
                                    :dir="dir"
                                    theme="cursor-pointer"
                                    v-model:items-per-page="
                                        LeadRepository.itemsPerPage
                                    "
                                    :headers="headers"
                                    :items-length="LeadRepository.totalItems"
                                    :items="filteredRows ?? LeadRepository.appointments"
                                    :loading="LeadRepository.loading"
                                    :search="LeadRepository.appointmentSearch"
                                    @update:options="
                                        LeadRepository.FetchAppointments
                                    "
                                    :item-key="LeadRepository.appointments"
                                    hover
                                    class="w-100 mx-auto"
                                >
                                                                        <template v-slot:item.date="{ item }">
                                        <FormattedDate :value="item.date" />
                                    </template>
<template v-slot:item.printBtn="{ item }">
                                        <v-btn
                                            color="primaryOld"
                                            @click="
                                                generateAppointmentPrint(
                                                    item
                                                )
                                            "
                                        >
                                            {{ $t("print") }}
                                        </v-btn>
                                    </template>

                                    <template v-slot:item.action="{ item }">
                                        <v-menu>
                                            <template
                                                v-slot:activator="{ props }"
                                            >
                                                <v-btn
                                                    icon="mdi-dots-vertical"
                                                    v-bind="props"
                                                    variant="text"
                                                ></v-btn>
                                            </template>
                                            <v-list>
                                                <v-list-item>
                                                    <v-list-item-title
                                                        
                                                        @click="edit(item)"
                                                        class="cursor-pointer d-flex gap-3 justify-left pb-3"
                                                    >
                                                        <v-icon
                                                            color="tealColor"
                                                            >mdi-square-edit-outline</v-icon
                                                        >
                                                        {{ $t("edit") }}
                                                    </v-list-item-title>

                                                    <v-list-item-title
                                                        
                                                        class="cursor-pointer d-flex gap-3"
                                                        @click="
                                                            deleteItem(item)
                                                        "
                                                    >
                                                        <v-icon color="error"
                                                            >mdi-delete-outline</v-icon
                                                        >
                                                        {{ $t("delete") }}
                                                    </v-list-item-title>
                                                </v-list-item>
                                            </v-list>
                                        </v-menu>
                                    </template>
                                </v-data-table-server>
                            </v-col>
                        </v-row>
                    </v-main>
                </v-app>
            </div>
        </div>
    </div>
    <div class="appointment-print-source" aria-hidden="true">
        <PrintAppointment
            v-if="showPrint"
            ref="printSheet"
            :appointment="selectedAppointment"
        />
    </div>
</template>

<script setup>
const showFilters = ref(false);
const filteredRows = ref(null);
const filterFields = [
    { key: "patients.name", label: "patient" },
    { key: "dentists.name", label: "doctor" },
    { key: "date", label: "date" },
    { key: "userName", label: "addedBy" },
    { key: "status", label: "status" },
];

import { ref, computed, nextTick } from "vue";
import AppBar from "../../../components/AppBar.vue";
import CreateAppointment from "./CreateAppointment.vue";
import { useLeadRepository } from "@/store/LeadRepository";
import { useAuthRepository } from "../../../store/AuthRepository";
const AuthRepository = useAuthRepository();

import { useI18n } from "vue-i18n";
const { t, locale } = useI18n();
const LeadRepository = useLeadRepository();
import PrintAppointment from "./PrintAppointment.vue";
const showPrint = ref(false);
const selectedAppointment = ref(null);
const printSheet = ref(null);

const generateAppointmentPrint = async (appointment) => {
    if (!appointment) return;

    selectedAppointment.value = appointment;
    showPrint.value = true;
    await nextTick();

    const exposed = printSheet.value?.printContent;
    const source = exposed?.outerHTML ? exposed : exposed?.value;
    if (!source) {
        showPrint.value = false;
        return;
    }

    const iframe = document.createElement("iframe");
    iframe.setAttribute("aria-hidden", "true");
    iframe.style.cssText = "position:fixed;width:0;height:0;border:0;";
    document.body.appendChild(iframe);

    const pageDir = locale.value === "en" ? "ltr" : "rtl";
    const doc = iframe.contentDocument;
    doc.open();
    doc.write(
        `<!DOCTYPE html><html lang="${locale.value}" dir="${pageDir}"><head><meta charset="utf-8"><style>@page{size:A4;margin:14mm}html,body{margin:0;padding:0;background:#fff}*{box-sizing:border-box}</style></head><body>${source.outerHTML}</body></html>`
    );
    doc.close();

    await Promise.all(
        [...doc.images].map(
            (img) =>
                img.complete
                    ? Promise.resolve()
                    : new Promise((resolve) => {
                          img.onload = resolve;
                          img.onerror = resolve;
                      })
        )
    );

    const printWindow = iframe.contentWindow;
    printWindow.onafterprint = () => {
        iframe.remove();
        showPrint.value = false;
    };
    printWindow.focus();
    printWindow.print();
};

// bulk delete

// delete and update Create
const CreateDialogShow = () => {
    LeadRepository.appointment = {};
    LeadRepository.isEditMode = false;
    LeadRepository.createDialog = true;
};

const dir = computed(() => {
    return locale.value === "fa" ? "rtl" : "ltr"; // Correctly set "rtl" and "ltr"
});

const edit = (item) => {
    console.log(item, "me");
    LeadRepository.isEditMode = true;
    LeadRepository.appointment = {};
    if (Object.keys(LeadRepository.appointment).length === 0) {
        LeadRepository.fetchAppointment(item.id)
            .then(() => {
                LeadRepository.createDialog = true;
            })
            .catch((error) => {
                console.error("Error fetching data:", error);
            });
    }
};

const deleteItem = async (item) => {
    await LeadRepository.DeleteAppointment(item.id);
};

// header
const headers = [
    {
        title: t("patient"),
        key: "patients.name",
        align: "start",
        sortable: false,
    },
    {
        title: t("doctor"),
        key: "dentists.name",
        align: "start",
        sortable: false,
    },
    { title: t("date"), key: "date", align: "start", sortable: false },
    { title: t("addedBy"), key: "userName", align: "start", sortable: false },
    { title: t("time"), key: "time", align: "start", sortable: false },
    { title: t("status"), key: "status", align: "start", sortable: false },
    { title: t("print"), key: "printBtn", align: "center", sortable: false },
    { title: t("action"), key: "action", align: "end", sortable: false },
];
</script>

<style scoped>
.appointment-print-source {
    position: absolute;
    width: 0;
    height: 0;
    overflow: hidden;
}
</style>
