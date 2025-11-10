<script setup>
import { ref } from "vue";
import Table from "@/Components/Table.vue";

const visible = ref('pendientes');

defineEmits(['verFormulario']);
</script>

<template>
    <div class="col-12">
        <h4 class="fs-5 text-primary-emphasis">Alertas</h4>
    </div>
    <div class="row">
        <div class="col-12 mb-2">
            <button
                type="button"
                class="btn btn-outline-secondary me-2"
                :class="{ active: visible === 'pendientes', 'text-white': visible === 'pendientes' }"
                @click="visible = 'pendientes'"
            >
                Pendientes de revisión (3)
            </button>
            <button
                type="button"
                class="btn btn-outline-secondary"
                :class="{ active: visible === 'historicas', 'text-white': visible === 'historicas' }"
                @click="visible = 'historicas'"
            >
                Históricas
            </button>
        </div>
    </div>
    <div class="row" v-if="visible === 'pendientes'">
        <div class="col-12 mb-2">
            <h4 class="fs-5 text-primary-emphasis">Pendientes de revisión</h4>
        </div>
        <div class="col-12">
            <h5 class="text-primary-emphasis">Alertas clínicas</h5>
        </div>
        <div class="col-12">
            <Table>
                <template #header>
                    <tr>
                        <th>Alerta</th>
                        <th>Valor</th>
                        <th>Rango normal</th>
                        <th>Fecha de medición</th>
                        <th>Fecha máxima de contacto</th>
                        <th>Recomendación</th>
                        <th>Estado</th>
                    </tr>
                </template>

                <tr>
                    <td>Glucosa elevada/alta</td>
                    <td>120</td>
                    <td>80 - 120</td>
                    <td>23/07/2025</td>
                    <td>23/07/2025</td>
                    <td>Indicar que vaya a consulta</td>
                    <td>
                        <button type="button" class="btn btn-secondary btn-sm text-white fw-semibold shadow" @click="$emit('verFormulario', 1)">
                            Ir al formulario
                        </button>
                    </td>
                </tr>
            </Table>
        </div>
        <div class="col-12">
            <h5 class="text-primary-emphasis">Alertas operativas</h5>
        </div>
        <div class="col-12">
            <Table>
                <template #header>
                    <tr>
                        <th>Alerta</th>
                        <th>Fecha de alerta</th>
                        <th>Recomendación</th>
                        <th>Estado</th>
                    </tr>
                </template>

                <tr>
                    <td>No cumplió (sin actividad)</td>
                    <td>23/07/2025</td>
                    <td>Contactar y registrar</td>
                    <td>
                        <button type="button" class="btn btn-secondary btn-sm text-white fw-semibold shadow">
                            Ir al formulario
                        </button>
                    </td>
                </tr>
            </Table>
        </div>
    </div>
    <div class="row" v-if="visible === 'historicas'">
        <div class="col-12 mb-2">
            <h4 class="fs-5 text-primary-emphasis">Históricas</h4>
        </div>
        <div class="col-12">
            <h5 class="text-primary-emphasis">Alertas clínicas</h5>
        </div>
        <div class="col-12">
            <Table>
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

                <tr>
                    <td>Glucosa elevada/alta</td>
                    <td>120</td>
                    <td>80 - 120</td>
                    <td>23/07/2025</td>
                    <td>23/07/2025</td>
                    <td>Indicar que vaya a consulta</td>
                </tr>
            </Table>
        </div>
        <div class="col-12">
            <h5 class="text-primary-emphasis">Alertas operativas</h5>
        </div>
        <div class="col-12">
            <Table>
                <template #header>
                    <tr>
                        <th>Alerta</th>
                        <th>Fecha de alerta</th>
                        <th>Recomendación</th>
                    </tr>
                </template>

                <tr>
                    <td>No cumplió (sin actividad)</td>
                    <td>23/07/2025</td>
                    <td>Contactar y registrar</td>
                </tr>
            </Table>
        </div>
    </div>
</template>

<style scoped>

</style>
