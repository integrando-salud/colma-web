<script setup>
import { ref, useTemplateRef, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import Dashboard from '@/Components/Layouts/Dashboard.vue';
import FormDatosPaciente from "@/Components/Forms/FormDatosPaciente.vue";
import FormInteraccionesPaciente from "@/Components/Forms/FormInteraccionesPaciente.vue";

const props = defineProps({
    paciente: Object,
});

const title = computed(() => {
    if (!props.paciente) {
        return 'Nuevo paciente';
    } else {
        return `Paciente: ${props.paciente.nombre} ${props.paciente.apellido} (${props.paciente.edad} años, ${props.paciente.sexo})`;
    }
});
const step = ref('datos_paciente');
const formPaciente = useTemplateRef('formPaciente');
const guardando = ref(false);

const goTo = (stepName) => {
    step.value = stepName;
};

const guardar = () => {
    switch (step.value) {
        case 'datos_paciente':
            saveDatosPaciente();
            break;
        case 'interacciones':
            saveInteraccion();
            break;
    }
};

const saveDatosPaciente = async () => {
    guardando.value = true;
    await formPaciente.value.submit();
    guardando.value = false;
};

const saveInteraccion = async () => {
    console.log('Guardar interacción');
}
</script>

<template>
    <Head :title />

    <Dashboard :title>
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
                            <a href="#" class="nav-link border-0 py-3 border-bottom border-dark-subtle text-body" :class="{ active: step === 'alertas', 'fw-bold': step === 'alertas' }" @click="goTo('alertas')">
                                Alertas
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
                            <FormDatosPaciente ref="formPaciente" :paciente />
                        </template>
                        <template v-else-if="step === 'datos_clinicos'">
                            <h4 class="fs-5 text-primary-emphasis">Datos clínicos</h4>
                        </template>
                        <template v-else-if="step === 'alertas'">
                            <h4 class="fs-5 text-primary-emphasis">Alertas</h4>
                        </template>
                        <template v-else-if="step === 'interacciones'">
                            <FormInteraccionesPaciente />
                        </template>
                    </div>
                </div>
            </div>
            <div class="col-12 text-end mt-3">
                <template v-if="guardando">
                    <span class="text-primary-emphasis">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        Guardando
                    </span>
                </template>
                <template v-else>
                    <template v-if="step === 'datos_paciente' || step === 'interacciones'">
                        <Link :href="route('dashboard.pacientes.index')" class="btn btn-grey fw-semibold shadow me-2">
                            Cancelar
                        </Link>
                        <button type="button" class="btn btn-secondary text-white fw-semibold shadow" @click="guardar">
                            Guardar
                        </button>
                    </template>
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
