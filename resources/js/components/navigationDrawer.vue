<template>
    <div class="nav-shell" :dir="dir" :class="{ 'is-rail': rail }" @mouseleave="scheduleClose">
        <router-link to="/dashboard" class="nav-logo-link" @mouseenter="clearHover">
            <img
                :src="SettingRepository.systemSettings.photo"
                alt="Logo"
                class="nav-logo"
            />
        </router-link>

        <div class="nav-scroll" @scroll="syncPanel">
            <section v-for="section in sections" :key="section.label" class="nav-block">
                <p class="nav-section" @mouseenter="clearHover">{{ section.label }}</p>
                <template v-for="item in section.items" :key="item.id || item.to">
                    <button
                        v-if="item.children"
                        type="button"
                        class="nav-row"
                        :class="{
                            'is-open': panelId === item.id,
                            'is-active': itemActive(item),
                        }"
                        :data-nav-id="item.id"
                        :aria-expanded="panelId === item.id"
                        @mouseenter="enterParent(item, $event)"
                        @mouseleave="scheduleClose"
                        @focus="enterParent(item, $event)"
                        @blur="scheduleClose"
                    >
                        <v-icon v-if="rail" :icon="item.icon" size="22" />
                        <span v-else class="nav-label">{{ item.title }}</span>
                        <svg v-if="!rail" class="nav-chevron" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M7 4l6 6-6 6" />
                        </svg>
                    </button>
                    <router-link
                        v-else
                        :to="item.to"
                        class="nav-row"
                        :class="{ 'is-active': itemActive(item) }"
                        @mouseenter="clearHover"
                    >
                        <v-icon v-if="rail" :icon="item.icon" size="22" />
                        <span v-else class="nav-label">{{ item.title }}</span>
                    </router-link>
                </template>
            </section>
        </div>

        <div class="nav-drawer-footer">
            <button type="button" class="nav-profile" @click="dialog = true" @mouseenter="clearHover">
                <v-avatar size="40" color="primaryOld" class="user-avatar">
                    <v-img
                        v-if="showPhoto"
                        :src="displayUser.photo"
                        cover
                        alt=""
                        @error="photoBroken = true"
                    />
                    <span v-else class="user-initials">{{ initials }}</span>
                </v-avatar>
                <span v-if="!rail" class="nav-profile-copy">
                    <span class="nav-profile-name">{{ displayUser.name }}</span>
                    <span class="nav-profile-email">{{ displayUser.email }}</span>
                </span>
            </button>

            <v-dialog v-model="dialog" max-width="350">
                <v-card class="text-center">
                    <v-card-text>
                        <v-avatar size="80" color="primaryOld" class="user-avatar">
                            <v-img
                                v-if="showPhoto"
                                :src="displayUser.photo"
                                cover
                                alt=""
                                @error="photoBroken = true"
                            />
                            <span v-else class="user-initials user-initials-lg">{{
                                initials
                            }}</span>
                        </v-avatar>
                        <h3 class="mt-3">{{ displayUser.name }}</h3>
                        <p class="text-gray-500">{{ displayUser.email }}</p>
                        <div class="text-start mt-4">
                            <template v-if="twoFactor.enabled && !twoFactor.secret">
                                <p class="text-caption mb-2">{{ t("twoFactorOn") }}</p>
                                <v-text-field
                                    v-model="twoFactor.password"
                                    :label="t('login.password')"
                                    type="password"
                                    density="compact"
                                    hide-details
                                    class="mb-2"
                                />
                                <v-text-field
                                    v-model="twoFactor.code"
                                    :label="t('twoFactorCode')"
                                    density="compact"
                                    maxlength="6"
                                    hide-details
                                />
                                <v-btn
                                    class="mt-2"
                                    block
                                    color="error"
                                    variant="tonal"
                                    :loading="twoFactor.loading"
                                    @click="disableTwoFactor"
                                >
                                    {{ t("disableTwoFactor") }}
                                </v-btn>
                            </template>
                            <v-btn
                                v-else-if="!twoFactor.secret"
                                block
                                variant="tonal"
                                color="primary"
                                :loading="twoFactor.loading"
                                @click="startTwoFactor"
                            >
                                {{ t("enableTwoFactor") }}
                            </v-btn>
                            <template v-else>
                                <p class="text-caption mb-2">{{ t("twoFactorHint") }}</p>
                                <p class="text-caption mb-2 twofactor-secret">{{ twoFactor.secret }}</p>
                                <v-text-field
                                    v-model="twoFactor.code"
                                    :label="t('twoFactorCode')"
                                    density="compact"
                                    maxlength="6"
                                    hide-details
                                />
                                <v-btn
                                    class="mt-2"
                                    block
                                    color="primary"
                                    :loading="twoFactor.loading"
                                    @click="confirmTwoFactor"
                                >
                                    {{ t("save") }}
                                </v-btn>
                            </template>
                        </div>
                    </v-card-text>
                    <v-card-actions>
                        <v-btn color="error" block @click="logout">{{ t("logOut") }}</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </div>
    </div>

    <Teleport to="body">
        <div
            v-if="panelGroup"
            class="nav-flyout"
            :dir="dir"
            :class="{ 'is-dark': isDark }"
            :style="flyoutStyle"
            @mouseenter="cancelClose"
            @mouseleave="scheduleClose"
        >
            <p class="nav-flyout-label">{{ panelGroup.title }}</p>
            <router-link
                v-for="item in panelGroup.children"
                :key="item.to"
                :to="item.to"
                class="nav-flyout-link"
                :class="{ 'is-active': itemActive(item) }"
            >
                {{ item.title }}
            </router-link>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useRoute } from "vue-router";
import { useTheme } from "vuetify";
import { toast } from "vue3-toastify";
import { axios } from "../axios";
import { useAuthRepository } from "../store/AuthRepository";
import { useSettingRepository } from "../store/SettingRepository";

const props = defineProps({
    dir: { type: String, default: "" },
});

const { t, locale } = useI18n();
const theme = useTheme();
const SettingRepository = useSettingRepository();
SettingRepository.FetchSystemSettings();
const route = useRoute();
const AuthRepository = useAuthRepository();

const dialog = ref(false);
const photoBroken = ref(false);
const twoFactor = reactive({
    secret: "",
    code: "",
    password: "",
    enabled: false,
    loading: false,
});

watch(
    () => AuthRepository.user?.twoFactorEnabled,
    (enabled) => {
        twoFactor.enabled = Boolean(enabled);
    },
    { immediate: true }
);

const twoFactorError = (error) =>
    error?.response?.data?.message || t("saveFailed");

const startTwoFactor = async () => {
    twoFactor.loading = true;
    try {
        const response = await axios.post("two-factor/enable");
        twoFactor.secret = response.data?.secret || "";
        twoFactor.code = "";
    } catch (error) {
        toast.error(twoFactorError(error));
    } finally {
        twoFactor.loading = false;
    }
};

const confirmTwoFactor = async () => {
    twoFactor.loading = true;
    try {
        await axios.post("two-factor/confirm", { code: twoFactor.code });
        twoFactor.secret = "";
        twoFactor.code = "";
        twoFactor.enabled = true;
        if (AuthRepository.user) {
            AuthRepository.user.twoFactorEnabled = true;
        }
        toast.success(t("twoFactorOn"));
    } catch (error) {
        toast.error(twoFactorError(error));
    } finally {
        twoFactor.loading = false;
    }
};

const disableTwoFactor = async () => {
    twoFactor.loading = true;
    try {
        await axios.post("two-factor/disable", {
            password: twoFactor.password,
            code: twoFactor.code,
        });
        twoFactor.enabled = false;
        twoFactor.secret = "";
        twoFactor.code = "";
        twoFactor.password = "";
        if (AuthRepository.user) {
            AuthRepository.user.twoFactorEnabled = false;
        }
        toast.success(t("twoFactorOff"));
    } catch (error) {
        toast.error(twoFactorError(error));
    } finally {
        twoFactor.loading = false;
    }
};

const displayUser = computed(() => {
    const profile = AuthRepository.user || {};
    const name =
        profile.name ||
        `${profile.firstName || ""} ${profile.lastName || ""}`.trim();

    return {
        name,
        email: profile.email || "",
        photo: profile.photo || profile.profilePicture || "",
    };
});

const showPhoto = computed(
    () => Boolean(displayUser.value.photo) && !photoBroken.value
);

const initials = computed(() => {
    const letters = displayUser.value.name
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join("");

    return (letters || "U").toUpperCase();
});

watch(
    () => displayUser.value.photo,
    () => {
        photoBroken.value = false;
    }
);

onMounted(() => {
    AuthRepository.refreshPermissions();
});

const logout = () => {
    AuthRepository.Logout();
};

const dir = computed(
    () => props.dir || (["fa", "pa"].includes(locale.value) ? "rtl" : "ltr")
);
const rail = computed(() => AuthRepository.rail);
const isDark = computed(() => theme.global.current.value.dark);

const child = (to, title, match) => ({ to, title, match: match || [to] });
const leaf = (to, title, icon, match) => ({ to, title, icon, match: match || [to] });
const group = (id, title, icon, children) => ({ id, title, icon, children });

const sections = computed(() => [
    {
        label: t("navClinic"),
        items: [
            leaf("/dashboard", t("dashboard"), "mdi-home-lightning-bolt-outline"),
            group("lead", t("leads"), "mdi-gauge", [
                child("/lead", t("leads")),
                child("/leadCategory", t("leadCategory")),
                child("/leadStage", t("leadStage")),
            ]),
            leaf("/appointments", t("appointment"), "mdi-calendar-clock", [
                "/appointments",
                "/appointmentPrint",
            ]),
            leaf("/cure", t("cureCycle"), "mdi-tooth-outline", [
                "/cure",
                "/createCure",
                "/updateCure",
                "/viewCureCycle",
            ]),
            leaf("/mainLaboratory", t("inboundLaboratory"), "mdi-microscope", [
                "/mainLaboratory",
                "/createMainLab",
                "/updateMainLab",
            ]),
            leaf("/laboratory", t("outboundLaboratory"), "mdi-test-tube", [
                "/laboratory",
                "/createLab",
                "/updateLab",
            ]),
        ],
    },
    {
        label: t("navFinance"),
        items: [
            leaf("/inventory", t("inventory"), "mdi-package-variant-closed"),
            group("expense", t("expense"), "mdi-currency-usd-off", [
                child("/expense", t("expense")),
                child("/billExpense", t("billExpense"), [
                    "/billExpense",
                    "/createBillExpense",
                    "/updateBillExpense",
                ]),
                child("/expenseProducts", t("products")),
                child("/expenseCat", t("categories")),
            ]),
        ],
    },
    {
        label: t("navPeople"),
        items: [
            group("people", t("people"), "mdi-card-account-details-outline", [
                child("/employee", t("employee"), ["/employee", "/viewEmployee"]),
                child("/patients", t("patients"), ["/patients", "/viewPatients"]),
                child("/user", t("user")),
                child("/owners", t("owners")),
                child("/supplier", t("supplier"), ["/supplier", "/viewSupplier"]),
                child("/customer", t("customer"), ["/customer", "/viewCustomer"]),
                child("/doctors", t("doctor")),
            ]),
        ],
    },
    {
        label: t("navInsights"),
        items: [
            leaf("/notifications", t("notifications"), "mdi-bell-outline"),
            group("reports", t("reports"), "mdi-finance", [
                child("/deleteHistory", t("deleteHistory")),
                child("/profitLoss", t("profitLoss")),
                child("/patientsReport", t("patient")),
                child("/categoryReport", t("expenseCategory")),
                child("/productReport", t("expenseProduct")),
                child("/serviceReport", t("service")),
            ]),
        ],
    },
    {
        label: t("navSystem"),
        items: [
            group("setting", t("setting"), "mdi-cog-outline", [
                child("/systemSetting", t("systemSetting")),
                child("/moneyAcc", t("moneyAccount")),
                child("/rolePermissions", t("rolePermission"), [
                    "/rolePermissions",
                    "/createPermissions",
                    "/updatePermissions",
                ]),
                child("/service", t("service")),
                child("/dental-types", t("dentalTypes")),
            ]),
        ],
    },
]);

const pathMatches = (path, prefixes) =>
    prefixes.some((prefix) => path === prefix || path.startsWith(`${prefix}/`));

const itemActive = (item) => {
    if (item.children) {
        return item.children.some((childItem) => itemActive(childItem));
    }
    return pathMatches(route.path, item.match || [item.to]);
};

const hoverId = ref(null);
const panelTop = ref(16);
let closeTimer = null;

const panelId = computed(() => hoverId.value);

const findGroup = (id) => {
    for (const section of sections.value) {
        const found = section.items.find((item) => item.id === id);
        if (found) return found;
    }
    return null;
};

const panelGroup = computed(() => (panelId.value ? findGroup(panelId.value) : null));

const flyoutStyle = computed(() => {
    const edge = rail.value ? 56 : 272;
    const side = dir.value === "rtl" ? "right" : "left";
    return {
        top: `${panelTop.value}px`,
        [side]: `${edge + 10}px`,
    };
});

const placePanel = (target, count) => {
    const rect = target.getBoundingClientRect();
    const height = 52 + count * 46;
    const maxTop = Math.max(12, window.innerHeight - height - 12);
    panelTop.value = Math.min(Math.max(12, rect.top), maxTop);
    nextTick(() => {
        const flyout = document.querySelector(".nav-flyout");
        if (!flyout) return;
        const box = flyout.getBoundingClientRect();
        const overflow = box.bottom - (window.innerHeight - 12);
        if (overflow > 0) {
            panelTop.value = Math.max(12, panelTop.value - overflow);
        }
    });
};

const cancelClose = () => {
    clearTimeout(closeTimer);
};

const clearHover = () => {
    cancelClose();
    hoverId.value = null;
};

const scheduleClose = () => {
    clearTimeout(closeTimer);
    closeTimer = setTimeout(() => {
        hoverId.value = null;
    }, 80);
};

const enterParent = (item, event) => {
    cancelClose();
    hoverId.value = item.id;
    placePanel(event.currentTarget, item.children.length);
};

const syncPanel = () => {
    if (!panelId.value) return;
    const row = document.querySelector(`[data-nav-id="${panelId.value}"]`);
    const group = findGroup(panelId.value);
    if (row && group) placePanel(row, group.children.length);
};

onBeforeUnmount(() => {
    clearTimeout(closeTimer);
});
</script>

<style scoped>
.nav-shell {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    height: 100%;
    min-height: 0;
    color: #161616;
}

.nav-logo-link {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    margin: 18px 18px 6px;
    line-height: 0;
}

.nav-logo {
    width: 72px;
    height: 48px;
    object-fit: contain;
    object-position: start center;
    background: transparent;
    border: 0;
    border-radius: 0;
    box-shadow: none;
}

.nav-scroll {
    flex: 1 1 auto;
    min-height: 0;
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-width: none;
    padding-bottom: 8px;
}

.nav-scroll:hover {
    scrollbar-width: thin;
    scrollbar-color: #888 transparent;
}

.nav-scroll::-webkit-scrollbar {
    width: 0;
}

.nav-scroll:hover::-webkit-scrollbar {
    width: 4px;
}

.nav-scroll::-webkit-scrollbar-thumb {
    background: transparent;
    border-radius: 4px;
}

.nav-scroll:hover::-webkit-scrollbar-thumb {
    background: #888;
}

.nav-section {
    margin: 16px 18px 2px;
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #8b9198;
}

.nav-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: calc(100% - 16px);
    min-height: 44px;
    margin: 1px 8px;
    padding: 8px 10px;
    border: 0;
    border-radius: 12px;
    background: transparent;
    color: inherit;
    font: inherit;
    font-size: 15px;
    font-weight: 500;
    text-align: start;
    text-decoration: none;
    cursor: pointer;
}

.nav-row:hover,
.nav-row.is-open,
.nav-row.is-active {
    background: rgba(15, 23, 42, 0.06);
}

.nav-chevron {
    width: 16px;
    height: 16px;
    flex: 0 0 auto;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.7;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.nav-shell[dir="rtl"] .nav-chevron {
    transform: scaleX(-1);
}

.nav-drawer-footer {
    flex-shrink: 0;
    margin: 0 8px 10px;
}

.nav-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    min-height: 56px;
    margin: 0;
    padding: 8px 10px;
    border: 0;
    border-radius: 12px;
    background: transparent;
    color: inherit;
    font: inherit;
    text-align: start;
    cursor: pointer;
}

.nav-profile:hover {
    background: rgba(15, 23, 42, 0.06);
}

.nav-profile-copy {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.nav-profile-name,
.nav-profile-email {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.nav-profile-name {
    font-size: 14px;
    font-weight: 500;
}

.nav-profile-email {
    font-size: 12px;
    font-weight: 400;
    color: #8b9198;
}

.user-avatar {
    flex-shrink: 0;
}

.user-initials {
    color: #fff;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.02em;
}

.user-initials-lg {
    font-size: 1.25rem;
}

.twofactor-secret {
    font-family: ui-monospace, Consolas, monospace;
    letter-spacing: 0.04em;
    word-break: break-all;
}

.is-rail .nav-logo-link {
    justify-content: center;
    margin: 12px auto 8px;
}

.is-rail .nav-logo {
    width: 32px;
    height: 32px;
    object-position: center;
}

.is-rail .nav-section,
.is-rail .nav-label,
.is-rail .nav-chevron,
.is-rail .nav-profile-copy {
    display: none;
}

.is-rail .nav-row {
    justify-content: center;
    width: 40px;
    min-height: 40px;
    margin: 4px auto;
    padding: 0;
}

.is-rail .nav-profile {
    justify-content: center;
    width: 40px;
    min-height: 40px;
    margin: 0 auto;
    padding: 0;
}

:global(.v-theme--dark) .nav-shell {
    color: #f4f4f5;
}

:global(.v-theme--dark) .nav-section,
:global(.v-theme--dark) .nav-profile-email {
    color: #8e989f;
}

:global(.v-theme--dark) .nav-row:hover,
:global(.v-theme--dark) .nav-row.is-open,
:global(.v-theme--dark) .nav-row.is-active,
:global(.v-theme--dark) .nav-profile:hover {
    background: rgba(255, 255, 255, 0.08);
}
</style>

<style>
.nav-flyout {
    position: fixed;
    z-index: 1010;
    width: 248px;
    max-height: calc(100vh - 24px);
    overflow: auto;
    padding: 10px 8px 12px;
    border: 1px solid rgba(17, 24, 39, 0.08);
    border-radius: 18px;
    background: #fff;
    color: #161616;
}

.nav-flyout.is-dark {
    border-color: rgba(255, 255, 255, 0.08);
    background: #1b2128;
    color: #f4f4f5;
}

.nav-flyout-label {
    margin: 6px 10px 4px;
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #8b9198;
}

.nav-flyout.is-dark .nav-flyout-label {
    color: #8e989f;
}

.nav-flyout-link {
    display: flex;
    align-items: center;
    min-height: 42px;
    margin: 1px 0;
    padding: 8px 10px;
    border-radius: 12px;
    color: inherit;
    font-size: 15px;
    font-weight: 500;
    text-decoration: none;
}

.nav-flyout-link:hover,
.nav-flyout-link.is-active {
    background: rgba(15, 23, 42, 0.06);
}

.nav-flyout.is-dark .nav-flyout-link:hover,
.nav-flyout.is-dark .nav-flyout-link.is-active {
    background: rgba(255, 255, 255, 0.08);
}
</style>
