<script setup>
import { ref, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useNotificationStore } from "@/Stores/notification";

const page = usePage();
const pacientesActivos = ref(0);
const pacientesConAlertas = ref(0);
const contactosPendientes = ref(0);
const notification = useNotificationStore();

const countPacientesActivos = async () => {
    try {
        const { data } = await axios.get(route('ajax.usuario.contarPacientesActivos', {
            usuario: page.props.usuario.id
        }));

        pacientesActivos.value = data.pacientes_activos;
    } catch (error) {
        notification.errorNotification('Error al contar los pacientes activos');
    }
};

const countPacientesConAlertas = async () => {
    try {
        const { data } = await axios.get(route('ajax.usuario.contarPacientesConAlertasPendientes', {
            usuario: page.props.usuario.id
        }));

        pacientesConAlertas.value = data.pacientes_con_alertas_pendientes;
    } catch (error) {
        notification.errorNotification('Error al contar los pacientes con alertas pendientes');
    }
};

const countContactosPendientes = async () => {
    try {
        const { data } = await axios.get(route('ajax.usuario.contarContactosPendientes', {
            usuario: page.props.usuario.id
        }));

        contactosPendientes.value = data.contactos_pendientes;
    } catch (error) {
        notification.errorNotification('Error al contar los contactos pendientes');
    }
};

onMounted(async () => {
    await countPacientesActivos();
    await countPacientesConAlertas();
    await countContactosPendientes();
});
</script>

<template>
    <div class="row">
        <div class="col-12 mb-4">
            <div class="panel-general text-primary-emphasis p-4 pb-md-5 rounded-3">
                <h4 class="mb-4">Panel general</h4>
                <div class="row">
                    <div class="col-12 col-sm-4 col-md-4 mb-3 mb-md-0 text-center">
                        <div class="fs-1 fw-semibold">
                            {{ pacientesActivos }}/{{ $page.props.usuario.numero_pacientes }}
                        </div>
                        <div>pacientes activos</div>
                    </div>
                    <div class="col-12 col-sm-4 col-md-4 mb-3 mb-md-0 text-center">
                        <div class="fs-1 fw-semibold">
                            {{ pacientesConAlertas }}
                        </div>
                        <div>alertas</div>
                    </div>
                    <div class="col-12 col-sm-4 col-md-4 text-center">
                        <div class="fs-1 fw-semibold">{{ contactosPendientes }}</div>
                        <div>contactos</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
