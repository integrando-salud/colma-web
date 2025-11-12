<script setup>
import { ref } from "vue";
import { useNotificationStore } from "@/Stores/notification";
import Pagination from "@/Components/Pagination.vue";

const props = defineProps({
    idPaciente: {
        type: Number,
        required: true,
    },
    interacciones: {
        type: Object,
        required: true,
    }
});

const notification = useNotificationStore();
const interaccionSelected = ref(null);
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
                    <div class="col-12 col-md-6 col-lg-5 col-xl-4">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="interaccion in interacciones.data" :key="interaccion.id">
                                        <td>{{ interaccion.fecha }}</td>
                                        <td>{{ interaccion.contactado_descripcion }}</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-secondary btn-sm text-white" @click="interaccionSelected = interaccion">Ver más</button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-center small border-top">
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
            <button type="button" class="btn btn-secondary text-white text-decoration-none" @click="interaccionSelected = null">
                <i class="fa fa-arrow-left"></i>
                Volver al historial de interacciones
            </button>
        </div>
        <div class="col-12">
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
                    <div class="row h-100">
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
                                Nueva fecha de contacto
                            </div>
                            <div class="col-12 col-sm-8 col-xl-6 py-2 align-content-center">
                                {{ interaccionSelected.fecha_reprogramacion }}
                            </div>
                        </div>
                    </div>
                </div>
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
</template>

<style scoped>

</style>
