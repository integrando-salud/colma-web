<script setup>
import { ref, computed, onMounted, useTemplateRef } from "vue";
import { usePage, useForm } from "@inertiajs/vue3";
import { useNotificationStore } from "@/Stores/notification";
import Pagination from "@/Components/Pagination.vue";
import DetalleAlertaInteraccion from "@/Components/Pacientes/DetalleAlertaInteraccion.vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    idPaciente: {
        type: Number,
        required: true,
    },
    interacciones: {
        type: Object,
        required: true,
    },
    interaccion: null
});

const page = usePage();
const notification = useNotificationStore();
const interaccionSelected = ref(null);
const formEliminar = useForm({
    interaccion: null
});
const modalEliminarInteraccion = useTemplateRef('modalEliminarInteraccion');
const eliminando = ref(false);

const puedeEliminar = computed(() => {
    return page.props.usuario.rol?.nombre === 'superusuario' || page.props.usuario.rol?.nombre === 'supervisor_pais';
});

const showModalEliminarInteraccion = (interaccion) => {
    formEliminar.interaccion = interaccion.id;
    modalEliminarInteraccion.value.showModal();
};

const hideModalEliminarInteraccion = () => {
    modalEliminarInteraccion.value.hideModal();
};

const handleCloseModalEliminarInteraccion = () => {
    formEliminar.interaccion = null;
};

const eliminarInteraccion = async () => {
    formEliminar.post(route('interacciones.delete'), {
        onBefore: () => {
            eliminando.value = true;
        },
        onSuccess: () => {
            notification.successNotification('Se eliminó con éxito.');
            formEliminar.interaccion = null;
            hideModalEliminarInteraccion();
        },
        onError: () => {
            notification.errorNotification('Error al eliminar.');
        },
        onFinish: () => {
            eliminando.value = false;
        }
    });
}

onMounted(() => {
    if (props.interaccion) {
        interaccionSelected.value = props.interacciones.data.find((item) => item.id == props.interaccion);
    }
});
</script>

<template>
    <div class="row" v-if="interaccionSelected === null">
        <div class="col-12">
            <h4 class="fs-5 text-primary-emphasis">Historial de interacciones</h4>
        </div>
        <div class="col-12">
            <div class="alert alert-secondary" v-if="interacciones?.data?.length === 0">No se encontraron interacciones</div>
            <template v-else-if="interacciones?.data?.length > 0">
                <div class="row">
                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Fecha y hora</th>
                                        <th>Usuario</th>
                                        <th>Estado</th>
                                        <th>Modalidad</th>
                                        <th>Motivo</th>
                                        <th>Nueva fecha de contacto</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="interaccion in interacciones.data" :key="interaccion.id">
                                        <td>{{ interaccion.fecha + ' ' + interaccion.hora }}</td>
                                        <td>{{ interaccion.usuario.nombre }}</td>
                                        <td>{{ interaccion.contactado_descripcion }}</td>
                                        <td>{{ interaccion.modalidad }}</td>
                                        <td>{{ interaccion.motivo }}</td>
                                        <td>{{ interaccion.fecha_proximo_contacto }}</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-secondary btn-sm text-white me-1" @click="interaccionSelected = interaccion">Ver más</button>
                                            <button v-if="puedeEliminar" type="button" class="btn btn-danger btn-sm text-white" @click="showModalEliminarInteraccion(interaccion)">Eliminar</button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="7" class="text-center small border-top">
                                            Viendo {{ interacciones.meta.from }} a {{ interacciones.meta.to }} de {{ interacciones.meta.total }} interacciones
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                            <Pagination v-if="interacciones.meta.last_page > 1" :prev-url="interacciones.links.prev" :next-url="interacciones.links.next" />
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
    <div class="row" v-else>
        <div class="col-12 mb-2">
            <a :href="route('dashboard.pacientes.interacciones', idPaciente)" class="btn btn-secondary text-white text-decoration-none" @click="interaccionSelected = null">
                <i class="fa fa-arrow-left"></i>
                Volver al historial de interacciones
            </a>
        </div>

        <DetalleAlertaInteraccion v-if="interaccionSelected.alerta" :alerta="interaccionSelected.alerta" />

        <div class="col-12">
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <div class="col-12 col-sm-4 col-xl-4 py-2 align-content-center fw-bold">
                            Usuario
                        </div>
                        <div class="col-12 col-sm-8 col-xl-6 py-2 align-content-center">
                            {{ interaccionSelected.usuario.nombre }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <div class="col-12 col-sm-4 col-xl-4 py-2 align-content-center fw-bold">
                            Resultado del contacto
                        </div>
                        <div class="col-12 col-sm-8 col-xl-6 py-2 align-content-center">
                            {{ interaccionSelected.contactado_descripcion }}
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3" v-if="interaccionSelected.razon_no_contacto">
                    <div class="row">
                        <div class="col-12 col-sm-4 col-xl-4 py-2 align-content-center fw-bold">
                            Razón de no contacto
                        </div>
                        <div class="col-12 col-sm-8 col-xl-6 py-2 align-content-center">
                            {{ interaccionSelected.razon_no_contacto }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <div class="col-12 col-sm-4 col-xl-4 py-2 align-content-center fw-bold">
                            Fecha de contacto
                        </div>
                        <div class="col-12 col-sm-8 col-xl-6 py-2 align-content-center">
                            {{ interaccionSelected.fecha }}
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <div class="col-12 col-sm-4 col-xl-4 py-2 align-content-center fw-bold">
                            Hora de contacto
                        </div>
                        <div class="col-12 col-sm-8 col-xl-4 py-2 align-content-center">
                            {{ interaccionSelected.hora }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <div class="col-12 col-sm-4 col-xl-4 py-2 align-content-center fw-bold">
                            Nueva fecha de contacto
                        </div>
                        <div class="col-12 col-sm-8 col-xl-6 py-2 align-content-center">
                            {{ interaccionSelected.fecha_proximo_contacto }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <div class="col-12 mb-sm-3">
                            <div class="row">
                                <div class="col-12 col-sm-4 col-xl-4 py-2 align-content-center fw-bold">
                                    Modalidad
                                </div>
                                <div class="col-12 col-sm-8 col-xl-6 py-2 align-content-center">
                                    {{ interaccionSelected.modalidad }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mb-sm-3">
                            <div class="row">
                                <div class="col-12 col-sm-4 col-xl-4 py-2 align-content-center fw-bold">
                                    Motivo
                                </div>
                                <div class="col-12 col-sm-8 col-xl-6 py-2 align-content-center">
                                    {{ interaccionSelected.motivo }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-8 mb-sm-3">
                    <div class="row">
                        <div class="col-12 col-sm-4 col-md-2 py-2 align-content-center fw-bold">
                            Observaciones
                        </div>
                        <div class="col-12 col-sm-8 col-md-10 col-xl-6 py-2 align-content-center">
                            {{ interaccionSelected.observaciones }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="row" v-if="interaccionSelected.accion === 'Alerta clínica'">
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <div class="col-12 col-sm-4 col-xl-4 py-2 align-content-center fw-bold">
                            Acción
                        </div>
                        <div class="col-12 col-sm-8 col-md-10 col-xl-6 py-2 align-content-center">
                            {{ interaccionSelected.accion }}
                        </div>
                    </div>
                </div>
            </div>
            <template v-if="interaccionSelected.motivo === 'Reprogramación'">
                <div class="row">
                    <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                        <div class="row">
                            <div class="col-12 col-sm-4 col-xl-4 py-2 align-content-center fw-bold">
                                Motivo reprogramación
                            </div>
                            <div class="col-12 col-sm-8 col-xl-6 py-2 align-content-center">
                                {{ interaccionSelected.motivo_reprogramacion }}
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <Modal id="modalEliminarInteraccion" ref="modalEliminarInteraccion" title="Eliminar interacción" :loading="eliminando" @modalClosed="handleCloseModalEliminarInteraccion">
        <p>Se eliminará interacción del día {{ formEliminar.interaccion?.fecha }} a las {{ formEliminar.interaccion?.hora }}.</p>
        <p>¿Desea continuar?</p>

        <template #footer>
            <template v-if="eliminando === false">
                <button type="button" class="btn btn-grey fw-semibold shadow" :disabled="eliminando" @click="hideModalEliminarInteraccion">
                    No, cancelar
                </button>
                <button type="button" class="btn btn-secondary text-white fw-semibold shadow" :disabled="eliminando" @click="eliminarInteraccion">
                    Si, continuar
                </button>
            </template>
            <template v-else>
                <button type="button" class="btn btn-secondary text-white fw-semibold shadow" disabled>
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Eliminando interacción, espere...
                </button>
            </template>
        </template>
    </Modal>
</template>

<style scoped>

</style>
