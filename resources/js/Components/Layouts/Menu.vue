<script setup>
import MenuItem from "@/Components/Layouts/MenuItem.vue";
</script>

<template>
    <nav class="navbar navbar-expand-md">
        <div class="container-fluid">
            <MenuItem :link="route('dashboard.index')" :title="$page.props.app_name" class="mx-md-5 fw-bold" />
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbar">
                <ul class="navbar-nav me-auto mb-2 mb-md-0">
                    <slot />
                </ul>
                <a :href="route('dashboard.logout')" class="text-primary-emphasis text-decoration-none fs-6 me-md-1">
                    <i class="fa fa-sign-out"></i>
                    Cerrar sesión
                </a>
            </div>
        </div>
    </nav>
</template>

<style scoped>
.navbar-toggler, .navbar-toggler:focus {
    border: none;
}
</style>
