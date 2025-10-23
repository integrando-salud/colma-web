<script setup>
import { Head } from "@inertiajs/vue3";
import Dashboard from '@/Components/Layouts/Dashboard.vue';
import PanelGeneral from "@/Components/Layouts/PanelGeneral.vue";
import Pacientes from "@/Components/Pages/Pacientes.vue";

defineProps({
    pacientes: Object,
    filtros: Object,
});
</script>

<template>
    <Head title="Inicio" />

    <Dashboard>
        <PanelGeneral />

        <h3 class="mb-4 text-primary-emphasis">Pacientes</h3>

        <Pacientes :pacientes :filtros :urlBase="route('dashboard.index')" />
    </Dashboard>
</template>

<style scoped>

</style>
