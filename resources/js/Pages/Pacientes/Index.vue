<script setup>
import { Head } from "@inertiajs/vue3";
import Dashboard from '@/Components/Layouts/Dashboard.vue';
import Pacientes from "@/Components/Pages/Pacientes.vue";
</script>

<template>
    <Head title="Pacientes" />

    <Dashboard title="Pacientes">
        <Pacientes />
    </Dashboard>
</template>

<style scoped>

</style>
