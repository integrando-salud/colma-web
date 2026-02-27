<script setup>
const props = defineProps({
    datosClinicos: {
        type: Object,
        default: () => [],
    }
});
</script>

<template>
    <div class="row">
        <div class="col-12">
            <h4 class="fs-5 text-primary-emphasis">Datos clínicos</h4>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div v-if="datosClinicos.length" class="table-responsive">
                <table class="table table-bordered bg-white text-center">
                    <thead>
                        <tr>
                            <th class="bg-white text-body">Registros</th>
                            <th v-for="(dato, key) in datosClinicos" :key="key" class="bg-white text-body">
                                {{ dato.fecha }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Glucosa en sangre</td>
                            <td v-for="(dato, key) in datosClinicos" :key="key">
                                {{ dato.glucosa_valor ?? '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td>Estado de ánimo</td>
                            <td v-for="(dato, key) in datosClinicos" :key="key">
                                {{ dato.estado_animo_valor ?? '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td>Presión sistólica</td>
                            <td v-for="(dato, key) in datosClinicos" :key="key">
                                {{ dato.presion_sistolica ?? '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td>Presión diastólica</td>
                            <td v-for="(dato, key) in datosClinicos" :key="key">
                                {{ dato.presion_diastolica ?? '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="alert alert-secondary">
                No se encontraron registros
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
