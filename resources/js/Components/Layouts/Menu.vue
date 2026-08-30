<script setup>
import { computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import MenuItem from "@/Components/Layouts/MenuItem.vue";

const page = usePage();

const showMenuUsuarios = computed(() => {
    if (!page.props.usuario) {
        return false;
    }

    const roles = ['superusuario', 'supervisor_pais', 'supervisor'];

    return roles.find((role) => role === page.props.usuario.rol.nombre);
});

const logout = () => {
    router.post(route('dashboard.logout'));
};
</script>

<template>
    <nav class="navbar navbar-expand-md">
        <div class="container-xxl">
            <img
                class="me-4"
                :src="$page.props.app.logo"
                :alt="$page.props.app.nombre"
                style="max-height: 60px;"
            >
            <a :href="route('dashboard.index')" class="btn btn-secondary text-white me-2">
                Inicio
            </a>
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
                    <li class="nav-item" v-if="showMenuUsuarios">
                        <MenuItem :link="route('dashboard.indicadores.index')" title="Indicadores" />
                    </li>
                </ul>
                <button type="button" class="btn btn-secondary text-white" @click="logout">
                    <i class="fa fa-sign-out"></i>
                    Cerrar sesión
                </button>
            </div>
        </div>
    </nav>
</template>

<style scoped>
.navbar-toggler, .navbar-toggler:focus {
    border: none;
}
</style>
