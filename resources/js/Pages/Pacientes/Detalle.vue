<script setup>
import { ref, useTemplateRef, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import Dashboard from '@/Components/Layouts/Dashboard.vue';
import FormDatosPaciente from "@/Components/Forms/FormDatosPaciente.vue";
import FormInteraccionesPaciente from "@/Components/Forms/FormInteraccionesPaciente.vue";
import HistorialInteraccionesPaciente from "@/Components/HistorialInteraccionesPaciente.vue";
import AlertasPendientes from "@/Components/Pacientes/Alertas/AlertasPendientes.vue";
import AlertasHistorial from "@/Components/Pacientes/Alertas/AlertasHistorial.vue";

const props = defineProps({
    paciente: Object,
    step: {
        type: String,
        default: 'datos_paciente',
    },
    interacciones: {
        type: Object,
        default: () => [],
    },
    alertasPendientes: {
        type: Object,
        default: () => [],
    },
    alertasHistoricas: {
        type: Object,
        default: () => [],
    },
    alerta: {
        type: Object,
        default: () => [],
    }
});

const title = computed(() => {
    if (!props.paciente) {
        return 'Nuevo paciente';
    } else {
        return `Paciente: ${props.paciente.nombre} ${props.paciente.apellido} (${props.paciente.edad} años, ${props.paciente.sexo})`;
    }
});

const formPaciente = useTemplateRef('formPaciente');
const formContacto = useTemplateRef('formContacto');
const guardando = ref(false);

const guardar = () => {
    switch (props.step) {
        case 'datos_paciente':
            saveDatosPaciente();
            break;
        case 'nueva-interaccion':
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
    guardando.value = true;
    await formContacto.value.submit();
    guardando.value = false;
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
                            <a
                                :href="paciente ? route('dashboard.pacientes.edit', paciente.id) : '#'"
                                class="nav-link border-0 py-3 border-bottom border-dark-subtle text-body rounded-start-2"
                                :class="{
                                    active: step === 'datos_paciente',
                                    'fw-bold': step === 'datos_paciente',
                                    disabled: !paciente
                                }"
                            >
                                Datos del paciente
                            </a>
                        </li>
                        <li class="nav-item">
                            <a
                                href="#"
                                class="nav-link border-0 py-3 border-bottom border-dark-subtle text-body"
                                :class="{
                                    active: step === 'datos_clinicos',
                                    'fw-bold': step === 'datos_clinicos',
                                    disabled: !paciente,
                                    'opacity-25': !paciente,
                                }"
                            >
                                Datos clínicos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a
                                href="#"
                                class="nav-link dropdown-toggle border-0 py-3 border-bottom border-dark-subtle text-body"
                                :class="{
                                    active: step === 'alertas',
                                    'fw-bold': step === 'alertas',
                                    disabled: !paciente,
                                    'opacity-25': !paciente,
                                }"
                                data-bs-toggle="dropdown"
                            >
                                Alertas
                                <span class="badge bg-secondary ms-1" v-if="paciente && paciente.alertas_pendientes">{{ paciente.alertas_pendientes }}</span>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a :href="paciente ? route('dashboard.pacientes.alertas', paciente.id) : '#'" class="dropdown-item">Pendientes de revisión</a>
                                    <a :href="paciente ? route('dashboard.pacientes.alertas.historial', paciente.id) : '#'" class="dropdown-item">Historial de alertas</a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a
                                href="#"
                                class="nav-link dropdown-toggle border-0 py-3 border-bottom border-dark-subtle text-body"
                                :class="{
                                    active: step === 'nueva-interaccion' || step === 'historial-interacciones',
                                    'fw-bold': step === 'nueva-interaccion' || step === 'historial-interacciones',
                                    disabled: !paciente,
                                    'opacity-25': !paciente,
                                }"
                                data-bs-toggle="dropdown"
                            >
                                Interacciones
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a :href="paciente ? route('dashboard.pacientes.createInteraccion', paciente.id) : '#'" class="dropdown-item">Nueva interacción</a>
                                    <a :href="paciente ? route('dashboard.pacientes.interacciones', paciente.id) : '#'" class="dropdown-item">Historial de interacciones</a>
                                </li>
                            </ul>
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
                            <AlertasPendientes :alertas="alertasPendientes" />
                        </template>
                        <template v-else-if="step === 'alertas-historial'">
                            <AlertasHistorial :alertas="alertasHistoricas" />
                        </template>
                        <template v-else-if="step === 'nueva-interaccion'">
                            <FormInteraccionesPaciente ref="formContacto" :id-paciente="paciente.id" :alerta />
                        </template>
                        <template v-else-if="step === 'historial-interacciones'">
                            <HistorialInteraccionesPaciente :id-paciente="paciente.id" :interacciones />
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
                    <template v-if="step === 'datos_paciente' || step === 'nueva-interaccion'">
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
