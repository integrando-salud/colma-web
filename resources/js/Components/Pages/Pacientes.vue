<script setup>
import { ref, reactive, computed } from "vue";
import { router } from "@inertiajs/vue3";
import Table from "@/Components/Table.vue";
import Pagination from "@/Components/Pagination.vue";

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
            <template v-if="pacientes.data.length > 0">
                <Table>
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
                                    'fa-chevron-down': formFiltros.orderBy !== 'activo' || formFiltros.orderBy === 'activo' && formFiltros.orderDir === 'asc',
                                    'fa-chevron-up': formFiltros.orderBy === 'activo' && formFiltros.orderDir === 'desc'
                                }" @click="orderBy('activo')"></i>
                            </th>
                            <th>Interacciones</th>
                            <th></th>
                        </tr>
                    </template>

                    <template v-for="paciente in pacientes.data" :key="paciente.id">
                        <tr :class="{ 'row-alerta': paciente.alertas_pendientes > 0 }">
                            <td>{{ paciente.id }}</td>
                            <td>{{ paciente.nombre }} {{ paciente.apellido }}</td>
                            <td>{{ paciente.fecha_enrolamiento_format }}</td>
                            <td>{{ paciente.sesiones.completadas }}/{{ paciente.sesiones.total }}</td>
                            <td>
                                {{ paciente.alertas_pendientes ? paciente.alertas_pendientes : '-' }}
                                <i class="fa fa-warning text-danger" v-if="paciente.alertas_pendientes"></i>
                            </td>
                            <td>{{ paciente.usuario.numero_accesos }}</td>
                            <td>{{ paciente.usuario.ultimo_acceso }}</td>
                            <td>
                                <span class="badge py-2 px-3" :class="{ 'bg-success': paciente.activo, 'bg-dark-grey': !paciente.activo }">
                                    {{ paciente.estado }}
                                </span>
                            </td>
                            <td>
                                <a :href="route('dashboard.pacientes.createInteraccion', paciente.id)" class="btn btn-link text-body fs-5">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </a>
                            </td>
                            <td>
                                <button type="button" class="btn btn-link btn-ver-mas text-decoration-none" @click="pacienteSelected === paciente ? hideDetails() : showDetails(paciente)">
                                    Ver {{ (pacienteSelected === paciente ? 'menos' : 'más') }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="pacienteSelected === paciente">
                            <td colspan="10" class="px-4 table-patient-details-container">
                                <table class="table table-patient-details text-start">
                                    <tr>
                                        <td class="text-primary-emphasis p-3" colspan="2">
                                            <i class="fa-regular fa-user fa-2x me-3 pb-0"></i>
                                            <span class="fs-3 fw-semibold pb-0 me-3">{{ pacienteSelected.nombre }} {{ pacienteSelected.apellido }}</span>
                                            <span class="fs-4 pb-0">({{ pacienteSelected.edad }} años, {{ pacienteSelected.sexo }})</span>
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
                                        <td class="text-end p-3"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-primary-emphasis fw-semibold align-top p-3">Datos de contacto</td>
                                        <td class="p-3">
                                            <p>
                                                <span class="fw-semibold">Teléfono 1:</span> {{ pacienteSelected.telefono }}
                                            </p>
                                            <p class="mb-0">
                                                <span class="fw-semibold">Teléfono 2:</span> {{ pacienteSelected.telefono2 }}
                                            </p>
                                        </td>
                                        <td class="p-3 align-top">
                                            <span class="fw-semibold">Email:</span> {{ pacienteSelected.email }}
                                        </td>
                                        <td class="p-3 align-top">
                                            <span class="fw-semibold">Dirección:</span> {{ pacienteSelected.direccion }}
                                        </td>
                                        <td class="p-3 align-top">
                                            <span class="fw-semibold">Referencia:</span> {{ pacienteSelected.referencia }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-primary-emphasis fw-semibold align-top p-3">Datos clínicos</td>
                                        <td class="p-3">
                                            <table v-if="pacienteSelected.datos_clinicos.length" class="table table-bordered bg-white text-center">
                                                <tr>
                                                    <th class="p-1">Registros</th>
                                                    <th v-for="(registro, key) in pacienteSelected.datos_clinicos" :key="key" class="p-1">
                                                        {{ registro.fecha }}
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <td class="p-1">Glucosa</td>
                                                    <td v-for="(registro, key) in pacienteSelected.datos_clinicos" :key="key" class="p-1">
                                                        {{ registro.glucosa_valor ?? '-' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="p-1">Presión sistólica</td>
                                                    <td v-for="(registro, key) in pacienteSelected.datos_clinicos" :key="key" class="p-1">
                                                        {{ registro.presion_sistolica ?? '-' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="p-1">Presión diastólica</td>
                                                    <td v-for="(registro, key) in pacienteSelected.datos_clinicos" :key="key" class="p-1">
                                                        {{ registro.presion_diastolica ?? '-' }}
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td class="text-primary-emphasis fw-semibold align-top p-3">
                                            <button v-if="pacienteSelected.datos_clinicos.length" class="btn bg-primary text-white p-1">
                                                <i class="fa-solid fa-chart-line"></i>
                                                Ver gráficos
                                            </button>
                                        </td>
                                        <td class="text-primary-emphasis fw-semibold align-top p-3">Puntos de contacto</td>
                                        <td class="p-3 align-top">
                                            <table v-if="pacienteSelected.interacciones.length" class="table table-bordered bg-white text-center">
                                                <tr v-for="interaccion in pacienteSelected.interacciones" :key="interaccion.id">
                                                    <td class="p-1">
                                                        <a
                                                            :href="route('dashboard.pacientes.interacciones', { paciente: pacienteSelected.id, contacto: interaccion.id })"
                                                            class="text-decoration-none d-block text-body"
                                                        >
                                                            {{ interaccion.fecha }}
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-primary-emphasis fw-semibold align-top p-3">Datos de uso de la app</td>
                                        <td class="p-3 align-top">
                                            <span class="fw-semibold">Sesiones completas:</span> 4/5
                                        </td>
                                        <td class="p-3 align-top">
                                            <span class="fw-semibold">Metas cumplidas:</span> 4
                                        </td>
                                        <td colspan="2" class="p-3 align-top">
                                            <span class="fw-semibold">Fecha de enrolamiento:</span> {{ pacienteSelected.fecha_enrolamiento_format }}
                                        </td>
                                        <td class="p-3 text-end">
                                            <div class="d-flex flex-column">
                                                <a class="btn btn-outline-primary px-3 py-2 shadow mb-3" :href="route('dashboard.pacientes.alertas', paciente.id)">
                                                    Ir a alertas <i class="fa-solid fa-triangle-exclamation"></i>
                                                </a>
                                                <a class="btn bg-primary text-white px-3 py-2 shadow" :href="route('dashboard.pacientes.edit', paciente.id)">
                                                    Ir a paciente <i class="fa-solid fa-arrow-right"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </template>

                    <template #footer>
                        <tr>
                            <td colspan="10" class="small border-top">
                                Viendo {{ pacientes.meta.from }} a {{ pacientes.meta.to }} de {{ pacientes.meta.total }} pacientes
                            </td>
                        </tr>
                    </template>
                </Table>

                <Pagination v-if="pacientes.meta.last_page > 1" :prev-url="pacientes.links.prev" :next-url="pacientes.links.next" />
            </template>
            <div class="alert alert-secondary" v-else>
                No se encontraron pacientes.
            </div>
        </div>
    </div>
</template>

<style scoped>
.row-alerta {
    background-color: #F98080;
}

.row-alerta .fa-warning {
    font-size: 1.5rem;
}

.row-alerta .btn-ver-mas {
    color: #205BF1;
}
</style>
