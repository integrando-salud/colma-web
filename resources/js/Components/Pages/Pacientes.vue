<script setup>
import { ref, reactive, computed } from "vue";
import Table from "@/Components/Table.vue";
import {router} from "@inertiajs/vue3";

const props = defineProps({
    pacientes: Object,
    filtros: Object,
    urlBase: String,
});

const pacienteSelected = ref(null);

const formFiltros = reactive({
    estado: props.filtros.estado || '',
    alertas: props.filtros.alertas || '',
    nombre: props.filtros.nombre || '',
    orderBy: props.filtros.orderBy || null,
    orderDir: props.filtros.orderDir || null,
});

const showDetails = (paciente) => {
    pacienteSelected.value = paciente;
};

const hideDetails = () => {
    pacienteSelected.value = null;
}

const pacienteSelectedDiagnosticos = computed(() => {
    if (!pacienteSelected.value || pacienteSelected.value.diagnosticos.length === 0) {
        return '';
    }

    return pacienteSelected.value.diagnosticos.map(diagnostico => diagnostico.nombre).join(', ');
});

const filterPacientes = () => {
    router.get(props.urlBase, formFiltros, { preserveState: true, replace: true });
};

const orderBy = (column) => {
    formFiltros.orderDir = (column === formFiltros.orderBy && formFiltros.orderDir === 'asc') ? 'desc' : 'asc';
    formFiltros.orderBy = column;
    filterPacientes();
}
</script>

<template>
    <div class="row">
        <div class="col-12 text-end mb-4">
            <a :href="route('dashboard.pacientes.create')" class="btn btn-secondary text-white fw-semibold">
                Agregar paciente
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12 mb-4">
            <form class="row justify-content-md-end align-items-center" @submit.prevent="filterPacientes">
                <div class="col-12 col-md-2 mb-2 mb-md-0 text-md-end">
                    Filtros:
                </div>
                <div class="col-12 col-sm-6 col-md-3 col-lg-3 col-xl-2 mb-2 mb-md-0">
                    <select name="estado" id="filtroEstado" class="form-select rounded-4 bg-body-secondary" v-model="formFiltros.estado" @change="filterPacientes">
                        <option value="">Estado</option>
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3 col-lg-3 col-xl-2 mb-2 mb-md-0">
                    <select name="alertas" id="filtroAlertas" class="form-select rounded-4 bg-body-secondary" v-model="formFiltros.alertas" @change="filterPacientes">
                        <option value="">Alertas</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2">
                    <input type="text" name="paciente" id="filtroPaciente" placeholder="Buscar paciente" class="form-control rounded-4 bg-body-secondary" v-model="formFiltros.nombre" @change="filterPacientes">
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <Table v-if="pacientes.data.length > 0">
                <template #header>
                    <tr>
                        <th>
                            <span class="me-3">ID</span>
                            <i class="fa order" :class="{
                                'fa-chevron-down': formFiltros.orderBy !== 'id' || formFiltros.orderBy === 'id' && formFiltros.orderDir === 'asc',
                                'fa-chevron-up': formFiltros.orderBy === 'id' && formFiltros.orderDir === 'desc'
                            }" @click="orderBy('id')"></i>
                        </th>
                        <th>
                            <span class="me-3">Nombre paciente</span>
                            <i class="fa order" :class="{
                                'fa-chevron-down': formFiltros.orderBy !== 'nombre' || formFiltros.orderBy === 'nombre' && formFiltros.orderDir === 'asc',
                                'fa-chevron-up': formFiltros.orderBy === 'nombre' && formFiltros.orderDir === 'desc'
                            }" @click="orderBy('nombre')"></i>
                        </th>
                        <th>
                            <span class="me-3">Fecha enrolamiento</span>
                            <i class="fa order" :class="{
                                'fa-chevron-down': formFiltros.orderBy !== 'fecha_enrolamiento' || formFiltros.orderBy === 'fecha_enrolamiento' && formFiltros.orderDir === 'asc',
                                'fa-chevron-up': formFiltros.orderBy === 'fecha_enrolamiento' && formFiltros.orderDir === 'desc'
                            }" @click="orderBy('fecha_enrolamiento')"></i>
                        </th>
                        <th>
                            <span class="me-3">Sesiones</span>
                            <i class="fa order" :class="{
                                'fa-chevron-down': formFiltros.orderBy !== 'sesiones' || formFiltros.orderBy === 'sesiones' && formFiltros.orderDir === 'asc',
                                'fa-chevron-up': formFiltros.orderBy === 'sesiones' && formFiltros.orderDir === 'desc'
                            }" @click="orderBy('sesiones')"></i>
                        </th>
                        <th>
                            <span class="me-3">Alertas</span>
                            <i class="fa order" :class="{
                                'fa-chevron-down': formFiltros.orderBy !== 'alertas' || formFiltros.orderBy === 'alertas' && formFiltros.orderDir === 'asc',
                                'fa-chevron-up': formFiltros.orderBy === 'alertas' && formFiltros.orderDir === 'desc'
                            }" @click="orderBy('alertas')"></i>
                        </th>
                        <th>
                            <span class="me-3">Ingresos</span>
                            <i class="fa order" :class="{
                                'fa-chevron-down': formFiltros.orderBy !== 'ingresos' || formFiltros.orderBy === 'ingresos' && formFiltros.orderDir === 'asc',
                                'fa-chevron-up': formFiltros.orderBy === 'ingresos' && formFiltros.orderDir === 'desc'
                            }" @click="orderBy('ingresos')"></i>
                        </th>
                        <th>
                            <span class="me-3">Último ingreso</span>
                            <i class="fa order" :class="{
                                'fa-chevron-down': formFiltros.orderBy !== 'ultimo_ingreso' || formFiltros.orderBy === 'ultimo_ingreso' && formFiltros.orderDir === 'asc',
                                'fa-chevron-up': formFiltros.orderBy === 'ultimo_ingreso' && formFiltros.orderDir === 'desc'
                            }" @click="orderBy('ultimo_ingreso')"></i>
                        </th>
                        <th>
                            <span class="me-3">Estado</span>
                            <i class="fa order" :class="{
                                'fa-chevron-down': formFiltros.orderBy !== 'estado' || formFiltros.orderBy === 'estado' && formFiltros.orderDir === 'asc',
                                'fa-chevron-up': formFiltros.orderBy === 'estado' && formFiltros.orderDir === 'desc'
                            }" @click="orderBy('estado')"></i>
                        </th>
                        <th>Interacciones</th>
                        <th></th>
                    </tr>
                </template>

                <template v-for="paciente in pacientes.data" :key="paciente.id">
                    <tr>
                        <td>{{ paciente.id }}</td>
                        <td>{{ paciente.nombre }} {{ paciente.apellido }}</td>
                        <td>{{ paciente.fecha_enrolamiento_format }}</td>
                        <td>{{ paciente.sesiones.completadas }}/{{ paciente.sesiones.total }}</td>
                        <td>-</td>
                        <td>{{ paciente.usuario.numero_accesos }}</td>
                        <td>{{ paciente.usuario.ultimo_acceso }}</td>
                        <td>
                            <span class="badge py-2 px-3" :class="{ 'bg-success': paciente.activo, 'bg-dark-grey': !paciente.activo }">
                                {{ paciente.estado }}
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-link text-body fs-5">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                        </td>
                        <td>
                            <button type="button" class="btn btn-link text-decoration-none" @click="pacienteSelected === paciente ? hideDetails() : showDetails(paciente)">
                                Ver {{ (pacienteSelected === paciente ? 'menos' : 'más') }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="pacienteSelected === paciente">
                        <td colspan="10" class="bg-info px-4">
                            <table class="table table-patient-details text-start">
                                <tr>
                                    <td class="text-primary-emphasis p-3" colspan="2">
                                        <i class="fa-regular fa-user fa-2x me-3 pb-0"></i>
                                        <span class="fs-3 fw-semibold pb-0 me-3">{{ pacienteSelected.nombre }} {{ pacienteSelected.apellido }}</span>
                                        <span class="fs-4 pb-0">({{ pacienteSelected.edad }} años)</span>
                                    </td>
                                    <td class="p-3">
                                        <span class="fw-semibold">Promotor:</span> {{ pacienteSelected.promotor.nombre }}
                                    </td>
                                    <td class="p-3">
                                        <span class="fw-semibold">Centro de salud:</span> {{ pacienteSelected.centro_salud.nombre }}
                                    </td>
                                    <td class="p-3">
                                        <span class="fw-semibold">Diagnósticos:</span> {{ pacienteSelectedDiagnosticos }}
                                    </td>
                                    <td class="text-end p-3">
                                        <i class="fa-solid fa-triangle-exclamation fa-2x text-danger p-0"></i>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-primary-emphasis fw-semibold p-3">Datos de contacto</td>
                                    <td class="p-3">
                                        <span class="fw-semibold">Email:</span> {{ pacienteSelected.email }}
                                    </td>
                                    <td class="p-3">
                                        <span class="fw-semibold">Teléfono:</span> {{ pacienteSelected.telefono }}
                                    </td>
                                    <td colspan="3" class="p-3">
                                        <span class="fw-semibold">Dirección:</span> {{ pacienteSelected.direccion }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-primary-emphasis fw-semibold align-top p-3">Datos clínicos</td>
                                    <td class="p-3">
                                        <table class="table table-bordered bg-white text-center">
                                            <tr>
                                                <th class="p-1">Registros</th>
                                                <th class="p-1">23/07/2025</th>
                                                <th class="p-1">20/08/2025</th>
                                            </tr>
                                            <tr>
                                                <td class="p-1">Glucosa</td>
                                                <td class="p-1">120 <i class="fa-solid fa-arrow-up text-danger p-0"></i></td>
                                                <td class="p-1">90</td>
                                            </tr>
                                            <tr>
                                                <td class="p-1">Peso</td>
                                                <td class="p-1">90</td>
                                                <td class="p-1">90</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td class="text-primary-emphasis fw-semibold align-top p-3">
                                        <button class="btn btn-link py-0 text-decoration-none text-primary">
                                            Ver gráficos <i class="fa-solid fa-chart-line"></i>
                                        </button>
                                    </td>
                                    <td class="text-primary-emphasis fw-semibold align-top p-3">Puntos de contacto</td>
                                    <td class="p-3">
                                        <table class="table table-bordered bg-white text-center">
                                            <tr>
                                                <td class="p-1">20/08/2025</td>
                                            </tr>
                                            <tr>
                                                <td class="p-1">20/08/2025</td>
                                            </tr>
                                            <tr>
                                                <td class="p-1">20/08/2025</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-primary-emphasis fw-semibold align-top p-3">Datos de uso de la app</td>
                                    <td class="p-3">
                                        <span class="fw-semibold">Sesiones completas:</span> 4/5
                                    </td>
                                    <td class="p-3">
                                        <span class="fw-semibold">Metas cumplidas:</span> 4
                                    </td>
                                    <td colspan="2" class="p-3">
                                        <span class="fw-semibold">Fecha de enrolamiento:</span> {{ pacienteSelected.fecha_enrolamiento_format }}
                                    </td>
                                    <td class="p-3 text-end">
                                        <a class="btn btn-link text-decoration-none text-primary" :href="route('dashboard.pacientes.edit', paciente.id)">
                                            Ver detalle <i class="fa-solid fa-arrow-right"></i>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </template>
            </Table>
            <div class="alert alert-secondary" v-else>
                No se encontraron pacientes.
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
