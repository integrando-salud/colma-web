<script setup>
import { ref, useTemplateRef, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import Dashboard from '@/Components/Layouts/Dashboard.vue';
import FormDatosPaciente from "@/Components/Forms/FormDatosPaciente.vue";
import FormInteraccionesPaciente from "@/Components/Forms/FormInteraccionesPaciente.vue";
import HistorialInteraccionesPaciente from "@/Components/Pacientes/HistorialInteraccionesPaciente.vue";
import AlertasPendientes from "@/Components/Pacientes/Alertas/AlertasPendientes.vue";
import AlertasHistorial from "@/Components/Pacientes/Alertas/AlertasHistorial.vue";
import DatosClinicos from "@/Components/Pacientes/DatosClinicos.vue";
import ModalCancelar from "@/Components/ModalCancelar.vue";
import ModalConfirmar from "@/Components/ModalConfirmar.vue";

const props = defineProps({
    paciente: Object,
    tab: {
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
        default: null,
    },
    interaccion: {
        type: Object,
        default: null,
    },
    origenInteraccion: {
        type: String,
        default: 'contactos',
    },
    datosClinicos: {
        type: Object,
        default: () => [],
    },
    filtrosDatosClinicos: {
        type: Object,
        default: () => ({}),
    }
});

const title = computed(() => {
    if (!props.paciente) {
        return 'Nuevo paciente';
    } else {
        return `Paciente: ${props.paciente.nombre} ${props.paciente.apellido} (${props.paciente.edad} años, ${props.paciente.sexo})`;
    }
});

const subtitle = computed(() => {
    if (props.paciente) {
        return `Teléfono: ${props.paciente.telefono} | Dirección: ${props.paciente.direccion}`;
    }

    return null;
});

const formPaciente = useTemplateRef('formPaciente');
const formContacto = useTemplateRef('formContacto');
const guardando = ref(false);
const modalCancelar = useTemplateRef('modalCancelar');
const modalConfirmar = useTemplateRef('modalConfirmar');

const guardar = () => {
    hideModalConfirmar();

    switch (props.tab) {
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

const mostrarGuardar = computed(() => {
    if (props.paciente && !props.paciente.activo) {
        return false;
    }

    return props.tab === 'datos_paciente' || props.tab === 'nueva-interaccion';
});

const showModalCancelar = () => {
    modalCancelar.value.showModal();
};

const hideModalCancelar = () => {
    modalCancelar.value.hideModal();
};

const confirmCancelar = () => {
    hideModalCancelar();

    const redirectTo = props.interaccion
        ? route('dashboard.pacientes.interacciones', props.paciente.id)
        : route('dashboard.pacientes.index');

    router.get(redirectTo);
};

const showModalConfirmar = () => {
    modalConfirmar.value.showModal();
};

const hideModalConfirmar = () => {
    modalConfirmar.value.hideModal();
};
</script>

<template>
    <Head :title />

    <Dashboard :title :subtitle>
        <div class="row">
            <div class="col-12">
                <div class="border rounded-2">
                    <ul class="nav nav-tabs bg-white rounded-top-2 border-bottom border-dark-subtle">
                        <li class="nav-item">
                            <Link
                                v-if="paciente"
                                :href="route('dashboard.pacientes.edit', paciente.id)"
                                class="nav-link border-0 py-3 border-bottom border-dark-subtle text-body rounded-start-2"
                                :class="{
                                    active: tab === 'datos_paciente',
                                    'fw-bold': tab === 'datos_paciente',
                                }"
                            >
                                Datos del paciente
                            </Link>
                            <span v-else class="nav-link border-0 py-3 border-bottom border-dark-subtle text-body rounded-start-2 disabled">
                                Datos del paciente
                            </span>
                        </li>
                        <li class="nav-item">
                            <Link
                                v-if="paciente"
                                :href="route('dashboard.pacientes.datosClinicos', paciente.id)"
                                class="nav-link border-0 py-3 border-bottom border-dark-subtle text-body"
                                :class="{
                                    active: tab === 'datos-clinicos',
                                    'fw-bold': tab === 'datos-clinicos',
                                }"
                            >
                                Datos clínicos
                            </Link>
                            <span v-else class="nav-link border-0 py-3 border-bottom border-dark-subtle text-body disabled opacity-25">
                                Datos clínicos
                            </span>
                        </li>
                        <li class="nav-item">
                            <button
                                v-if="paciente"
                                type="button"
                                class="nav-link dropdown-toggle border-0 py-3 border-bottom border-dark-subtle text-body"
                                :class="{
                                    active: tab === 'alertas' || tab === 'alertas-historial',
                                    'fw-bold': tab === 'alertas' || tab === 'alertas-historial',
                                }"
                                data-bs-toggle="dropdown"
                            >
                                Alertas
                                <span class="badge bg-secondary ms-1" v-if="paciente.alertas_pendientes">{{ paciente.alertas_pendientes }}</span>
                            </button>
                            <span v-else class="nav-link dropdown-toggle border-0 py-3 border-bottom border-dark-subtle text-body disabled opacity-25">
                                Alertas
                            </span>
                            <ul v-if="paciente" class="dropdown-menu">
                                <li>
                                    <Link :href="route('dashboard.pacientes.alertas', paciente.id)" class="dropdown-item">Pendientes de revisión</Link>
                                    <Link :href="route('dashboard.pacientes.alertas.historial', paciente.id)" class="dropdown-item">Historial de alertas</Link>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <button
                                v-if="paciente"
                                type="button"
                                class="nav-link dropdown-toggle border-0 py-3 border-bottom border-dark-subtle text-body"
                                :class="{
                                    active: tab === 'nueva-interaccion' || tab === 'historial-interacciones',
                                    'fw-bold': tab === 'nueva-interaccion' || tab === 'historial-interacciones',
                                }"
                                data-bs-toggle="dropdown"
                            >
                                Contactos
                            </button>
                            <span v-else class="nav-link dropdown-toggle border-0 py-3 border-bottom border-dark-subtle text-body disabled opacity-25">
                                Contactos
                            </span>
                            <ul v-if="paciente" class="dropdown-menu">
                                <li>
                                    <Link v-if="paciente.activo" :href="route('dashboard.pacientes.createInteraccion', paciente.id)" class="dropdown-item">Nuevo contacto</Link>
                                    <Link :href="route('dashboard.pacientes.interacciones', paciente.id)" class="dropdown-item">Historial de contactos</Link>
                                </li>
                            </ul>
                        </li>
                    </ul>
                    <div class="p-3">
                        <template v-if="tab === 'datos_paciente'">
                            <FormDatosPaciente ref="formPaciente" :paciente />
                        </template>
                        <template v-else-if="tab === 'datos-clinicos'">
                            <DatosClinicos :datos-clinicos :id-paciente="paciente.id" :filtros="filtrosDatosClinicos" />
                        </template>
                        <template v-else-if="tab === 'alertas'">
                            <AlertasPendientes :alertas="alertasPendientes" />
                        </template>
                        <template v-else-if="tab === 'alertas-historial'">
                            <AlertasHistorial :alertas="alertasHistoricas" />
                        </template>
                        <template v-else-if="tab === 'nueva-interaccion'">
                            <FormInteraccionesPaciente ref="formContacto" :id-paciente="paciente.id" :alerta :interaccion />
                        </template>
                        <template v-else-if="tab === 'historial-interacciones'">
                            <HistorialInteraccionesPaciente :id-paciente="paciente.id" :interacciones :interaccion :origen-interaccion />
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
                    <template v-if="mostrarGuardar">
                        <button type="button" class="btn btn-grey fw-semibold shadow me-2" @click="showModalCancelar">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-secondary text-white fw-semibold shadow" @click="showModalConfirmar">
                            Guardar
                        </button>
                    </template>
                </template>
            </div>
        </div>
    </Dashboard>

    <ModalCancelar
        ref="modalCancelar"
        @confirm="confirmCancelar"
    />

    <ModalConfirmar
        ref="modalConfirmar"
        @confirm="guardar"
    />
</template>

<style scoped>
.nav-tabs .nav-item:first-child a {
    border-bottom-left-radius: 0 !important;
}

.nav-tabs .nav-link.active, .nav-tabs .nav-item.show .nav-link {
    background-color: #c3c3c3;
}
</style>
