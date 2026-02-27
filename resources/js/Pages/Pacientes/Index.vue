<script setup>
import { Head } from "@inertiajs/vue3";
import Dashboard from '@/Components/Layouts/Dashboard.vue';
import Pacientes from "@/Components/Pages/Pacientes.vue";

defineProps({
    pacientes: Object,
    promotores: {
        type: Object,
        default: null
    },
    filtros: Object,
});
</script>

<template>
    <Head title="Pacientes" />

    <Dashboard title="Pacientes">
        <Pacientes :filtros :pacientes :promotores :urlBase="route('dashboard.pacientes.index')" />
    </Dashboard>
</template>

<style scoped>

</style>
