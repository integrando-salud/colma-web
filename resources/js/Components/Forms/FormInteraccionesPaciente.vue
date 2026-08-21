<script setup>
import { ref, onMounted } from "vue";
import { useNotificationStore } from "@/Stores/notification";
import DetalleAlertaInteraccion from "@/Components/Pacientes/DetalleAlertaInteraccion.vue";
import FormProximoContacto from "@/Components/Forms/FormProximoContacto.vue";
import FormContactoBase from "@/Components/Forms/FormContactoBase.vue";
import FormAgendaCita from "@/Components/Forms/FormAgendaCita.vue";
import FormRegistroCita from "@/Components/Forms/FormRegistroCita.vue";
import FormSeguimientoContacto from "@/Components/Forms/FormSeguimientoContacto.vue";
import FormContactoParticipante from "@/Components/Forms/FormContactoParticipante.vue";
import FormNoAdherencia from "@/Components/Forms/FormNoAdherencia.vue";
import FormProblemasConexion from "@/Components/Forms/FormProblemasConexion.vue";
import FormAlertaClinica from "@/Components/Forms/FormAlertaClinica.vue";
import FormPedidoAyuda from "@/Components/Forms/FormPedidoAyuda.vue";
import FormObservacionesContacto from "@/Components/Forms/FormObservacionesContacto.vue";
import VentanaContactoTable from "@/Components/Forms/VentanaContactoTable.vue";
import { useFormInteraccionPaciente } from "@/Composables/useFormInteraccionPaciente";
import { useVentanaContacto } from "@/Composables/useVentanaContacto";

const props = defineProps({
    idPaciente: {
        type: Number,
        required: true,
    },
    alerta: {
        type: Object,
        default: null,
    },
    interaccion: {
        type: Object,
        default: null,
    }
});

const notification = useNotificationStore();
const entrenamientoActual = ref(null);

const {
    formContacto,
    motivosContacto,
    reagendaEntrenamiento,
    submit,
    verFechaHoraContacto,
    verFilaFechaProximoContacto,
    applyAlertaDefaults,
    fillDataInteraccion,
    registerFormWatchers,
} = useFormInteraccionPaciente({
    props,
    entrenamientoActual,
});

const {
    ventanaContacto,
    verVentanaContacto,
    colorFechaEntrenamiento,
    fillDataEntrenamientoActual,
    getFechaMasDias,
    isValidDate,
    resetVentanasContacto,
    setVentanasContacto,
} = useVentanaContacto({
    formContacto,
    entrenamientoActual,
    reagendaEntrenamiento,
});

registerFormWatchers({
    getFechaMasDias,
    isValidDate,
    resetVentanasContacto,
    setVentanasContacto,
});

const getEntrenamientoActual = async () => {
    try {
        const { data } = await axios.get(route('ajax.paciente.entrenamientoActual', props.idPaciente));

        entrenamientoActual.value = data || null;
    } catch (error) {
        notification.errorNotification(error.response?.data?.message || 'Error al obtener el entrenamiento actual');
    }
};

defineExpose({ submit });

onMounted(async () => {
    formContacto.paciente_id = props.idPaciente;

    if (props.interaccion) {
        fillDataInteraccion(props.interaccion);
    }

    await getEntrenamientoActual();
    fillDataEntrenamientoActual();
    applyAlertaDefaults(props.alerta);
});
</script>

<template>
    <div class="row">
        <div class="col-12">
            <h4 class="fs-5 text-primary-emphasis">Formulario de contacto</h4>
        </div>

        <DetalleAlertaInteraccion v-if="alerta" :alerta="alerta" />

        <div class="col-12 col-lg-7 mb-2 mb-lg-0">
            <div class="row">
                <FormContactoBase
                    :form="formContacto"
                    :motivos-contacto="motivosContacto"
                    :alerta="alerta"
                    :ver-fecha-hora-contacto="verFechaHoraContacto"
                />

                <template v-if="formContacto.contactado === 1">
                    <FormAgendaCita :form="formContacto" />
                    <FormRegistroCita :form="formContacto" />
                    <FormSeguimientoContacto :form="formContacto" />
                    <FormContactoParticipante :form="formContacto" />
                    <FormNoAdherencia :form="formContacto" />
                    <FormProblemasConexion :form="formContacto" />
                    <FormAlertaClinica :form="formContacto" />
                    <FormPedidoAyuda :form="formContacto" />
                </template>

                <FormProximoContacto
                    v-if="verFilaFechaProximoContacto"
                    :form="formContacto"
                />

                <FormObservacionesContacto :form="formContacto" />
            </div>
        </div>

        <VentanaContactoTable
            v-if="verVentanaContacto"
            :ventana-contacto="ventanaContacto"
            :color-fecha-entrenamiento="colorFechaEntrenamiento"
        />
    </div>
</template>

<style scoped>

</style>
