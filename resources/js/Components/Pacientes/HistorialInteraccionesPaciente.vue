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
                                            <template v-if="puedeEliminar">
                                                <a
                                                    :href="route('dashboard.pacientes.editInteraccion', {
                                                        paciente: interaccion.paciente.id,
                                                        interaccion: interaccion.id
                                                    })"
                                                    class="btn btn-success btn-sm text-white me-1"
                                                >
                                                    Editar
                                                </a>
                                                <button
                                                    type="button"
                                                    class="btn btn-danger btn-sm text-white"
                                                    @click="showModalEliminarInteraccion(interaccion)"
                                                >
                                                    Eliminar
                                                </button>
                                            </template>
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
                            Modalidad
                        </div>
                        <div class="col-12 col-sm-8 col-xl-6 py-2 align-content-center">
                            {{ interaccionSelected.modalidad }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
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
            <div
                v-if="interaccionSelected.motivo === 'Agendar cita de entrenamiento' || interaccionSelected.motivo === 'Agendar cita final'"
                class="row"
            >
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <div class="col-12 col-sm-4 col-xl-4 py-2 align-content-center fw-bold">
                            {{ 'Fecha programada para cita ' + (interaccionSelected.motivo === 'Agendar cita de entrenamiento' ? 'de entrenamiento' : 'final') }}
                        </div>
                        <div class="col-12 col-sm-8 col-xl-6 py-2 align-content-center">
                            {{ interaccionSelected.fecha_cita }}
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <div class="col-12 col-sm-4 col-xl-4 py-2 align-content-center fw-bold">
                            {{ 'Lugar acordado para cita ' + (interaccionSelected.motivo === 'Agendar cita de entrenamiento' ? 'de entrenamiento' : 'final') }}
                        </div>
                        <div class="col-12 col-sm-8 col-xl-6 py-2 align-content-center">
                            {{ interaccionSelected.lugar_cita }}
                            <span v-if="interaccionSelected.lugar_cita_otro">
                                ({{ interaccionSelected.lugar_cita_otro }})
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div
                v-if="interaccionSelected.motivo === 'Cita de entrenamiento' || interaccionSelected.motivo === 'Cita final' || interaccionSelected.motivo === 'Re-entrenamiento'"
                class="row"
            >
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <div class="col-12 col-sm-4 col-xl-4 py-2 align-content-center fw-bold">
                            Fecha
                        </div>
                        <div class="col-12 col-sm-8 col-xl-6 py-2 align-content-center">
                            {{ interaccionSelected.fecha_cita_final }}
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <div class="col-12 col-sm-4 col-xl-4 py-2 align-content-center fw-bold">
                            Hora
                        </div>
                        <div class="col-12 col-sm-8 col-xl-6 py-2 align-content-center">
                            {{ interaccionSelected.hora_cita_final }}
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <div class="col-12 col-sm-4 col-xl-4 py-2 align-content-center fw-bold">
                            Lugar
                        </div>
                        <div class="col-12 col-sm-8 col-xl-6 py-2 align-content-center">
                            {{ interaccionSelected.lugar_cita_final }}
                            <span v-if="interaccionSelected.lugar_cita_final_otro">
                                ({{ interaccionSelected.lugar_cita_final_otro }})
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div
                v-if="interaccionSelected.motivo === 'Seguimiento 2 semanas luego del entrenamiento' || interaccionSelected.motivo === 'Seguimiento mensual'"
                class="row"
            >
                <div class="col-12 py-2 align-content-center fw-bold">
                    ¿El/la participante tuvo dificultades utilizando el aplicativo desde su último contacto?
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.seguimiento_pregunta_a }}
                </div>
                <div class="col-12 py-2 align-content-center fw-bold">
                    En caso si, ¿qué dificultades tuvo?
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.seguimiento_pregunta_b ?? '-' }}
                </div>
                <div class="col-12 py-2 align-content-center fw-bold">
                    ¿El/la participante pudo resolver estas dificultades?
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.seguimiento_pregunta_c ?? '-' }}
                </div>
                <div class="col-12 py-2 align-content-center fw-bold">
                    En caso si, ¿cómo resolvió estas dificultades?
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.seguimiento_pregunta_d ?? '-' }}
                </div>
                <div class="col-12 py-2 align-content-center fw-bold">
                    ¿Pudiste ayudar al participante a resolver estas dificultades durante la llamada?
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.seguimiento_pregunta_e ?? '-' }}
                </div>
                <div class="col-12 py-2 align-content-center fw-bold">
                    Agenda una visita domiciliaria o un re-entrenamiento
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.seguimiento_pregunta_f ?? '-' }}
                </div>
            </div>
            <div
                v-if="interaccionSelected.motivo === 'Contacto iniciado por el participante'"
                class="row"
            >
                <div class="col-12 py-2 align-content-center fw-bold">
                    Razón de contacto del participante
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.razon_contacto_participante }}
                    <span v-if="interaccionSelected.razon_contacto_participante_otro">
                        ({{ interaccionSelected.razon_contacto_participante_otro }})
                    </span>
                </div>
                <div class="col-12 py-2 align-content-center fw-bold">
                    Escribe las acciones realizadas
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.contacto_participante_acciones }}
                </div>
            </div>
            <div
                v-if="interaccionSelected.motivo === 'No completa las sesiones'"
                class="row"
            >
                <div class="col-12 py-2 align-content-center fw-bold">
                    ¿Qué dificultades tuvo el/la participante con las sesiones?
                </div>
                <div class="col-12 py-2 align-content-center">
                    <ul>
                        <li v-for="(item, index) in interaccionSelected.no_adherencia_pregunta_a" :key="index">
                            {{ item }}
                            <span v-if="item === 'Otro' && interaccionSelected.no_adherencia_otro">
                                ({{ interaccionSelected.no_adherencia_otro }})
                            </span>
                        </li>
                    </ul>
                </div>
                <div class="col-12 py-2 align-content-center fw-bold">
                    ¿Pudiste ayudar al participante a resolver estas dificultades durante la llamada?
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.no_adherencia_pregunta_b ?? '-' }}
                </div>
                <div class="col-12 py-2 align-content-center fw-bold">
                    Agenda una visita domiciliaria o un re-entrenamiento
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.no_adherencia_pregunta_c ?? '-' }}
                </div>
            </div>
            <div
                v-if="interaccionSelected.motivo === 'Problemas de conexión'"
                class="row"
            >
                <div class="col-12 py-2 align-content-center fw-bold">
                    ¿Qué dificultades tuvo el/la participante con las sesiones?
                </div>
                <div class="col-12 py-2 align-content-center">
                    <ul>
                        <li v-for="(item, index) in interaccionSelected.problemas_conexion_pregunta_a" :key="index">
                            {{ item }}
                            <span v-if="item === 'Otro' && interaccionSelected.problemas_conexion_otro">
                                ({{ interaccionSelected.problemas_conexion_otro }})
                            </span>
                        </li>
                    </ul>
                </div>
                <div class="col-12 py-2 align-content-center fw-bold">
                    ¿Pudiste ayudar al participante a resolver estas dificultades durante la llamada?
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.problemas_conexion_pregunta_b ?? '-' }}
                </div>
                <div class="col-12 py-2 align-content-center fw-bold">
                    Agenda una visita domiciliaria o un re-entrenamiento
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.problemas_conexion_pregunta_c ?? '-' }}
                </div>
            </div>
            <div
                v-if="interaccionSelected.motivo === 'Alerta clínica'"
                class="row"
            >
                <div class="col-12 py-2 align-content-center fw-bold">
                    ¿El/la participante vio la recomendación de buscar atención en el aplicativo?
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.alerta_clinica_pregunta_a ?? '-' }}
                </div>
                <div class="col-12 py-2 align-content-center fw-bold">
                    ¿Recomendaste al participante que busque atención?
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.alerta_clinica_pregunta_b ?? '-' }}
                </div>
                <div class="col-12 py-2 align-content-center fw-bold">
                    ¿El/la participante siguió la recomendación indicada por el aplicativo?
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.alerta_clinica_pregunta_c ?? '-' }}
                </div>
                <div class="col-12 py-2 align-content-center fw-bold">
                    ¿Por qué motivo(s) el participante no pudo seguir la recomendación del aplicativo?
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.alerta_clinica_pregunta_d ?? '-' }}
                </div>
                <div class="col-12 py-2 align-content-center fw-bold">
                    ¿El/la participante pudo acceder a la atención recomendada?
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.alerta_clinica_pregunta_e ?? '-' }}
                </div>
                <div class="col-12 py-2 align-content-center fw-bold">
                    ¿Qué dificultades encontró para acceder a la atención recomendada?
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.alerta_clinica_pregunta_f ?? '-' }}
                </div>
            </div>
            <div
                v-if="interaccionSelected.motivo === 'Pedido de ayuda a través del aplicativo'"
                class="row"
            >
                <div class="col-12 py-2 align-content-center fw-bold">
                    ¿Qué dificultades tuvo el/la participante?
                </div>
                <div class="col-12 py-2 align-content-center">
                    <ul>
                        <li v-for="(item, index) in interaccionSelected.pedido_ayuda_pregunta_a" :key="index">
                            {{ item }}
                            <span v-if="item === 'Otro' && interaccionSelected.pedido_ayuda_otro">
                                ({{ interaccionSelected.pedido_ayuda_otro }})
                            </span>
                        </li>
                    </ul>
                </div>
                <div class="col-12 py-2 align-content-center fw-bold">
                    Describe las dificultades que tuvo el/la participante
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.pedido_ayuda_pregunta_b ?? '-' }}
                </div>
                <div class="col-12 py-2 align-content-center fw-bold">
                    ¿Pudiste ayudar al participante a resolver estas dificultades durante la llamada?
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.pedido_ayuda_pregunta_c ?? '-' }}
                </div>
                <div class="col-12 py-2 align-content-center fw-bold">
                    Agenda una visita domiciliaria o un re-entrenamiento
                </div>
                <div class="col-12 py-2 align-content-center">
                    {{ interaccionSelected.pedido_ayuda_pregunta_d ?? '-' }}
                </div>
            </div>
            <div
                v-if="interaccionSelected.contactado == 0"
                class="row"
            >
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <div class="col-12 col-sm-4 col-xl-4 py-2 align-content-center fw-bold">
                            Motivo de reprogramación
                        </div>
                        <div class="col-12 col-sm-8 col-xl-6 py-2 align-content-center">
                            {{ interaccionSelected.motivo_reprogramacion }}
                        </div>
                    </div>
                </div>
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
                <div class="col-12 fw-bold">
                    Observaciones
                </div>
                <div class="col-12">
                    {{ interaccionSelected.observaciones ?? '-' }}
                </div>
            </div>
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
