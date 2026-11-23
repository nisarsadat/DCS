<template>
    <div class="bg-cover">
        <div class="login-page">
            <div class="lang-switch">
                <LangSwticher />
            </div>

            <v-container class="fill-height d-flex align-center justify-center">
                <v-form
                    @submit.prevent="loginFunc"
                    ref="formRef"
                    lazy-validation
                >
                    <div class="form-wrapper">
                        <div class="login-header">
                            <h1>{{ $t("login.title") }}</h1>
                        </div>

                        <v-text-field
                            v-model="formData.email"
                            density="compact"
                            :placeholder="$t('login.email')"
                            prepend-inner-icon="mdi-email-outline"
                            variant="outlined"
                            :rules="[rules.required, rules.email]"
                        ></v-text-field>

                        <v-text-field
                            v-model="formData.password"
                            :append-inner-icon="
                                visible ? 'mdi-eye-off' : 'mdi-eye'
                            "
                            :type="visible ? 'text' : 'password'"
                            density="compact"
                            :placeholder="$t('login.password')"
                            prepend-inner-icon="mdi-lock-outline"
                            variant="outlined"
                            @click:append-inner="visible = !visible"
                            :rules="[rules.required, rules.password]"
                        ></v-text-field>

                        <v-text-field
                            v-if="needsCode"
                            v-model="code"
                            density="compact"
                            :placeholder="t('twoFactorCode')"
                            prepend-inner-icon="mdi-shield-key-outline"
                            variant="outlined"
                            maxlength="6"
                        />

                        <v-btn
                            class="submit-btn"
                            color="primary"
                            block
                            type="submit"
                            :loading="isLoading"
                            :disabled="isLoading"
                        >
                            {{ isLoading ? $t("login.loading") : $t("login.button") }}
                        </v-btn>

                        <div class="text-end pt-4 text-primaryOld">
                            <router-link to="/forgot-password">
                                <a class="forgot-link">
                                    {{ $t("login.forgot") }}
                                </a>
                            </router-link>
                        </div>
                    </div>
                </v-form>
            </v-container>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { useAuthRepository } from "@/store/AuthRepository";
import LangSwticher from "@/components/LangSwticher.vue";
import { toast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";

const router = useRouter();
const { t, locale } = useI18n();
const AuthRepository = useAuthRepository();

const formData = reactive({
    email: "",
    password: "",
});
const visible = ref(false);
const needsCode = ref(false);
const code = ref("");
const formRef = ref(null);
const isLoading = computed(() => AuthRepository.isLoading);

// Validation rules
const rules = {
    required: (v) => !!v || t("validation.required"),
    email: (v) => /.+@.+\..+/.test(v) || t("validation.email"),
    password: (v) =>
        (!!v && v.trim().length >= 3) || t("validation.passwordLength"),
};

// Login function - corrected version
const loginFunc = async () => {
    const isValid = await formRef.value?.validate();
    if (!isValid.valid) {
        toast.error(t("validation.bothFields"));
        return;
    }

    try {
        if (needsCode.value) {
            await AuthRepository.confirmTwoFactor(code.value);
        } else {
            const result = await AuthRepository.Login(formData);
            if (result?.twoFactor) {
                needsCode.value = true;
                toast.info(t("twoFactorCode"));
                return;
            }
        }

        if (AuthRepository.isLoggedIn) {
            toast.success(t("login.success"));
            router.push("/dashboard");
        } else if (AuthRepository.error) {
            toast.error(AuthRepository.error);
        }
    } catch (error) {
        toast.error(t("login.error"));
    }
};


// Language switch logic
const changeLanguage = (lang) => {
    locale.value = lang;
};
</script>

<style scoped>
/* Your existing styles remain the same */
.forgot-link {
    font-size: 0.9rem;
    color: primaryOld;
    cursor: pointer;
    text-decoration: underline;
    margin-top: -10px;
    display: inline-block;
}
.forgot-link:hover {
    color: primaryOld;
}
.bg-cover {
    background: radial-gradient(circle at top left, #ccfbf1, #f8fafc 42%, #e2e8f0);
    height: 100vh;
}
.login-page {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
    width: 100vw;
}
.lang-switch {
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 10;
}
.form-wrapper {
    background-color: #ffffff;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    max-width: 400px;
    width: 26rem;
    min-height: 22rem;
    height: auto;
    position: relative;
    backdrop-filter: blur(4px);
}
.login-header h1 {
    font-size: 24px;
    margin-bottom: 20px;
    font-weight: bold;
    text-align: center;
}
.submit-btn {
    background-color: #0f766e;
    color: white;
    margin-top: 20px;
}
.submit-btn:hover {
    background-color: #115e59;
}
.v-text-field {
    margin-bottom: 20px;
}
.lang-switch {
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 10;
}
</style>