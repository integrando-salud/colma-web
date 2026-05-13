<script setup>
import { computed, watch, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useNotificationStore } from '@/Stores/notification';
import Menu from '@/Components/Layouts/Menu.vue';
import Notification from "@/Components/Notification.vue";

const page = usePage();
const notification = useNotificationStore();
const successNotification = computed(() => page.props.success);
const errorNotification = computed(() => page.props.error);

const showMenu = computed(() => page.props.usuario);

watch(successNotification, (value) => {
    if (value) {
        notification.successNotification(value);
    }
});

watch(errorNotification, (value) => {
    if (value) {
        notification.errorNotification(value);
    }
});

onMounted(() => {
    if (successNotification.value) {
        notification.successNotification(successNotification.value);
    }

    if (errorNotification.value) {
        notification.errorNotification(errorNotification.value);
    }
});
</script>

<template>
    <Menu v-if="showMenu" />

    <div class="container-xxl">
        <slot />
    </div>

    <Notification v-model:message="notification.message" :color="notification.color" />
</template>

<style scoped>

</style>
