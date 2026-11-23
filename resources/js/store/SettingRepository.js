import i18n, { applyLocale } from "@/i18n";
import vuetify from "../../plugins/vuetify";
import { defineStore } from "pinia";
import { ref, reactive } from "vue";
import { axios } from "../axios";
import { useRouter } from "vue-router";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";

const dentalForm = (data, method) => {
    const body = new FormData();
    body.append("name", data.name || "");
    body.append("description", data.description || "");
    if (method) body.append("_method", method);
    (data.files || []).forEach((item) => body.append("images[]", item.file || item));
    (data.removeImages || []).forEach((id) => body.append("removeImages[]", id));
    return body;
};

export let useSettingRepository = defineStore("SettingRepository", {
    state() {
        return {
            isEditMode: ref(false),
            router: useRouter(),
            search: ref(""),
            serverItems: ref(""),
            loadingTable: ref(true),
            loading: ref(true),
            totalItems: ref(5),
            selectedItems: ref([]),
            itemsPerPage: ref(5),
            createDialog: ref(false),
            transferDialog: ref(false),
            // systemSettings
            systemSettings: reactive([]),
            systemSetting: reactive([]),
            dateType: localStorage.getItem("dateType") || "jalali",
            defaultLanguage: localStorage.getItem("locale") || "en",
            defaultTheme: localStorage.getItem("theme") === "dark" ? "dark" : "light",
            // permissions
            permissionSearch: ref(""),
            permissions: reactive([]),
            permission: reactive([]),
            // service groups
            serviceGroupSearch: ref(""),
            serviceGroups: reactive([]),
            serviceGroup: reactive([]),
            // service
            serviceSearch: ref(""),
            services: reactive([]),
            service: reactive([]),
            // Dental
            dentalSearch: ref(""),
            dentals: reactive([]),
            dental: reactive([]),
            // money Account
            moneyAccSearch: ref(""),
            moneyAccs: reactive([]),
            s: reactive([]),
        };
    },
    actions: {
        setEditMode(EditMode) {
            console.log(EditMode, "editMode");
            this.EditMode = EditMode;
        },
        applyDateType(type) {
            const next = type === "gregorian" ? "gregorian" : "jalali";
            this.dateType = next;
            localStorage.setItem("dateType", next);
            if (this.systemSettings && typeof this.systemSettings === "object") {
                this.systemSettings.date_type = next;
            }
        },
        applyLanguage(lang) {
            const next = ["en", "fa", "pa"].includes(lang) ? lang : "en";
            this.defaultLanguage = next;
            applyLocale(next);
            if (this.systemSettings && typeof this.systemSettings === "object") {
                this.systemSettings.default_language = next;
            }
        },
        applyTheme(theme) {
            const next = theme === "dark" ? "dark" : "light";
            this.defaultTheme = next;
            localStorage.setItem("theme", next);
            if (this.systemSettings && typeof this.systemSettings === "object") {
                this.systemSettings.default_theme = next;
            }
            const name = next === "dark" ? "myCustomDarkTheme" : "myCustomLightTheme";
            if (vuetify?.theme?.global?.name) {
                vuetify.theme.global.name.value = name;
            }
        },
        getTodaysDate() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, "0");
            const day = String(today.getDate()).padStart(2, "0");
            return `${year}-${month}-${day}`;
        },
        // systemSettings
        async FetchSystemSettings() {
            this.loading = true;

            try {
                const response = await axios.get(`systemSettings`);
                this.systemSettings = response.data.data || {};
                this.applyDateType(this.systemSettings.date_type);
                this.applyLanguage(this.systemSettings.default_language);
                this.applyTheme(this.systemSettings.default_theme);
            } catch (err) {
                this.systemSettings = this.systemSettings || {};
            } finally {
                this.loading = false;
            }
        },
        async fetchSystemSetting(id) {
            console.log(id);
            // this.error = null;
            try {
                const response = await axios.get(`systemSettings/${id}`);

                this.systemSetting = response.data.data;
                console.log(this.systemSetting, "mn");
            } catch (err) {
                // this.error = err.message;
            }
        },
        async UpdateSystemSetting(data) {
            console.log(data, "data");
            try {
                const config = {
                    method: "POST",
                    url: "systemSettings/updateSettings/" + 1,
                    headers: {
                        "Content-Type": "multipart/form-data",
                    },

                    data: data,
                };
                const response = await axios(config);
                this.applyDateType(data?.date_type || response.data?.data?.date_type);
                this.applyLanguage(data?.default_language || response.data?.data?.default_language);
                this.applyTheme(data?.default_theme || response.data?.data?.default_theme);
                toast.success(i18n.global.t("toast.companyCreated"), {
                    position: "top-right",
                    autoClose: 3000,
                    hideProgressBar: false,
                    closeOnClick: true,
                    pauseOnHover: true,
                    draggable: true,
                    progress: undefined,
                });
                this.FetchSystemSettings({
                    page: this.page,
                    itemsPerPage: this.itemsPerPage,
                });
            } catch (err) {
                // If there's an error, set the error in the store
                this.error = err;
                toast.error(i18n.global.t("toast.companyFailed"), {
                    position: "top-right",
                    autoClose: 3000,
                    hideProgressBar: false,
                    closeOnClick: true,
                    pauseOnHover: true,
                    draggable: true,
                    progress: undefined,
                });
            }
        },
        async CreateSystemSetting(formData) {
            console.log(formData);
            try {
                // Adding a custom header to the Axios request
                const config = {
                    method: "POST",
                    url: "systemSettings",
                    headers: {
                        "Content-Type": "multipart/form-data",
                    },
                    data: formData,
                };
                const response = await axios(config);

                this.createDialog = false;

                this.FetchSystemSettings({
                    page: this.page,
                    itemsPerPage: this.itemsPerPage,
                });
            } catch (err) {}
        },
        async DeleteSystemSetting(id) {
            this.isLoading = true;
            this.owners = [];
            this.error = null;

            try {
                const config = {
                    method: "DELETE",
                    url: "systemSettings/" + id,
                };

                const response = await axios(config);

                // this.patients = response.data.data;
                this.fetchSystemSettings({
                    page: this.page,
                    itemsPerPage: this.itemsPerPage,
                });
            } catch (err) {
                this.error = err;
            }
        },

        // service Group
        async FetchServiceGroups({ page, itemsPerPage }) {
            this.loading = true;

            const response = await axios.get(
                `serviceGroups?page=${page}&perPage=${itemsPerPage}&search=${this.serviceGroupSearch}`
            );
            this.serviceGroups = response.data.data;
            this.totalItems = response.data.meta.total;
            this.loading = false;
        },
        async fetchServiceGroup(id) {
            // this.error = null;
            try {
                const response = await axios.get(`serviceGroups/${id}`);

                this.serviceGroup = response.data.data;
                console.log(this.serviceGroup);
            } catch (err) {
                // this.error = err.message;
            }
        },
        async UpdateServiceGroup(id, data) {
            try {
                const config = {
                    method: "PUT",
                    url: "serviceGroups/" + id,

                    data: data,
                };

                // Using Axios to make a post request with async/await and custom headers
                const response = await axios(config);
                this.createDialog = false;
                this.FetchServiceGroups({
                    page: this.page,
                    itemsPerPage: this.itemsPerPage,
                });
            } catch (err) {
                this.error = err;
            }
        },
        async CreateServiceGroup(formData) {
            console.log(formData);
            try {
                const config = {
                    method: "POST",
                    url: "serviceGroups",

                    data: formData,
                };
                const response = await axios(config);
                this.FetchServiceGroups({
                    page: this.page,
                    itemsPerPage: this.itemsPerPage,
                });
            } catch (err) {
                // If there's an error, set the error in the stor
            }
        },
        async DeleteServiceGroup(id) {
            this.isLoading = true;
            this.setting = [];
            this.error = null;

            try {
                const config = {
                    method: "DELETE",
                    url: "serviceGroups/" + id,
                };

                const response = await axios(config);

                // this.setting = response.data.data;
                this.FetchServiceGroups({
                    page: this.page,
                    itemsPerPage: this.itemsPerPage,
                });
            } catch (err) {
                this.error = err;
            }
        },
        // service
        async FetchServices({ page, itemsPerPage }) {
            this.loading = true;

            const response = await axios.get(
                `services?page=${page}&perPage=${itemsPerPage}&search=${this.serviceSearch}`
            );
            this.services = response.data.data;
            this.totalItems = response.data.meta.total;
            this.loading = false;
        },
        async fetchService(id) {
            // this.error = null;
            try {
                const response = await axios.get(`services/${id}`);

                this.service = response.data.data;
                console.log(this.serviceGroup);
            } catch (err) {
                // this.error = err.message;
            }
        },
        async UpdateService(id, data) {
            try {
                const config = {
                    method: "PUT",
                    url: "services/" + id,

                    data: data,
                };

                // Using Axios to make a post request with async/await and custom headers
                const response = await axios(config);
                this.createDialog = false;
                this.FetchServices({
                    page: this.page,
                    itemsPerPage: this.itemsPerPage,
                });
            } catch (err) {
                this.error = err;
            }
        },
        async CreateService(formData) {
            console.log(formData);
            try {
                const config = {
                    method: "POST",
                    url: "services",

                    data: formData,
                };
                const response = await axios(config);
                this.createDialog = false;

                this.FetchServices({
                    page: this.page,
                    itemsPerPage: this.itemsPerPage,
                });
            } catch (err) {
                // If there's an error, set the error in the stor
            }
        },
        async DeleteService(id) {
            this.isLoading = true;
            this.setting = [];
            this.error = null;

            try {
                const config = {
                    method: "DELETE",
                    url: "services/" + id,
                };

                const response = await axios(config);

                // this.setting = response.data.data;
                this.FetchServices({
                    page: this.page,
                    itemsPerPage: this.itemsPerPage,
                });
            } catch (err) {
                this.error = err;
            }
        },
        async FetchDentals({ page, itemsPerPage }) {
            this.loading = true;

            const response = await axios.get(
                `tooths?page=${page}&perPage=${itemsPerPage}&search=${this.dentalSearch}`
            );
            this.dentals = response.data.data;
            this.totalItems = response.data.meta.total;
            this.loading = false;
        },
        async fetchDental(id) {
            // this.error = null;
            try {
                const response = await axios.get(`tooths/${id}`);

                this.dental = response.data.data;
                console.log(this.dental);
            } catch (err) {
                // this.error = err.message;
            }
        },
        async UpdateDental(id, data) {
            try {
                await axios.post("tooths/" + id, dentalForm(data, "PUT"), {
                    headers: { "Content-Type": undefined },
                });
                this.createDialog = false;
                this.FetchDentals({ page: 1, itemsPerPage: 8 });
            } catch (err) {
                this.error = err;
            }
        },
        async CreateDental(formData) {
            console.log(formData);
            try {
                await axios.post("tooths", dentalForm(formData), {
                    headers: { "Content-Type": undefined },
                });
                this.createDialog = false;

                this.FetchDentals({ page: 1, itemsPerPage: 8 });
            } catch (err) {
                // If there's an error, set the error in the stor
            }
        },
        async DeleteDental(id) {
            this.isLoading = true;
            this.setting = [];
            this.error = null;

            try {
                const config = {
                    method: "DELETE",
                    url: "tooths/" + id,
                };

                const response = await axios(config);

                // this.setting = response.data.data;
                this.FetchDentals({
                    page: this.page,
                    itemsPerPage: this.itemsPerPage,
                });
            } catch (err) {
                this.error = err;
            }
        },
        // accounts
        async fetchMoneyAccountsForSelect() {
            const response = await axios.get(`moneyAccount`);
            this.moneyAccs = response.data.data || [];
        },
        // ==================================================
        async fetchMoneyAccounts({ page, itemsPerPage }) {
            this.loading = true;

            const response = await axios.get(
                `moneyAccount?page=${page}&perPage=${itemsPerPage}&search=${this.moneyAccSearch}`
            );
            this.moneyAccs = response.data.data;
            this.totalItems = response.data.meta.total;
            this.loading = false;
        },
        async fetchMoneyAcc(id) {
            // this.error = null;
            try {
                const response = await axios.get(`moneyAccount/${id}`);

                this.s = response.data.data;
                console.log(this.moneyAccs);
            } catch (err) {
                // this.error = err.message;
            }
        },
        async UpdateMoneyAcc(id, data) {
            try {
                const config = {
                    method: "PUT",
                    url: "moneyAccount/" + id,

                    data: data,
                };

                // Using Axios to make a post request with async/await and custom headers
                const response = await axios(config);
                this.createDialog = false;
                this.fetchMoneyAccounts({
                    page: this.page,
                    itemsPerPage: this.itemsPerPage,
                });
            } catch (err) {
                // If there's an error, set the error in the store
                this.error = err;
            }
        },
        async CreateMoneyAcc(formData) {
            console.log(formData);
            try {
                // Adding a custom header to the Axios request
                const config = {
                    method: "POST",
                    url: "moneyAccount",

                    data: formData,
                };

                // Using Axios to make a GET request with async/await and custom headers
                const response = await axios(config);
                this.createDialog = false;
                toast.success(i18n.global.t("toast.moneyAccountCreated"), {
                    position: "top-right",
                    autoClose: 3000,
                    hideProgressBar: false,
                    closeOnClick: true,
                    pauseOnHover: true,
                    draggable: true,
                    progress: undefined,
                });
                this.fetchMoneyAccounts({
                    page: this.page,
                    itemsPerPage: this.itemsPerPage,
                });
            } catch (err) {
                this.error =
                    err.response?.data?.message ||
                    "Failed to create Money Account. Please try again.";

                // Show toast
                toast.error(this.error, {
                    position: "top-right",
                    autoClose: 3000,
                    hideProgressBar: false,
                    closeOnClick: true,
                    pauseOnHover: true,
                    draggable: true,
                    progress: undefined,
                });
            }
        },
        async DeleteMoneyAcc(id) {
            this.isLoading = true;
            this.setting = [];
            this.error = null;

            try {
                const config = {
                    method: "DELETE",
                    url: "moneyAccount/" + id,
                };

                const response = await axios(config);

                // this.setting = response.data.data;
                this.fetchMoneyAccounts({
                    page: this.page,
                    itemsPerPage: this.itemsPerPage,
                });
            } catch (err) {
                this.error = err;
            }
        },
        // money Transfer
        //
        async CreateTransferAcc(formData) {
            console.log(formData);
            try {
                // Adding a custom header to the Axios request
                const config = {
                    method: "POST",
                    url: "moneyTransfer",

                    data: formData,
                };

                // Using Axios to make a GET request with async/await and custom headers
                const response = await axios(config);
                this.transferDialog = false;
                this.fetchMoneyAccounts({
                    page: this.page,
                    itemsPerPage: this.itemsPerPage,
                });
            } catch (err) {
                // If there's an error, set the error in the stor
            }
        },
    },
});
