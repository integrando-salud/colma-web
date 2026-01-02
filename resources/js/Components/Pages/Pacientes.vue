<script setup>
import { ref, reactive, computed, useTemplateRef, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import Table from "@/Components/Table.vue";
import Pagination from "@/Components/Pagination.vue";
import Modal from "@/Components/Modal.vue";
import { useNotificationStore } from "@/Stores/notification";

const props = defineProps({
    pacientes: Object,
    filtros: Object,
    urlBase: String,
});

const page = usePage();
const pacienteSelected = ref(null);
const pacienteSelectedCambiarEstado = ref(null);
const notification = useNotificationStore();
const modalCambiarEstado = useTemplateRef('modalCambiarEstado');
const modalDetallePaciente = useTemplateRef('modalDetallePaciente');
const guardandoEstado = ref(false);
const diagnosticos = ref([]);

const mensajeCambiarEstado = computed(() => {
    if (!pacienteSelectedCambiarEstado.value) {
        return '';
    }

    return 'Se ' + (pacienteSelectedCambiarEstado.value.activo ? 'desactivará' : 'activará') + ` el paciente <strong>${pacienteSelectedCambiarEstado.value.nombre} ${pacienteSelectedCambiarEstado.value.apellido}</strong>.`;
});

const ocultarCrear = computed(() => {
    return page.props.usuario.pais?.id === 2 && page.props.usuario.rol.nombre === 'promotor';
});

const showModalCambiarEstado = (paciente) => {
    pacienteSelectedCambiarEstado.value = paciente;
    modalCambiarEstado.value.showModal();
};

const hideModalCambiarEstado = () => {
    modalCambiarEstado.value.hideModal();
};

const handleCloseModalCambiarEstado = () => {
    pacienteSelectedCambiarEstado.value = null;
};

const showModalDetallePaciente = (paciente) => {
    pacienteSelected.value = paciente;
    modalDetallePaciente.value.showModal();
};

const hideModalDetallePaciente = () => {
    modalDetallePaciente.value.hideModal();
};

const handleCloseModalDetallePaciente = () => {
    pacienteSelected.value = null;
};

const formFiltros = reactive({
    estado: props.filtros.estado || '',
    diagnostico: props.filtros.diagnostico || '',
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

const cambiarEstado = async () => {
    try {
        const { data } = await axios.patch(route('ajax.paciente.cambiarEstado', pacienteSelectedCambiarEstado.value.id));
        notification.successNotification(data.message || 'Se guardó con éxito.');
        pacienteSelectedCambiarEstado.value.activo = data.activo;
        pacienteSelectedCambiarEstado.value.estado = data.activo ? 'Activo' : 'Inactivo';
        hideModalCambiarEstado();
    } catch (error) {
        notification.errorNotification(error.response?.data?.message || 'Error al cambiar el estado del paciente.');
    }
};

const listarDiagnosticos = (diagnosticos) => {
    return diagnosticos.map(diagnostico => diagnostico.nombre).join(', ');
};

const getDiagnosticos = async () => {
    try {
        const response = await axios.get(route('ajax.diagnosticos.get'), {
            params: { activo: 1 }
        });

        diagnosticos.value = response.data;
    } catch (error) {
        notification.errorNotification('Error al obtener los diagnósticos.');
    }
};

onMounted(() => {
    getDiagnosticos();
});
</script>

<template>
    <div class="row" v-if="ocultarCrear === false">
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
                    <select name="diagnostico" id="filtroDiagnostico" class="form-select rounded-4 bg-body-secondary" v-model="formFiltros.diagnostico" @change="filterPacientes">
                        <option value="">Diagnóstico</option>
                        <option v-for="diagnostico in diagnosticos" :key="diagnostico.id" :value="diagnostico.id">{{ diagnostico.nombre }}</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3 col-lg-3 col-xl-2 mb-2 mb-md-0">
                    <select name="alertas" id="filtroAlertas" class="form-select rounded-4 bg-body-secondary" v-model="formFiltros.alertas" @change="filterPacientes">
                        <option value="">Alertas</option>
                        <option value="clinica">Clínicas</option>
                        <option value="operativa">Operativas</option>
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
                                <span class="me-3">Código</span>
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
                                <span class="me-3">Fecha de inicio</span>
                                <i class="fa order" :class="{
                                    'fa-chevron-down': formFiltros.orderBy !== 'fecha_enrolamiento' || formFiltros.orderBy === 'fecha_enrolamiento' && formFiltros.orderDir === 'asc',
                                    'fa-chevron-up': formFiltros.orderBy === 'fecha_enrolamiento' && formFiltros.orderDir === 'desc'
                                }" @click="orderBy('fecha_enrolamiento')"></i>
                            </th>
                            <th>
                                <span class="me-3">Diagnóstico</span>
                                <i class="fa order" :class="{
                                    'fa-chevron-down': formFiltros.orderBy !== 'diagnostico' || formFiltros.orderBy === 'diagnostico' && formFiltros.orderDir === 'asc',
                                    'fa-chevron-up': formFiltros.orderBy === 'diagnostico' && formFiltros.orderDir === 'desc'
                                }" @click="orderBy('diagnostico')"></i>
                            </th>
                            <th v-if="$page.props.usuario.rol?.nombre !== 'promotor'">
                                <span class="me-3">Promotor</span>
                                <i class="fa order" :class="{
                                    'fa-chevron-down': formFiltros.orderBy !== 'promotor' || formFiltros.orderBy === 'promotor' && formFiltros.orderDir === 'asc',
                                    'fa-chevron-up': formFiltros.orderBy === 'promotor' && formFiltros.orderDir === 'desc'
                                }" @click="orderBy('promotor')"></i>
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
                                    'fa-chevron-down': formFiltros.orderBy !== 'numero_alertas' || formFiltros.orderBy === 'numero_alertas' && formFiltros.orderDir === 'asc',
                                    'fa-chevron-up': formFiltros.orderBy === 'numero_alertas' && formFiltros.orderDir === 'desc'
                                }" @click="orderBy('numero_alertas')"></i>
                            </th>
                            <th>
                                <span class="me-3">Fecha siguiente contacto</span>
                                <i class="fa order" :class="{
                                    'fa-chevron-down': formFiltros.orderBy !== 'fecha_proximo_contacto' || formFiltros.orderBy === 'fecha_proximo_contacto' && formFiltros.orderDir === 'asc',
                                    'fa-chevron-up': formFiltros.orderBy === 'fecha_proximo_contacto' && formFiltros.orderDir === 'desc'
                                }" @click="orderBy('fecha_proximo_contacto')"></i>
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
                            <th>Formulario de contacto</th>
                            <th></th>
                        </tr>
                    </template>

                    <template v-for="paciente in pacientes.data" :key="paciente.id">
                        <tr :class="{ 'row-alerta': paciente.alertas_pendientes > 0 }">
                            <td>{{ paciente.id }}</td>
                            <td>{{ paciente.nombre }} {{ paciente.apellido }}</td>
                            <td>{{ paciente.fecha_enrolamiento_format }}</td>
                            <td>{{ listarDiagnosticos(paciente.diagnosticos) }}</td>
                            <td v-if="$page.props.usuario.rol?.nombre !== 'promotor'">{{ paciente.promotor?.nombre }}</td>
                            <td>{{ paciente.sesiones.completadas }}/{{ paciente.sesiones.total }}</td>
                            <td>
                                {{ paciente.alertas_pendientes ? paciente.alertas_pendientes : '-' }}
                                <i class="fa fa-warning text-danger" v-if="paciente.alertas_pendientes"></i>
                            </td>
                            <td>{{ paciente.fecha_proximo_contacto ?? '-' }}</td>
                            <td>{{ paciente.usuario.ultimo_acceso }}</td>
                            <td>
                                <span
                                    class="badge py-2 px-3"
                                    :class="{
                                        'bg-success': paciente.activo,
                                        'bg-dark-grey': !paciente.activo
                                    }"
                                    :style="{ cursor: 'pointer' }"
                                    @click="showModalCambiarEstado(paciente)"
                                >
                                    {{ paciente.estado }}
                                </span>
                            </td>
                            <td>
                                <a :href="route('dashboard.pacientes.createInteraccion', paciente.id)" class="btn btn-link text-body fs-5">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </a>
                            </td>
                            <td>
                                <button type="button" class="btn btn-link btn-ver-mas text-decoration-none" @click="showModalDetallePaciente(paciente)">
                                    Ver más
                                </button>
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

    <Modal id="modalCambiarEstado" ref="modalCambiarEstado" title="Cambiar estado" :loading="guardandoEstado" @modalClosed="handleCloseModalCambiarEstado">
        <p>
            <span v-html="mensajeCambiarEstado"></span>
        </p>
        <p>¿Desea continuar?</p>

        <template #footer>
            <template v-if="guardandoEstado === false">
                <button type="button" class="btn btn-grey fw-semibold shadow" :disabled="guardandoEstado" @click="hideModalCambiarEstado">
                    No, cancelar
                </button>
                <button type="button" class="btn btn-secondary text-white fw-semibold shadow" :disabled="guardandoEstado" @click="cambiarEstado">
                    Si, continuar
                </button>
            </template>
            <template v-else>
                <button type="button" class="btn btn-secondary text-white fw-semibold shadow" disabled>
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Cambiando estado, espere...
                </button>
            </template>
        </template>
    </Modal>

    <Modal
        id="modalDetallePaciente"
        ref="modalDetallePaciente"
        class="p-2"
        :full-width="true"
        @modalClosed="handleCloseModalDetallePaciente"
    >
        <template v-if="pacienteSelected">
            <div class="row">
                <div class="col-12 col-xl-4 text-primary-emphasis align-content-center mb-1 mb-xl-4">
                    <div class="row">
                        <div class="col-12">
                            <i class="fa-regular fa-user fa-2x me-3 pb-0"></i>
                            <span class="fs-3 fw-semibold pb-0 me-3">{{ pacienteSelected.nombre }} {{ pacienteSelected.apellido }}</span>
                            <span class="fs-4 pb-0">({{ pacienteSelected.edad }} años, {{ pacienteSelected.sexo }})</span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-8 align-content-center mb-2 mb-xl-4">
                    <div class="row">
                        <div class="col-12 col-lg-4">
                            <span class="fw-semibold">Promotor:</span> {{ pacienteSelected.promotor.nombre }}
                        </div>
                        <div class="col-12 col-lg-4">
                            <span class="fw-semibold">Centro de salud:</span> {{ pacienteSelected.centro_salud.nombre }}
                        </div>
                        <div class="col-12 col-lg-4">
                            <span class="fw-semibold">Diagnósticos:</span> {{ pacienteSelectedDiagnosticos }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-xl-2 text-primary-emphasis fw-semibold">
                    Datos de contacto
                </div>
                <div class="col-12 col-xl-10 mb-2 mb-xl-4">
                    <div class="row">
                        <div class="col-12 col-xl-3">
                            <div>
                                <span class="fw-semibold">Teléfono 1:</span> {{ pacienteSelected.telefono }}
                            </div>
                            <div>
                                <span class="fw-semibold">Teléfono 2:</span> {{ pacienteSelected.telefono2 }}
                            </div>
                        </div>
                        <div class="col-12 col-xl-3">
                            <span class="fw-semibold">Email:</span> {{ pacienteSelected.email }}
                        </div>
                        <div class="col-12 col-xl-3">
                            <span class="fw-semibold">Dirección:</span> {{ pacienteSelected.direccion }}
                        </div>
                        <div class="col-12 col-xl-3">
                            <span class="fw-semibold">Referencia:</span> {{ pacienteSelected.referencia }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-xl-2 text-primary-emphasis fw-semibold">
                    Datos clínicos
                </div>
                <div class="col-12 col-xl-10 mb-xl-4">
                    <div class="row">
                        <div class="col-12 col-xl-4">
                            <div class="table-responsive">
                                <table v-if="pacienteSelected.datos_clinicos.length" class="table table-bordered bg-white text-center">
                                    <tbody>
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
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-12 col-xl-3 offset-xl-2 text-primary-emphasis fw-semibold">
                            Fecha de contacto
                        </div>
                        <div class="col-12 col-xl-3">
                            <div class="table-responsive">
                                <table class="table table-bordered bg-white text-center">
                                    <template v-if="pacienteSelected.interacciones.length">
                                        <tr v-for="interaccion in pacienteSelected.interacciones" :key="interaccion.id">
                                            <td class="p-1 border-1">
                                                <a
                                                    :href="route('dashboard.pacientes.interacciones', { paciente: pacienteSelected.id, contacto: interaccion.id })"
                                                    class="text-decoration-none d-block text-body"
                                                >
                                                    {{ interaccion.fecha }}
                                                </a>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td class="p-1 border-1">Sin contacto.</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-xl-2 text-primary-emphasis fw-semibold">
                    Datos de uso de la app
                </div>
                <div class="col-12 col-xl-10">
                    <div class="row">
                        <div class="col-12 col-xl-3">
                            <span class="fw-semibold">Fecha de inicio:</span> {{ pacienteSelected.fecha_enrolamiento_format }}
                        </div>
                        <div class="col-12 col-xl-3">
                            <span class="fw-semibold">Sesiones completas:</span> 4/5
                        </div>
                        <div class="col-12 col-xl-3 mb-3 mb-xl-0">
                            <span class="fw-semibold">Metas cumplidas:</span> 4
                        </div>
                        <div class="col-12 col-xl-3">
                            <div class="mb-3 text-xl-end">
                                <a class="btn btn-outline-primary px-3 py-2 shadow" :href="route('dashboard.pacientes.alertas', pacienteSelected.id)">
                                    Ir a alertas
                                    <br>
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                </a>
                            </div>
                            <div class="text-xl-end">
                                <a class="btn bg-primary text-white px-3 shadow" :href="route('dashboard.pacientes.edit', pacienteSelected.id)">
                                    Ir a detalle
                                    <br>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <button type="button" class="btn btn-grey fw-semibold shadow" @click="hideModalDetallePaciente">
                Cerrar
            </button>
        </template>
    </Modal>
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
