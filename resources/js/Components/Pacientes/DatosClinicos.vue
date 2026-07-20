<script setup>
import { reactive } from "vue";
import { router } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import { vMaska } from "maska/vue";

const props = defineProps({
    datosClinicos: {
        type: Object,
        default: () => [],
    },
    idPaciente: {
        type: Number,
        required: true,
    },
    filtros: {
        type: Object,
        default: () => ({}),
    }
});

const filtros = reactive({
    desde: props.filtros.desde ?? '',
    hasta: props.filtros.hasta ?? '',
});

const aplicarFiltros = () => {
    router.get(route('dashboard.pacientes.datosClinicos', props.idPaciente), filtros, {
        preserveScroll: true,
    });
};

const limpiarFiltros = () => {
    filtros.desde = '';
    filtros.hasta = '';
    aplicarFiltros();
};
</script>

<template>
    <div class="row">
        <div class="col-12">
            <h4 class="fs-5 text-primary-emphasis">Datos clínicos</h4>
        </div>
    </div>
    <form class="row align-items-end g-2 mb-3" @submit.prevent="aplicarFiltros">
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            <label for="fecha-desde" class="form-label">Fecha de inicio</label>
            <input
                id="fecha-desde"
                v-model="filtros.desde"
                v-maska="'##/##/####'"
                data-maska-eager
                type="text"
                inputmode="numeric"
                class="form-control"
                placeholder="DD/MM/AAAA"
            />
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            <label for="fecha-hasta" class="form-label">Fecha de fin</label>
            <input
                id="fecha-hasta"
                v-model="filtros.hasta"
                v-maska="'##/##/####'"
                data-maska-eager
                type="text"
                inputmode="numeric"
                class="form-control"
                placeholder="DD/MM/AAAA"
            />
        </div>
        <div class="col-12 col-sm-auto text-end">
            <button type="submit" class="btn btn-primary text-white me-2">Filtrar</button>
            <button type="button" class="btn btn-grey" @click="limpiarFiltros">Limpiar</button>
        </div>
    </form>
    <div class="row">
        <div class="col-12">
            <div v-if="datosClinicos.data?.length" class="table-responsive">
                <table class="table table-bordered bg-white text-center">
                    <thead>
                        <tr>
                            <th class="bg-white text-body">Fecha y hora</th>
                            <th v-for="(dato, key) in datosClinicos.data" :key="key" class="bg-white text-body">
                                {{ dato.fecha }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Glucosa en sangre</td>
                            <td v-for="(dato, key) in datosClinicos.data" :key="key">
                                {{ dato.glucosa_valor ?? '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td>Presión sistólica</td>
                            <td v-for="(dato, key) in datosClinicos.data" :key="key">
                                {{ dato.presion_sistolica ?? '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td>Presión diastólica</td>
                            <td v-for="(dato, key) in datosClinicos.data" :key="key">
                                {{ dato.presion_diastolica ?? '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="text-center small">
                    Viendo {{ datosClinicos.meta.from }} a {{ datosClinicos.meta.to }} de {{ datosClinicos.meta.total }} registros
                </div>
                <Pagination
                    v-if="datosClinicos.meta.last_page > 1"
                    :prev-url="datosClinicos.links.prev"
                    :next-url="datosClinicos.links.next"
                />
            </div>
            <div v-else class="alert alert-secondary">
                No se encontraron registros
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
