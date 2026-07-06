<script setup>
import Table from "@/Components/Table.vue";
import {router} from '@inertiajs/vue3'
import {useTemplateRef} from "vue";
import {ref} from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    alertas: {
        type: Object,
        required: true,
    },
    userRoles: Array,

});


const cancelarAlerta = () => {
    hideModalConfirmar();
    router.post(route('alertas.cancelar', {
         alerta: selectedAlertId.value,
    }))
}

const modalConfirmar = useTemplateRef('modalConfirmar');


const selectedAlertId = ref(null);

const showModalConfirmar = (id) => {
    selectedAlertId.value = id;
    modalConfirmar.value.showModal();
};

const hideModalConfirmar = () => {
    modalConfirmar.value.hideModal();
};


</script>

<template>
    <div class="row">
        <div class="col-12">
            <h4 class="text-primary-emphasis">Alertas pendientes</h4>
        </div>
        <div class="col-12">
            <h5 class="text-primary-emphasis">Alertas clínicas</h5>
        </div>
        <div class="col-12">
            <div class="alert alert-secondary" v-if="alertas.clinicas.length === 0">No se encontraron alertas clínicas
                pendientes.
            </div>
            <Table v-else>
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

                <tr v-for="alerta in alertas.clinicas" :key="alerta.id">
                    <td>{{ alerta.descripcion }}</td>
                    <td>{{ alerta.valor }}</td>
                    <td>{{ alerta.rango_normal }}</td>
                    <td>{{ alerta.fecha }}</td>
                    <td>{{ alerta.fecha_contacto }}</td>
                    <td class="recomendacion">{{ alerta.recomendacion }}</td>
                    <td>
                        <a v-if="alerta.paciente.activo"
                           :href="route('dashboard.pacientes.createInteraccion', {
                                paciente: alerta.paciente.id,
                                alerta: alerta.id,
                            })"
                           class="btn btn-secondary btn-sm text-white fw-semibold shadow"
                        >
                            Ir al formulario
                        </a>
                    </td>
                </tr>
            </Table>
        </div>
        <div class="col-12">
            <h5 class="text-primary-emphasis">Alertas operativas</h5>
        </div>
        <div class="col-12">
            <div class="alert alert-secondary" v-if="alertas.operativas.length === 0">No se encontraron alertas
                operativas pendientes.
            </div>
            <Table v-else>
                <template #header>
                    <tr>
                        <th>Alerta</th>
                        <th>Fecha de alerta</th>
                        <th>Fecha máxima de contacto</th>
                        <th>Recomendación</th>
                        <th>Estado</th>
                    </tr>
                </template>

                <tr v-for="alerta in alertas.operativas" :key="alerta.id">
                    <td>{{ alerta.descripcion }}</td>
                    <td>{{ alerta.fecha }}</td>
                    <td>{{ alerta.fecha_contacto }}</td>
                    <td class="recomendacion">{{ alerta.recomendacion }}</td>
                    <td>
                        <a :href="route('dashboard.pacientes.createInteraccion', {
                                paciente: alerta.paciente.id,
                                alerta: alerta.id,
                            })"
                           class="btn btn-secondary btn-sm text-white fw-semibold shadow"
                        >
                            Ir al formulario
                        </a>
                    </td>
                </tr>
            </Table>
        </div>
        <div class="col-12">
            <h5 class="text-primary-emphasis">Alertas calls</h5>
        </div>
        <div class="col-12">
            <div class="alert alert-secondary" v-if="alertas.calls.length === 0">No se encontraron alertas calls
                pendientes.
            </div>
            <Table v-else>
                <template #header>
                    <tr>
                        <th>Alerta</th>
                        <th>Fecha de alerta</th>
                        <th>Fecha máxima de contacto</th>
                        <th>Recomendación</th>
                        <th>Estado</th>
                    </tr>
                </template>

                <tr v-for="alerta in alertas.calls" :key="alerta.id">
                    <td>{{ alerta.descripcion }}</td>
                    <td>{{ alerta.fecha }}</td>
                    <td>{{ alerta.fecha_contacto }}</td>
                    <td class="recomendacion">{{ alerta.recomendacion }}</td>
                    <td>
                        <a :href="route('dashboard.pacientes.createInteraccion', {
                                paciente: alerta.paciente.id,
                                alerta: alerta.id,
                            })"
                           class="btn btn-secondary btn-sm text-white fw-semibold shadow"
                        >
                            Ir al formulario
                        </a>
                        <button
                            v-if="userRoles.some(role => ['supervisor', 'supervisor_pais', 'superusuario'].includes(role))"
                            type="button" class="btn btn-secondary btn-sm text-white fw-semibold shadow mt-2"
                                @click="showModalConfirmar(alerta.id)">
                            Cancelar
                        </button>
                    </td>
                </tr>
            </Table>
        </div>

    </div>

    <Modal id="modalConfirmar" ref="modalConfirmar" title="Confirmar">
        <div class="row">
            <div class="col-12">
                <p>
                    ¿Estás seguro/a que deseas guardar la información?
                </p>
            </div>
        </div>

        <template #footer>
            <button type="button" class="btn btn-grey fw-semibold shadow" @click="hideModalConfirmar">
                No
            </button>
            <button type="button" class="btn btn-secondary text-white fw-semibold shadow" @click="cancelarAlerta()">
                Si
            </button>
        </template>
    </Modal>
</template>

<style scoped>
.recomendacion {
    white-space: pre-wrap;
}
</style>
