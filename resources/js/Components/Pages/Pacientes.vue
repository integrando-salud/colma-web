<script setup>
import { ref, reactive, computed, useTemplateRef, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import Table from "@/Components/Table.vue";
import Pagination from "@/Components/Pagination.vue";
import Modal from "@/Components/Modal.vue";
import { useNotificationStore } from "@/Stores/notification";

const props = defineProps({
    pacientes: Object,
    promotores: {
        type: Object,
        default: null
    },
    filtros: Object,
    urlBase: String,
});

const page = usePage();
const pacienteSelected = ref(null);
const pacienteSelectedCambiarEstado = ref(null);
const pacienteSelectedResetearDatos = ref(null);
const notification = useNotificationStore();
const modalCambiarEstado = useTemplateRef('modalCambiarEstado');
const modalResetearDatosPaciente = useTemplateRef('modalResetearDatosPaciente');
const modalDetallePaciente = useTemplateRef('modalDetallePaciente');
const guardandoEstado = ref(false);
const reseteandoDatosPaciente = ref(false);
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
    if (page.props.usuario.rol.nombre === 'promotor' || page.props.usuario.rol.nombre === 'supervisor_pais') {
        return;
    }

    pacienteSelectedCambiarEstado.value = paciente;
    modalCambiarEstado.value.showModal();
};

const hideModalCambiarEstado = () => {
    modalCambiarEstado.value.hideModal();
};

const showModalResetearDatosPaciente = (idPaciente) => {
    pacienteSelectedResetearDatos.value = idPaciente;
    hideModalDetallePaciente();
    modalResetearDatosPaciente.value.showModal();
};

const hideModalResetearDatosPaciente = () => {
    modalResetearDatosPaciente.value.hideModal();
};

const handleCloseModalResetearDatosPaciente = () => {
    const paciente = props.pacientes.data.find(paciente => paciente.id === pacienteSelectedResetearDatos.value);
    pacienteSelectedResetearDatos.value = null;
    hideModalResetearDatosPaciente();
    showModalDetallePaciente(paciente);
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
    // diagnostico: props.filtros.diagnostico || '',
    alertas: props.filtros.alertas || '',
    nombre: props.filtros.nombre || '',
    promotor: props.filtros.promotor || '',
    orderBy: props.filtros.orderBy || null,
    orderDir: props.filtros.orderDir || null,
});

const filterPacientes = () => {
    router.get(props.urlBase, formFiltros, { preserveState: true, replace: true });
};

const resetFiltrosPacientes = () => {
    formFiltros.estado = '';
    formFiltros.alertas = '';
    formFiltros.nombre = '';
    formFiltros.promotor = '';
    formFiltros.orderBy = null;
    formFiltros.orderDir = null;
    router.get(props.urlBase, {}, { preserveState: true, replace: true });
};

const orderBy = (column) => {
    formFiltros.orderDir = (column === formFiltros.orderBy && formFiltros.orderDir === 'asc') ? 'desc' : 'asc';
    formFiltros.orderBy = column;
    filterPacientes();
}

const cambiarEstado = async () => {
    if (page.props.usuario.rol.nombre === 'promotor' || page.props.usuario.rol.nombre === 'supervisor_pais') {
        notification.errorNotification('Acción no permitida.');
        return;
    }

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

const resetearDatosPaciente = async () => {
    try {
        const { data } = await axios.post(route('ajax.paciente.resetearDatos', {
            paciente: pacienteSelectedResetearDatos.value
        }));

        const index = props.pacientes.data.findIndex(paciente => paciente.id === data.id);
        props.pacientes.data[index] = data;

        notification.successNotification('Se resetearon los datos con éxito.');

        handleCloseModalResetearDatosPaciente();
    } catch (error) {
        notification.errorNotification(error.response?.data?.message || 'Error al resetear los datos del paciente.');
    }
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
<!--                <div class="col-12 col-sm-6 col-md-3 col-lg-3 col-xl-2 mb-2 mb-md-0">-->
<!--                    <select name="diagnostico" id="filtroDiagnostico" class="form-select rounded-4 bg-body-secondary" v-model="formFiltros.diagnostico" @change="filterPacientes">-->
<!--                        <option value="">Diagnóstico</option>-->
<!--                        <option v-for="diagnostico in diagnosticos" :key="diagnostico.id" :value="diagnostico.id">{{ diagnostico.nombre }}</option>-->
<!--                    </select>-->
<!--                </div>-->
                <div
                    v-if="$page.props.usuario.rol.nombre === 'supervisor'"
                    class="col-12 col-sm-6 col-md-3 col-lg-3 col-xl-2 mb-2 mb-md-0"
                >
                    <select name="promotor" id="filtroPromotor" class="form-select rounded-4 bg-body-secondary" v-model="formFiltros.promotor" @change="filterPacientes">
                        <option value="">Promotor de salud</option>
                        <option v-for="promotor in promotores" :key="promotor.id" :value="promotor.id">{{ promotor.nombre }}</option>
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
                <div class="col-12 col-sm-6 col-md-auto mt-2 mt-md-0">
                    <button
                        type="button"
                        class="btn btn-outline-dark"
                        title="Borrar filtros"
                        aria-label="Borrar filtros"
                        @click="resetFiltrosPacientes"
                    >
                        <i class="fa fa-filter-circle-xmark"></i>
                    </button>
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
                                <span class="me-3">Siguiente contacto</span>
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
                            <td v-if="$page.props.usuario.rol?.nombre !== 'promotor'">{{ paciente.promotor?.nombre }}</td>
                            <td>{{ paciente.sesiones.completadas }}/{{ paciente.sesiones.total }}</td>
                            <td>
                                {{ paciente.alertas_pendientes ? paciente.alertas_pendientes : '-' }}
                                <i class="fa fa-warning text-danger" v-if="paciente.alertas_pendientes"></i>
                            </td>
                            <td
                                :class="{
                                    'text-warning': paciente.fecha_proximo_contacto_dias > 0 && paciente.fecha_proximo_contacto_dias <= 7,
                                    'text-danger': paciente.fecha_proximo_contacto_dias > 7,
                                }"
                            >
                                {{ paciente.fecha_proximo_contacto ?? '-' }}
                            </td>
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
        id="modalResetearDatosPaciente"
        ref="modalResetearDatosPaciente"
        title="Resetear datos"
        :loading="reseteandoDatosPaciente"
    >
        <p>Se resetearán los datos del paciente, incluidos:</p>
        <ul>
            <li>Registros de glucosa</li>
            <li>Registros de presión arterial</li>
            <li>Registros de estado de ánimo</li>
            <li>Interacciones</li>
            <li>Alertas</li>
            <li>Entrenamientos</li>
        </ul>
        <p>¿Desea continuar?</p>

        <template #footer>
            <template v-if="reseteandoDatosPaciente === false">
                <button
                    type="button"
                    class="btn btn-grey fw-semibold shadow"
                    :disabled="reseteandoDatosPaciente"
                    @click="handleCloseModalResetearDatosPaciente"
                >
                    No, cancelar
                </button>
                <button
                    type="button"
                    class="btn btn-secondary text-white fw-semibold shadow"
                    :disabled="reseteandoDatosPaciente"
                    @click="resetearDatosPaciente"
                >
                    Si, continuar
                </button>
            </template>
            <template v-else>
                <button
                    type="button"
                    class="btn btn-secondary text-white fw-semibold shadow"
                    disabled
                >
                    <span
                        class="spinner-border spinner-border-sm me-2"
                        role="status"
                        aria-hidden="true"
                    ></span>
                    Reseteando datos, espere...
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
            <div class="row mb-4">
                <div class="col-12 col-xl-4 align-content-center">
                    <div class="row">
                        <div class="col-12 text-primary-emphasis">
                            <span class="fs-3 fw-semibold pb-0 me-3">{{ pacienteSelected.nombre }} {{ pacienteSelected.apellido }}</span>
                            <span class="fs-4 pb-0">({{ pacienteSelected.edad }} años, {{ pacienteSelected.sexo_descripcion }})</span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <span class="fw-semibold">Centro de salud:</span> {{ pacienteSelected.centro_salud?.nombre }}
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-8 align-content-center">
                    <div class="row">
                        <div class="col-12 col-md-4 col-lg-4 text-xl-end">
                            <span class="fw-semibold">Usuario:</span> {{ pacienteSelected.usuario.documento }}
                        </div>
                        <div
                            v-if="$page.props.usuario.pais?.codigo === 'PE'"
                            class="col-12 col-md-8 col-lg-8 text-xl-end d-flex justify-content-xl-end align-items-center"
                        >
                            <span class="fw-semibold">Contraseña:</span>
                            <div class="ms-2 bg-white border px-2 py-1 min-w-password">
                                {{ pacienteSelected.clave }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="text-primary-emphasis">Datos de contacto</h5>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="row">
                        <div class="col-12">
                            <span class="fw-semibold">Teléfono 1:</span> {{ pacienteSelected.telefono }}
                        </div>
                        <div class="col-12">
                            <span class="fw-semibold">Teléfono 2:</span> {{ pacienteSelected.telefono2 }}
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="row">
                        <div class="col-12">
                            <span class="fw-semibold">Dirección:</span> {{ pacienteSelected.direccion }}
                        </div>
                        <div class="col-12">
                            <span class="fw-semibold">Referencia:</span> {{ pacienteSelected.referencia }}
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="row">
                        <div class="col-12">
                            <span class="fw-semibold">Email:</span> {{ pacienteSelected.email }}
                        </div>
                        <div class="col-12">
                            <span class="fw-semibold">Fecha de inicio:</span> {{ pacienteSelected.fecha_enrolamiento_format }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="text-primary-emphasis">Siguiente contacto</h5>
                </div>
                <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4">
                    <div class="row">
                        <div class="col-12 offset-sm-1 offset-md-3 offset-lg-5 offset-xl-11">
                            <div class="table-responsive">
                                <table class="table table-bordered bg-white text-center border">
                                    <thead>
                                        <tr>
                                            <th class="p-1">Fecha</th>
                                            <th class="p-1">Motivo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template v-if="pacienteSelected.proximos_contactos.length">
                                            <tr v-for="(item, index) in pacienteSelected.proximos_contactos" :key="index">
                                                <td class="p-1">
                                                    {{ item.fecha }}
                                                </td>
                                                <td class="p-1">
                                                    {{ item.motivo }}
                                                </td>
                                            </tr>
                                        </template>
                                        <tr v-else>
                                            <td class="p-1">Sin contactos programados.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="text-primary-emphasis">Datos de uso de la aplicación</h5>
                </div>
                <div class="col-12">
                    <span class="fw-semibold">Fecha de última actualización:</span> {{ pacienteSelected.fecha_ultima_sincronizacion }}
                </div>
                <div class="col-12 col-md-6">
                    <span class="fw-semibold">Sesiones completas:</span> 4/5
                </div>
                <div class="col-12 col-md-6">
                    <span class="fw-semibold">Metas cumplidas:</span> 4
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12 col-xl-2 text-primary-emphasis fw-semibold">
                    Observaciones
                </div>
                <div class="col-12 col-xl-10">
                    <div class="bg-white border p-2 min-h-observaciones">
                        {{ pacienteSelected.observaciones }}
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <template v-if="pacienteSelected">
                <button
                    v-if="pacienteSelected.test"
                    type="button"
                    class="btn btn-danger shadow"
                    @click="showModalResetearDatosPaciente(pacienteSelected.id)"
                >
                    <i class="fa-solid fa-rotate-left"></i>
                    Resetear datos
                </button>
                <a class="btn btn-outline-primary shadow" :href="route('dashboard.pacientes.alertas', pacienteSelected.id)">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    Ir a alertas
                </a>
                <a class="btn bg-primary text-white shadow" :href="route('dashboard.pacientes.edit', pacienteSelected.id)">
                    <i class="fa-solid fa-arrow-right"></i>
                    Ir a detalle
                </a>
            </template>
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
    color: #333;
}

.order {
    cursor: pointer;
}

.min-h-observaciones {
    min-height: 100px;
    white-space: pre-wrap;
}

.min-w-password {
    min-width: 120px;
    min-height: 34px;
}
</style>
