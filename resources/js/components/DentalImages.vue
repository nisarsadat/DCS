<template>
    <button
        v-if="variant === 'icon'"
        type="button"
        class="photo-icon"
        :title="title || t('photos')"
        @click.stop="open = true"
    >
        <v-icon size="14">mdi-image-outline</v-icon>
    </button>

    <div v-else class="slide" @mouseenter="paused = true" @mouseleave="paused = false">
        <img v-if="current" :src="current" :alt="title || t('photos')" />
        <div v-else class="slide-empty">
            <v-icon size="28">mdi-image-off-outline</v-icon>
            <span>{{ t("noPhotos") }}</span>
        </div>
        <template v-if="urls.length > 1">
            <button type="button" class="nav prev" @click.stop="step(-1)">
                <v-icon size="18">mdi-chevron-left</v-icon>
            </button>
            <button type="button" class="nav next" @click.stop="step(1)">
                <v-icon size="18">mdi-chevron-right</v-icon>
            </button>
            <div class="dots">
                <button
                    v-for="(url, index) in urls"
                    :key="url + index"
                    type="button"
                    :class="{ on: index === currentIndex }"
                    @click.stop="currentIndex = index"
                ></button>
            </div>
        </template>
    </div>

    <Teleport to="body">
        <div v-if="open" class="photo-dialog" @click.self="open = false">
            <div class="photo-frame">
                <div class="photo-top">
                    <strong>{{ title || t("photos") }}</strong>
                    <button type="button" @click="open = false">
                        <v-icon size="18">mdi-close</v-icon>
                    </button>
                </div>
                <img v-if="current" :src="current" :alt="title || t('photos')" />
                <p v-else class="dialog-empty">{{ t("noPhotos") }}</p>
                <div v-if="urls.length > 1" class="dialog-nav">
                    <button type="button" @click="step(-1)">
                        <v-icon size="20">mdi-chevron-left</v-icon>
                    </button>
                    <span>{{ currentIndex + 1 }} / {{ urls.length }}</span>
                    <button type="button" @click="step(1)">
                        <v-icon size="20">mdi-chevron-right</v-icon>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { useI18n } from "vue-i18n";

const props = defineProps({
    images: { type: Array, default: () => [] },
    title: { type: String, default: "" },
    variant: { type: String, default: "icon" },
});

const { t } = useI18n();
const open = ref(false);
const paused = ref(false);
const currentIndex = ref(0);

const urls = computed(() =>
    (props.images || [])
        .map((image) => (typeof image === "string" ? image : image?.url))
        .filter(Boolean)
);

const current = computed(() => urls.value[currentIndex.value] || "");

const step = (direction) => {
    if (!urls.value.length) return;
    currentIndex.value = (currentIndex.value + direction + urls.value.length) % urls.value.length;
};

watch(urls, () => {
    currentIndex.value = 0;
});

let timer;
watch(
    [urls, paused, open],
    () => {
        clearInterval(timer);
        const playing = props.variant === "slide" ? !paused.value : open.value;
        if (urls.value.length > 1 && playing) {
            timer = setInterval(() => step(1), 3200);
        }
    },
    { immediate: true }
);

onBeforeUnmount(() => clearInterval(timer));
</script>

<style scoped>
.photo-icon {
    width: 22px;
    height: 22px;
    margin-inline-start: 6px;
    border: 0;
    border-radius: 999px;
    background: #ccfbf1;
    color: #0f766e;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    vertical-align: middle;
    flex: 0 0 auto;
}
.slide {
    position: relative;
    width: 220px;
    height: 132px;
    border-radius: 16px;
    overflow: hidden;
    background: #f1f5f9;
}
.slide img,
.photo-frame img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.slide-empty,
.dialog-empty {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.3rem;
    color: #94a3b8;
    font-size: 0.78rem;
}
.nav,
.dots button,
.photo-top button,
.dialog-nav button {
    border: 0;
    cursor: pointer;
}
.nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 26px;
    height: 26px;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.55);
    color: white;
}
.prev { inset-inline-start: 6px; }
.next { inset-inline-end: 6px; }
.dots {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 8px;
    display: flex;
    justify-content: center;
    gap: 5px;
}
.dots button {
    width: 6px;
    height: 6px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.7);
    padding: 0;
}
.dots button.on { background: white; width: 14px; }
.photo-dialog {
    position: fixed;
    inset: 0;
    z-index: 4000;
    background: rgba(15, 23, 42, 0.72);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
}
.photo-frame {
    width: min(720px, 100%);
    background: #0f172a;
    border-radius: 18px;
    overflow: hidden;
    color: white;
}
.photo-frame img {
    height: min(68vh, 480px);
    background: #020617;
}
.photo-top,
.dialog-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.7rem 0.9rem;
}
.photo-top button,
.dialog-nav button {
    width: 32px;
    height: 32px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    color: white;
}
.dialog-empty { height: 220px; }
:global(.v-theme--dark) .photo-icon { background: #134e4a; color: #99f6e4; }
:global(.v-theme--dark) .slide { background: #1e293b; }
:global(.linked-type) {
    display: inline-flex;
    align-items: center;
}
</style>
