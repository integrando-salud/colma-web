<script setup>
import { computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import Dashboard from '@/Components/Layouts/Dashboard.vue';

const page = usePage();
const mostrarSeguimientoCuartoMes = computed(() => page.props.app.seguimientosMensualesNuevos > 3);

defineProps({
    indicadoresGenerales: {
        type: Object,
        required: true,
    },
    indicadoresEntrenamiento: {
        type: Object,
        required: true,
    },
    indicadoresInteraccionesProgramadas: {
        type: Object,
        required: true,
    },
    indicadoresAlertasClinicas: {
        type: Object,
        required: true,
    },
    indicadoresAlertasOperativas: {
        type: Object,
        required: true,
    },
    indicadoresApp: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Indicadores" />

    <Dashboard title="Indicadores">
        <section aria-labelledby="indicadores-generales">
            <h4 id="indicadores-generales" class="mb-3">Indicadores generales</h4>

            <div class="table-responsive">
                <table class="table table-bordered bg-white align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Indicador</th>
                            <th scope="col">Descripción operacional</th>
                            <th scope="col">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Total de participantes registrados en el dashboard</td>
                            <td>Número total de personas que cuentan con registro creado en el sistema al momento del corte, independientemente de si iniciaron o continúan la intervención.</td>
                            <td>{{ indicadoresGenerales.total_participantes }}</td>
                        </tr>
                        <tr>
                            <td>Total de participantes activos</td>
                            <td>Proporción de participantes registrados cuyo estado actual en el sistema es "activo" al momento del corte, sobre el total de participantes registrados.</td>
                            <td>{{ indicadoresGenerales.total_participantes_activos }} ({{ indicadoresGenerales.porcentaje_participantes_activos }}%)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mt-5" aria-labelledby="indicadores-entrenamiento">
            <h4 id="indicadores-entrenamiento" class="mb-3">Entrenamiento</h4>

            <div class="table-responsive">
                <table class="table table-bordered bg-white align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Indicador</th>
                            <th scope="col">Descripción operacional</th>
                            <th scope="col">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Proporción de participantes entrenados</td>
                            <td>Proporción de participantes activos cuya sesión de entrenamiento fue completada por la promotora de salud.</td>
                            <td>{{ indicadoresEntrenamiento.participantes_entrenados }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mt-5" aria-labelledby="indicadores-interacciones-programadas">
            <h4 id="indicadores-interacciones-programadas" class="mb-3">Interacciones programadas con el participante</h4>

            <div class="table-responsive">
                <table class="table table-bordered bg-white align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Indicador</th>
                            <th scope="col">Descripción operacional</th>
                            <th scope="col">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>% de participantes con seguimiento a los 15 días realizado</td>
                            <td>Proporción de participantes que fueron contactados exitosamente para el seguimiento a los 15 días.</td>
                            <td>{{ indicadoresInteraccionesProgramadas.porcentaje_seguimiento_15_dias_realizado }}%</td>
                        </tr>
                        <tr>
                            <td>% de participantes con seguimiento mensual realizado</td>
                            <td>Proporción de participantes que fueron contactados exitosamente para el seguimiento a los 30 días.</td>
                            <td>{{ indicadoresInteraccionesProgramadas.porcentaje_seguimiento_mensual_realizado }}%</td>
                        </tr>
                        <tr>
                            <td>% de participantes con seguimiento a los 2 meses realizado</td>
                            <td>Proporción de participantes que fueron contactados exitosamente para el seguimiento a los 60 días.</td>
                            <td>{{ indicadoresInteraccionesProgramadas.porcentaje_seguimiento_2_meses_realizado }}%</td>
                        </tr>
                        <tr>
                            <td>% de participantes con seguimiento a los 3 meses realizado</td>
                            <td>Proporción de participantes que fueron contactados exitosamente para el seguimiento a los 90 días.</td>
                            <td>{{ indicadoresInteraccionesProgramadas.porcentaje_seguimiento_3_meses_realizado }}%</td>
                        </tr>
                        <tr v-if="mostrarSeguimientoCuartoMes">
                            <td>% de participantes con seguimiento a los 4 meses realizado</td>
                            <td>Proporción de participantes que fueron contactados exitosamente para el seguimiento a los 120 días.</td>
                            <td>{{ indicadoresInteraccionesProgramadas.porcentaje_seguimiento_4_meses_realizado }}%</td>
                        </tr>
                        <tr>
                            <td>% de participantes con contacto exitoso vía llamado telefónico</td>
                            <td>% de participantes contactados satisfactoriamente por medio de contacto "llamado telefónico / WhatsApp".</td>
                            <td>{{ indicadoresInteraccionesProgramadas.porcentaje_contacto_exitoso_via_llamado_telefonico }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mt-5" aria-labelledby="indicadores-alertas-clinicas">
            <h4 id="indicadores-alertas-clinicas" class="mb-3">Interacciones con el participante - Alerta clínica</h4>

            <div class="table-responsive">
                <table class="table table-bordered bg-white align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Indicador</th>
                            <th scope="col">Descripción operacional</th>
                            <th scope="col">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Proporción de alertas clínicas con resultado de contacto = "Contactado"</td>
                            <td>Proporción de alertas clínicas con resultado de contacto = "Contactado"</td>
                            <td>{{ indicadoresAlertasClinicas.interacciones_alertas_clinicas_contactadas }} ({{ indicadoresAlertasClinicas.porcentaje_alertas_clinicas_contactadas }}%)</td>
                        </tr>
                        <tr>
                            <td>Proporción de alertas clínicas con resultado de contacto = "Contactado" dentro de las 48 horas</td>
                            <td>Proporción de alertas clínicas con resultado de contacto = "Contactado" dentro de las 48 horas</td>
                            <td>{{ indicadoresAlertasClinicas.interacciones_alertas_clinicas_contactadas_48_horas }} ({{ indicadoresAlertasClinicas.porcentaje_alertas_clinicas_contactadas_48_horas }}%)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mt-5" aria-labelledby="indicadores-alertas-operativas">
            <h4 id="indicadores-alertas-operativas" class="mb-3">Interacciones con el participante - Alerta operativa</h4>

            <div class="table-responsive">
                <table class="table table-bordered bg-white align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Indicador</th>
                            <th scope="col">Descripción operacional</th>
                            <th scope="col">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Proporción de alertas operativas con resultado de contacto = "Contactado"</td>
                            <td>Proporción de alertas operativas con resultado de contacto = "Contactado"</td>
                            <td>{{ indicadoresAlertasOperativas.interacciones_alertas_operativas_contactadas }} ({{ indicadoresAlertasOperativas.porcentaje_alertas_operativas_contactadas }}%)</td>
                        </tr>
                        <tr>
                            <td>Proporción de alertas operativas con resultado de contacto = "Contactado" dentro de las 72 horas</td>
                            <td>Proporción de alertas operativas con resultado de contacto = "Contactado" dentro de las 72 horas</td>
                            <td>{{ indicadoresAlertasOperativas.interacciones_alertas_operativas_contactadas_72_horas }} ({{ indicadoresAlertasOperativas.porcentaje_alertas_operativas_contactadas_72_horas }}%)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mt-5" aria-labelledby="indicadores-app">
            <h4 id="indicadores-app" class="mb-3">App</h4>

            <div class="table-responsive">
                <table class="table table-bordered bg-white align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Indicador</th>
                            <th scope="col">Descripción operacional</th>
                            <th scope="col">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Adherencia a las sesiones (proporción de participantes con total de sesiones disponibles completas)</td>
                            <td>Proporción de participantes activos que completaron el 100% de las sesiones que el sistema ya había desbloqueado para ellos hasta el momento del corte, sobre el total de participantes activos.</td>
                            <td>{{ indicadoresApp.porcentaje_adherencia_sesiones }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </Dashboard>
</template>

<style scoped>
.table {
    width: 100%;
    table-layout: fixed;
}

.table th:nth-child(1),
.table td:nth-child(1) {
    width: 30%;
}

.table th:nth-child(2),
.table td:nth-child(2) {
    width: 55%;
}

.table th:nth-child(3),
.table td:nth-child(3) {
    width: 15%;
    text-align: center;
}

</style>
