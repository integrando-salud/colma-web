<script setup>
import Table from "@/Components/Table.vue";
import Pagination from "@/Components/Pagination.vue";

const props = defineProps({
    alertas: {
        type: Object,
        required: true,
    }
});
</script>

<template>
    <div class="col-12">
        <h5 class="text-primary-emphasis">Alertas clínicas</h5>
    </div>
    <div class="col-12">
        <div class="alert alert-secondary" v-if="alertas.clinicas.data.length === 0">No se encontraron alertas clínicas pasadas.</div>
        <Table v-else>
            <template #header>
                <tr>
                    <th>Alerta</th>
                    <th>Valor</th>
                    <th>Rango normal</th>
                    <th>Fecha de medición</th>
                    <th>Fecha máxima de contacto</th>
                    <th>Recomendación</th>
                </tr>
            </template>

            <tr v-for="alerta in alertas.clinicas.data" :key="alerta.id">
                <td>{{ alerta.descripcion }}</td>
                <td>{{ alerta.valor }}</td>
                <td>{{ alerta.rango_normal }}</td>
                <td>{{ alerta.fecha }}</td>
                <td>{{ alerta.fecha_contacto }}</td>
                <td>{{ alerta.recomendacion }}</td>
            </tr>

            <template #footer>
                <tr>
                    <td colspan="6" class="text-center small border-top">
                        Viendo {{ alertas.clinicas.meta.from }} a {{ alertas.clinicas.meta.to }} de {{ alertas.clinicas.meta.total }} alertas
                    </td>
                </tr>
            </template>
        </Table>
        <Pagination v-if="alertas.clinicas.meta.last_page > 1" :prev-url="alertas.clinicas.links.prev" :next-url="alertas.clinicas.links.next" />
    </div>
    <div class="col-12">
        <h5 class="text-primary-emphasis">Alertas operativas</h5>
    </div>
    <div class="col-12">
        <div class="alert alert-secondary" v-if="alertas.operativas.data.length === 0">No se encontraron alertas operativas pasadas.</div>
        <Table v-else>
            <template #header>
                <tr>
                    <th>Alerta</th>
                    <th>Fecha de alerta</th>
                    <th>Recomendación</th>
                </tr>
            </template>

            <tr v-for="alerta in alertas.operativas.data" :key="alerta.id">
                <td>{{ alerta.descripcion }}</td>
                <td>{{ alerta.fecha }}</td>
                <td>{{ alerta.recomendacion }}</td>
            </tr>

            <template #footer>
                <tr>
                    <td colspan="3" class="text-center small border-top">
                        Viendo {{ alertas.operativas.meta.from }} a {{ alertas.operativas.meta.to }} de {{ alertas.operativas.meta.total }} alertas
                    </td>
                </tr>
            </template>
        </Table>
    </div>
</template>

<style scoped>

</style>
