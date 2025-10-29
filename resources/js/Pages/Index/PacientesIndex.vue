<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import Dashboard from '@/Components/Layouts/Dashboard.vue';
import PanelGeneral from "@/Components/Layouts/PanelGeneral.vue";
import Pacientes from "@/Components/Pages/Pacientes.vue";

defineProps({
    pacientes: Object,
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

        <h3 class="mb-4">Pacientes</h3>

        <Pacientes :pacientes :filtros :urlBase="route('dashboard.index')" />
    </Dashboard>
</template>

<style scoped>

</style>
