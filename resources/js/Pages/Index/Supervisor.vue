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
        <div class="row">
            <div class="col-12 mb-4">
                <div class="panel-general text-primary-emphasis p-4 pb-md-5 rounded-3">
                    <h4 class="mb-4">Panel general</h4>
                    <div class="row">
                        <div class="col-12 col-sm-6 col-md-3 mb-3 mb-md-0 text-center">
                            <div class="fs-1 fw-semibold">12/15</div>
                            <div>pacientes activos</div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3 mb-3 mb-md-0 text-center">
                            <div class="fs-1 fw-semibold">5</div>
                            <div>alertas</div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3 mb-3 mb-sm-0 text-center">
                            <div class="fs-1 fw-semibold">?</div>
                            <div>adherencia promedio</div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3 text-center">
                            <div class="fs-1 fw-semibold">3</div>
                            <div>interacciones pendientes</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="mb-4 text-primary-emphasis">Usuarios</h3>

        <Usuarios :usuarios :roles :paises :centrosSalud :supervisores :filtros :urlBase="route('dashboard.index')" />
    </Dashboard>
</template>

<style scoped>

</style>
