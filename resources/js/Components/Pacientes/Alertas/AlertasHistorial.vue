<script setup>
import Table from "@/Components/Table.vue";
import Pagination from "@/Components/Pagination.vue";
import Modal from "@/Components/Modal.vue";
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref, useTemplateRef } from "vue";
import { useNotificationStore } from "@/Stores/notification";

const props = defineProps({
    alertas: {
        type: Object,
        required: true,
    }
});

const page = usePage();
const notification = useNotificationStore();
const alertaAEliminar = ref(null);
const formEliminar = useForm({ interaccion: null });
const modalEliminarInteraccion = useTemplateRef('modalEliminarInteraccion');
const eliminando = ref(false);

const puedeEliminar = computed(() => {
    return page.props.usuario.rol?.nombre === 'superusuario' || page.props.usuario.rol?.nombre === 'supervisor_pais';
});

const showModalEliminarInteraccion = (alerta) => {
    alertaAEliminar.value = alerta;
    formEliminar.interaccion = alerta.interaccion_id;
    modalEliminarInteraccion.value.showModal();
};

const hideModalEliminarInteraccion = () => modalEliminarInteraccion.value.hideModal();

const handleCloseModalEliminarInteraccion = () => {
    formEliminar.interaccion = null;
    alertaAEliminar.value = null;
};

const eliminarInteraccion = () => {
    formEliminar.post(route('interacciones.delete'), {
        onBefore: () => eliminando.value = true,
        onSuccess: () => {
            notification.successNotification('Se eliminó con éxito.');
            hideModalEliminarInteraccion();
        },
        onError: () => notification.errorNotification('Error al eliminar.'),
        onFinish: () => eliminando.value = false,
    });
};
</script>

<template>
    <div class="col-12">
        <h4 class="text-primary-emphasis">Historial de alertas</h4>
    </div>
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
                    <th></th>
                </tr>
            </template>

            <tr v-for="alerta in alertas.clinicas.data" :key="alerta.id">
                <td>{{ alerta.descripcion }}</td>
                <td>{{ alerta.valor }}</td>
                <td>{{ alerta.rango_normal }}</td>
                <td>{{ alerta.fecha }}</td>
                <td>{{ alerta.fecha_contacto }}</td>
                <td>{{ alerta.recomendacion }}</td>
                <td class="text-center">
                    <Link
                        v-if="alerta.interaccion_id"
                        :href="route('dashboard.pacientes.interacciones', {
                            paciente: alerta.paciente.id,
                            contacto: alerta.interaccion_id,
                        })"
                        class="btn btn-secondary btn-sm text-white m-1"
                    >
                        Ver más
                    </Link>
                    <template v-if="puedeEliminar && alerta.interaccion_id">
                        <a
                            :href="route('dashboard.pacientes.editInteraccion', {
                                paciente: alerta.paciente.id,
                                interaccion: alerta.interaccion_id,
                            })"
                            class="btn btn-success btn-sm text-white m-1"
                        >
                            Editar
                        </a>
                        <button
                            type="button"
                            class="btn btn-danger btn-sm text-white m-1"
                            @click="showModalEliminarInteraccion(alerta)"
                        >
                            Eliminar
                        </button>
                    </template>
                </td>
            </tr>

            <template #footer>
                <tr>
                    <td colspan="7" class="text-center small border-top">
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
                    <th></th>
                </tr>
            </template>

            <tr v-for="alerta in alertas.operativas.data" :key="alerta.id">
                <td>{{ alerta.descripcion }}</td>
                <td>{{ alerta.fecha }}</td>
                <td>{{ alerta.recomendacion }}</td>
                <td class="text-center">
                    <Link
                        v-if="alerta.interaccion_id"
                        :href="route('dashboard.pacientes.interacciones', {
                            paciente: alerta.paciente.id,
                            contacto: alerta.interaccion_id,
                        })"
                        class="btn btn-secondary btn-sm text-white m-1"
                    >
                        Ver más
                    </Link>
                    <template v-if="puedeEliminar && alerta.interaccion_id">
                        <a
                            :href="route('dashboard.pacientes.editInteraccion', {
                                paciente: alerta.paciente.id,
                                interaccion: alerta.interaccion_id,
                            })"
                            class="btn btn-success btn-sm text-white m-1"
                        >
                            Editar
                        </a>
                        <button
                            type="button"
                            class="btn btn-danger btn-sm text-white m-1"
                            @click="showModalEliminarInteraccion(alerta)"
                        >
                            Eliminar
                        </button>
                    </template>
                </td>
            </tr>

            <template #footer>
                <tr>
                    <td colspan="4" class="text-center small border-top">
                        Viendo {{ alertas.operativas.meta.from }} a {{ alertas.operativas.meta.to }} de {{ alertas.operativas.meta.total }} alertas
                    </td>
                </tr>
            </template>
        </Table>
        <Pagination v-if="alertas.operativas.meta.last_page > 1" :prev-url="alertas.operativas.links.prev" :next-url="alertas.operativas.links.next" />
    </div>

    <Modal id="modalEliminarInteraccion" ref="modalEliminarInteraccion" title="Eliminar interacción" :loading="eliminando" @modalClosed="handleCloseModalEliminarInteraccion">
        <p>Se eliminará la interacción asociada a la alerta del {{ alertaAEliminar?.fecha }}.</p>
        <p>¿Desea continuar?</p>

        <template #footer>
            <template v-if="!eliminando">
                <button type="button" class="btn btn-grey fw-semibold shadow" @click="hideModalEliminarInteraccion">
                    No, cancelar
                </button>
                <button type="button" class="btn btn-secondary text-white fw-semibold shadow" @click="eliminarInteraccion">
                    Sí, continuar
                </button>
            </template>
            <button v-else type="button" class="btn btn-secondary text-white fw-semibold shadow" disabled>
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Eliminando interacción, espere...
            </button>
        </template>
    </Modal>
</template>

<style scoped>

</style>
