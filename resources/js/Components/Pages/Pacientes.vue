<script setup>
import { reactive } from "vue";
import Table from "@/Components/Table.vue";

const props = defineProps({
    pacientes: Object,
    filtros: Object,
});

const formFiltros = reactive({
    estado: props.filtros.estado || '',
    alertas: props.filtros.alertas || '',
    paciente: props.filtros.paciente || '',
    orderBy: props.filtros.orderBy || null,
    orderDir: props.filtros.orderDir || null,
});

const filterPacientes = () => {
    console.log('Filtrar pacientes');
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
            <form class="row justify-content-md-end align-items-center">
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
                    <select name="alertas" id="filtroAlertas" class="form-select rounded-4 bg-body-secondary" v-model="formFiltros.alertas"  @change="filterPacientes">
                        <option value="">Alertas</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2">
                    <input type="text" name="paciente" id="filtroPaciente" placeholder="Buscar paciente" class="form-control rounded-4 bg-body-secondary" v-model="formFiltros.paciente">
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

                <tr v-for="paciente in pacientes.data" :key="paciente.id">
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
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                    </td>
                    <td>
                        <button type="button" class="btn btn-link text-decoration-none">
                            Ver más
                        </button>
                    </td>
                </tr>
            </Table>
            <div class="alert alert-secondary" v-else>
                No se encontraron pacientes.
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
