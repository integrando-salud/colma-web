<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import MenuItem from "@/Components/Layouts/MenuItem.vue";

const page = usePage();

const showMenuUsuarios = computed(() => {
    if (!page.props.usuario) {
        return false;
    }

    const roles = ['superusuario', 'supervisor', 'supervisor_pais'];

    return roles.find((role) => role === page.props.usuario.rol.nombre);
});
</script>

<template>
    <nav class="navbar navbar-expand-md">
        <div class="container-fluid">
            <MenuItem :link="route('dashboard.index')" :title="$page.props.app.nombre" class="mx-md-5 fw-bold" />
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbar">
                <ul class="navbar-nav me-auto mb-2 mb-md-0">
                    <li class="nav-item" :class="{ 'me-md-3': showMenuUsuarios }">
                        <MenuItem :link="route('dashboard.pacientes.index')" title="Pacientes" />
                    </li>
                    <li class="nav-item" v-if="showMenuUsuarios">
                        <MenuItem :link="route('dashboard.usuarios.index')" title="Administración de usuarios" />
                    </li>
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
