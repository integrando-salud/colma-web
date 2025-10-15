<script setup>
import { ref, reactive, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import Dashboard from '@/Components/Layouts/Dashboard.vue';
import { FORM_PACIENTE } from "@/Services/pacienteService";
import Password from "@/Components/Form/Password.vue";

const props = defineProps({
    centrosSalud: Array,
    promotores: Array,
    diagnosticos: Array,
    paquetes: Array,
});

const step = ref('datos_paciente');
const formPaciente = reactive(structuredClone(FORM_PACIENTE));
const guardandoDatosPaciente = ref(false);

const promotoresFiltered = computed(() => {
    return props.promotores.filter(promotor => promotor.centro_salud_id === formPaciente.centro_salud_id);
});

const goTo = (stepName) => {
    step.value = stepName;
};

const resetPromotor = () => {
    formPaciente.promotor_id = '';
};

const saveDatosPaciente = () => {
    console.log('Guardar datos del paciente');
};
</script>

<template>
    <Head title="Nuevo paciente" />

    <Dashboard title="Nuevo paciente">
        <div class="row">
            <div class="col-12">
                <div class="border rounded-2">
                    <ul class="nav nav-tabs bg-white rounded-top-2 border-bottom border-dark-subtle">
                        <li class="nav-item">
                            <a href="#" class="nav-link border-0 py-3 border-bottom border-dark-subtle text-body rounded-start-2" :class="{ active: step === 'datos_paciente', 'fw-bold': step === 'datos_paciente' }" @click="goTo('datos_paciente')">
                                Datos del paciente
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link border-0 py-3 border-bottom border-dark-subtle text-body" :class="{ active: step === 'datos_clinicos', 'fw-bold': step === 'datos_clinicos' }" @click="goTo('datos_clinicos')">
                                Datos clínicos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link border-0 py-3 border-bottom border-dark-subtle text-body" :class="{ active: step === 'interacciones', 'fw-bold': step === 'interacciones' }" @click="goTo('interacciones')">
                                Interacciones
                            </a>
                        </li>
                    </ul>
                    <div class="p-3">
                        <template v-if="step === 'datos_paciente'">
                            <div class="row">
                                <div class="col-12">
                                    <h4 class="fs-5 text-primary-emphasis">Datos personales</h4>
                                </div>
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                                            <div class="row">
                                                <label for="paciente_nombre" class="col-12 col-sm-4 col-xl-4 col-form-label">Nombre</label>
                                                <div class="col-12 col-sm-8 col-xl-6">
                                                    <input type="text" name="paciente_nombre" id="paciente_nombre" class="form-control" v-model="formPaciente.nombre" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                                            <div class="row">
                                                <label for="paciente_apellido" class="col-12 col-sm-4 col-xl-4 col-form-label">Apellido</label>
                                                <div class="col-12 col-sm-8 col-xl-6">
                                                    <input type="text" name="paciente_apellido" id="paciente_apellido" class="form-control" v-model="formPaciente.apellido" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                                            <div class="row">
                                                <label for="paciente_fecha_nacimiento" class="col-12 col-sm-4 col-xl-4 col-form-label">Fecha de nacimiento</label>
                                                <div class="col-12 col-sm-8 col-xl-6">
                                                    <input type="text" name="paciente_fecha_nacimiento" id="paciente_fecha_nacimiento" class="form-control" v-model="formPaciente.fecha_nacimiento" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                                            <div class="row">
                                                <label for="paciente_telefono" class="col-12 col-sm-4 col-xl-4 col-form-label">Teléfono</label>
                                                <div class="col-12 col-sm-8 col-xl-6">
                                                    <input type="text" name="paciente_telefono" id="paciente_telefono" class="form-control" v-model="formPaciente.telefono" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                                            <div class="row">
                                                <label for="paciente_email" class="col-12 col-sm-4 col-xl-4 col-form-label">Correo electrónico</label>
                                                <div class="col-12 col-sm-8 col-xl-6">
                                                    <input type="text" name="paciente_email" id="paciente_email" class="form-control" v-model="formPaciente.email" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                                            <div class="row">
                                                <label for="paciente_direccion" class="col-12 col-sm-4 col-xl-4 col-form-label">Dirección</label>
                                                <div class="col-12 col-sm-8 col-xl-6">
                                                    <input type="text" name="paciente_direccion" id="paciente_direccion" class="form-control" v-model="formPaciente.direccion" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-3 mt-sm-0">
                                    <h4 class="fs-5 text-primary-emphasis">Datos de enrolamiento</h4>
                                </div>
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                                            <div class="row">
                                                <label for="paciente_centro_salud" class="col-12 col-sm-4 col-xl-4 col-form-label">Centro de salud</label>
                                                <div class="col-12 col-sm-8 col-xl-6">
                                                    <select name="paciente_centro_salud" id="paciente_centro_salud" class="form-select" v-model="formPaciente.centro_salud_id" @change="resetPromotor">
                                                        <option value="">Seleccionar centro</option>
                                                        <option v-for="centro in centrosSalud" :key="centro.id" :value="centro.id">
                                                            {{ centro.name }}
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                                            <div class="row">
                                                <label for="paciente_promotor" class="col-12 col-sm-4 col-xl-4 col-form-label">Promotor asignado</label>
                                                <div class="col-12 col-sm-8 col-xl-6">
                                                    <select name="paciente_promotor" id="paciente_promotor" class="form-select" v-model="formPaciente.promotor_id">
                                                        <option value="">Seleccionar</option>
                                                        <option v-for="promotor in promotoresFiltered" :key="promotor.id" :value="promotor.id">
                                                            {{ promotor.name }}
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                                            <div class="row">
                                                <label for="paciente_fecha_enrolamiento" class="col-12 col-sm-4 col-xl-4 col-form-label">Fecha enrolamiento</label>
                                                <div class="col-12 col-sm-8 col-xl-6">
                                                    <input type="text" name="paciente_fecha_enrolamiento" id="paciente_fecha_enrolamiento" class="form-control" v-model="formPaciente.fecha_enrolamiento" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                                            <div class="row">
                                                <label class="col-12 col-sm-4 col-xl-4 col-form-label">Diagnósticos</label>
                                                <div class="col-12 col-sm-8 col-xl-6">
                                                    <div class="form-check" v-for="diagnostico in diagnosticos" :key="diagnostico.id">
                                                        <input type="checkbox" class="form-check-input" :value="diagnostico.id" :id="`diagnostico-${diagnostico.id}`" v-model="formPaciente.diagnosticos" />
                                                        <label :for="`diagnostico-${diagnostico.id}`" class="form-check-label">
                                                            {{ diagnostico.name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                                            <div class="row">
                                                <label for="paciente_paquete" class="col-12 col-sm-4 col-xl-4 col-form-label">Paquete asignado</label>
                                                <div class="col-12 col-sm-8 col-xl-6">
                                                    <select name="paciente_paquete" id="paciente_paquete" class="form-select" v-model="formPaciente.paquete_id">
                                                        <option value="">Seleccionar</option>
                                                        <option v-for="promotor in promotoresFiltered" :key="promotor.id" :value="promotor.id">
                                                            {{ promotor.name }}
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-3 mt-sm-0">
                                    <h4 class="fs-5 text-primary-emphasis">Asignar usuario y contraseña</h4>
                                </div>
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                                            <div class="row">
                                                <label for="paciente_usuario" class="col-12 col-sm-4 col-xl-4 col-form-label">Usuario</label>
                                                <div class="col-12 col-sm-8 col-xl-6">
                                                    <input type="text" name="paciente_usuario" id="paciente_usuario" class="form-control" v-model="formPaciente.email" disabled />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="row">
                                                <label for="paciente_password" class="col-12 col-sm-4 col-xl-4 col-form-label">Contraseña</label>
                                                <div class="col-12 col-sm-8 col-xl-6">
                                                    <Password name="paciente_password" id="paciente_password" v-model="formPaciente.password" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template v-else-if="step === 'datos_clinicos'">
                            <h4 class="fs-5 text-primary-emphasis">Datos clínicos</h4>
                        </template>
                        <template v-else-if="step === 'interacciones'">
                            <h4 class="fs-5 text-primary-emphasis">Interacciones</h4>
                        </template>
                    </div>
                </div>
            </div>
            <div class="col-12 text-end mt-3">
                <template v-if="step === 'datos_paciente'">
                    <Link :href="route('dashboard.pacientes.index')" class="btn btn-grey fw-semibold shadow me-2" :disabled="guardandoDatosPaciente">
                        Cancelar
                    </Link>
                    <button type="button" class="btn btn-secondary text-white fw-semibold shadow" :disabled="guardandoDatosPaciente" @click="saveDatosPaciente">
                        Guardar
                    </button>
                </template>
            </div>
        </div>
    </Dashboard>
</template>

<style scoped>
.nav-tabs .nav-item:first-child a {
    border-bottom-left-radius: 0 !important;
}
</style>
