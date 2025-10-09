<script setup>
import { Head } from "@inertiajs/vue3";
import Dashboard from '@/Components/Layouts/Dashboard.vue';
import Usuarios from '@/Components/Pages/Usuarios.vue';

defineProps({
    usuarios: Object,
    roles: {
        type: Array,
        default: () => [],
    },
    paises: {
        type: Array,
        default: () => [],
    },
    centros_salud: {
        type: Array,
        default: () => [],
    },
    supervisores: {
        type: Array,
        default: () => [],
    },
    filtros: Object,
});
</script>

<template>
    <Head title="Usuarios" />

    <Dashboard title="Administración de usuarios">
        <Usuarios :usuarios :roles :paises :centros_salud :supervisores :filtros :urlBase="route('dashboard.usuarios.index')" />
    </Dashboard>
</template>

<style scoped>

</style>
