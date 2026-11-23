
import { createApp } from "vue";
import "./bootstrap.js";
import { createPinia } from "pinia";
import piniaPluginPersistedstate from "pinia-plugin-persistedstate";
import "../css/app.css";
import "vue3-toastify/dist/index.css";
import "vuetify/styles";
import vuetify from "../plugins/vuetify";
import router from "./router.js";
import App from "./App.vue";
import DatePicker from './components/AppDatePicker.vue';
import FormattedDate from './components/FormattedDate.vue';
import TableFilter from './components/TableFilter.vue';
import i18n from './i18n';

import { useAuthRepository } from './store/AuthRepository.js'

const app = createApp(App);

const pinia = createPinia();
pinia.use(piniaPluginPersistedstate);

app.component('DatePicker', DatePicker);
app.component('FormattedDate', FormattedDate);
app.component('TableFilter', TableFilter);
app.use(router);
app.use(i18n);
app.use(pinia);
app.use(vuetify);

const auth = useAuthRepository();
auth.initialize();

app.mount("#app");

