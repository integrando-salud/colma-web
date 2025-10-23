<script setup>
import { Head } from "@inertiajs/vue3";
import Dashboard from '@/Components/Layouts/Dashboard.vue';
import PanelGeneral from "@/Components/Layouts/PanelGeneral.vue";
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
    centrosSalud: {
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
    <Head title="Inicio" />

    <Dashboard>
        <PanelGeneral />

        <h3 class="mb-4 text-primary-emphasis">Usuarios</h3>

        <Usuarios :usuarios :roles :paises :centrosSalud :supervisores :filtros :urlBase="route('dashboard.index')" />
    </Dashboard>
</template>

<style scoped>

</style>
