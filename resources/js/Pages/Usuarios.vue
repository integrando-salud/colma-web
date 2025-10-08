<script setup>
import { Head } from "@inertiajs/vue3";
import Dashboard from '@/Components/Layouts/Dashboard.vue';
import Usuarios from '@/Components/Pages/Usuarios.vue';

defineProps({
    usuarios: Object,
    roles: Array,
    paises: Array,
    centros_salud: Array,
    filtros: Object,
});
</script>

<template>
    <Head title="Usuarios" />

    <Dashboard title="Administración de usuarios">
        <Usuarios :usuarios :roles :paises :centros_salud :filtros :urlBase="route('dashboard.usuarios.index')" />
    </Dashboard>
</template>

<style scoped>

</style>
