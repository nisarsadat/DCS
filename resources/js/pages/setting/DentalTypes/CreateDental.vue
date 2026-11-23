<template>
    <div>
        <v-dialog
            transition="dialog-top-transition"
            width="50rem"
            v-model="SettingRepository.createDialog"
            class="rtl-dialog"
        >
            <template v-slot:default="{ isActive }">
                <v-card class="px-3">
                    <v-card-title
                        class="px-2 pt-4 d-flex justify-space-between"
                    >
                        <h2 class="font-weight-bold pl-4">
                            {{
                                SettingRepository.isEditMode
                                    ? t("update")
                                    : t("create")
                            }}
                        </h2>
                        <v-btn variant="text" @click="isActive.value = false">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </v-card-title>
                    <v-divider class="border-opacity-100 mx-6"></v-divider>

                    <v-card-text>
                        <v-form ref="formRef" class="pt-4">
                            <v-text-field
                                v-model="formData.name"
                                variant="outlined"
                                :label="$t('toothType')"
                                class="pb-4"
                                density="compact"
                                :rules="[rules.required]"
                            ></v-text-field>

                            <v-text-field
                                v-model="formData.description"
                                variant="outlined"
                                :label="$t('details')"
                                density="compact"
                                class="pb-4"
                                :rules="[rules.required]"
                            ></v-text-field>

                            <div class="photo-picker">
                                <label>
                                    {{ t("addPhotos") }}
                                    <input type="file" accept="image/*" multiple @change="addFiles" />
                                </label>
                                <div class="photo-previews">
                                    <div v-for="image in formData.images" :key="image.id" class="preview">
                                        <img :src="image.url" :alt="formData.name" />
                                        <button type="button" @click="removeExisting(image)">×</button>
                                    </div>
                                    <div v-for="(item, index) in formData.files" :key="item.preview" class="preview">
                                        <img :src="item.preview" :alt="formData.name" />
                                        <button type="button" @click="removeFile(index)">×</button>
                                    </div>
                                </div>
                            </div>
                        </v-form>
                    </v-card-text>

                    <div class="d-flex flex-row-reverse mb-6 mx-6">
                        <v-btn color="#112F53" class="px-4" @click="save">
                            {{
                                SettingRepository.isEditMode
                                    ? t("update")
                                    : t("submit")
                            }}
                        </v-btn>
                    </div>
                </v-card>
            </template>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, reactive } from "vue";
import { useSettingRepository } from "@/store/SettingRepository";
import { useI18n } from "vue-i18n";
const { t} = useI18n();

const SettingRepository = useSettingRepository();
const formRef = ref(null);

const formData = reactive({
    id: SettingRepository.dental.id,
    name: SettingRepository.dental.name,
    description: SettingRepository.dental.description,
    images: [...(SettingRepository.dental.images || [])],
    files: [],
    removeImages: [],
});
const rules = {
    required: (value) => !!value || "This field is required.",

    name: (value) =>
        /^[a-zA-Z\u0600-\u06FF\s]*$/.test(value) ||
        "Please enter a valid name.",
};

const addFiles = (event) => {
    for (const file of event.target.files || []) {
        formData.files.push({ file, preview: URL.createObjectURL(file) });
    }
    event.target.value = "";
};

const removeExisting = (image) => {
    formData.removeImages.push(image.id);
    formData.images = formData.images.filter((item) => item.id !== image.id);
};

const removeFile = (index) => {
    URL.revokeObjectURL(formData.files[index].preview);
    formData.files.splice(index, 1);
};

const save = async () => {
    const isValid = await formRef.value.validate();
    const ok = isValid === true || isValid?.valid === true;
    if (ok) {
        if (SettingRepository.isEditMode) {
            await SettingRepository.UpdateDental(formData.id, formData);
        } else {
            await SettingRepository.CreateDental(formData);
        }
    }
};
</script>

<style scoped>
.photo-picker label {
    display: inline-flex;
    align-items: center;
    height: 36px;
    padding: 0 0.9rem;
    border-radius: 999px;
    background: #f0fdfa;
    color: #0f766e;
    cursor: pointer;
    font-size: 0.88rem;
}
.photo-picker input { display: none; }
.photo-previews {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
    margin-top: 0.8rem;
}
.preview {
    position: relative;
    width: 84px;
    height: 84px;
}
.preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 12px;
}
.preview button {
    position: absolute;
    top: 4px;
    inset-inline-end: 4px;
    width: 20px;
    height: 20px;
    border: 0;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.72);
    color: white;
    cursor: pointer;
    line-height: 1;
}
</style>
