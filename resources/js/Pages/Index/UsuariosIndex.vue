<script setup>
import { Head, usePage } from "@inertiajs/vue3";
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

const page = usePage();
</script>

<template>
    <Head title="Inicio" />

    <Dashboard>
        <h3 class="text-primary-emphasis">
            ¡Bienvenid{{ page.props.usuario.sexo === 'F' ? 'a' : 'o' }} {{ page.props.usuario.nombre }}!
        </h3>

        <PanelGeneral />

        <h3 class="mb-4">Usuarios</h3>

        <Usuarios :usuarios :roles :paises :centrosSalud :supervisores :filtros :urlBase="route('dashboard.index')" />
    </Dashboard>
</template>

<style scoped>

</style>
