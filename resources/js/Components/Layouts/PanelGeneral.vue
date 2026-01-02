<script setup>
import { ref, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useNotificationStore } from "@/Stores/notification";

const page = usePage();
const pacientesActivos = ref(0);
const notification = useNotificationStore();

const countPacientesActivos = async () => {
    try {
        const { data } = await axios.get(route('ajax.usuario.pacientesActivos', {
            usuario: page.props.usuario.id
        }));

        pacientesActivos.value = data.pacientes_activos;
    } catch (error) {
        notification.errorNotification('Error al contar los pacientes activos');
    }
};

onMounted(async () => {
    await countPacientesActivos();
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
                            {{ $page.props.usuario.numero_alertas }}
                        </div>
                        <div>alertas</div>
                    </div>
                    <div class="col-12 col-sm-4 col-md-4 text-center">
                        <div class="fs-1 fw-semibold">3</div>
                        <div>contactos pendientes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
