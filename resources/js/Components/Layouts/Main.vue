<script setup>
import { computed, watch, onMounted } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
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
    <div class="container-fluid">
        <div id="top-bar" class="row position-fixed w-100">
            <div class="col-12 d-flex justify-content-between justify-content-sm-start align-items-center">
                <Link :href="route('dashboard.index')" class="fs-4 me-sm-4 fw-bold ms-md-5 text-primary-emphasis text-decoration-none">
                    {{ $page.props.app_name }}
                </Link>
                <MenuSupervisor v-if="$page.props.user?.rol.name.includes('superadmin', 'supervisor')" />
            </div>
        </div>

        <div id="content">
            <slot />
        </div>
    </div>

    <Notification v-model:message="notification.message" :color="notification.color" />
</template>

<style scoped>

</style>
