<script setup>
import { computed, watch, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useNotificationStore } from '@/Stores/notification';
import MenuSupervisor from '@/Components/Menus/MenuSupervisor.vue';
import Notification from "@/Components/Notification.vue";

const page = usePage();
const notification = useNotificationStore();
const successNotification = computed(() => page.props.success);
const errorNotification = computed(() => page.props.error);

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
    <MenuSupervisor v-if="$page.props.user?.rol.name.includes('superadmin', 'supervisor')" />

    <div class="container-fluid">
        <slot />
    </div>

    <Notification v-model:message="notification.message" :color="notification.color" />
</template>

<style scoped>

</style>
