<script setup>
import { computed, toRef, watch } from 'vue';
import Password from '@/Components/Form/Password.vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    roles: {
        type: Array,
        default: () => [],
    },
    paises: {
        type: Array,
        default: () => [],
    },
    centrosSalud: {
        type: Array,
        default: () => [],
    },
    supervisores: {
        type: Array,
        default: () => [],
    },
    usuarioSeleccionado: {
        type: Object,
        default: null,
    },
    centroSaludDisponible: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['changeRol']);
const formUsuario = toRef(props, 'form');

const esPromotor = computed(() => formUsuario.value.rol === 'promotor');
const supervisorSeleccionado = computed(() => {
    return props.supervisores.find((supervisor) => String(supervisor.id) === String(formUsuario.value.supervisor_id));
});
const paisBloqueado = computed(() => Boolean(props.usuarioSeleccionado) || esPromotor.value);
const centroSaludBloqueado = computed(() => !formUsuario.value.pais_id);
const centrosSaludFiltrados = computed(() => {
    if (!formUsuario.value.pais_id) {
        return props.centrosSalud;
    }

    return props.centrosSalud.filter((centro) => String(centro.pais?.id) === String(formUsuario.value.pais_id));
});

const asignarPaisSupervisor = () => {
    if (!esPromotor.value) {
        return;
    }

    formUsuario.value.pais_id = supervisorSeleccionado.value?.pais?.id ?? '';
    formUsuario.value.centro_salud_id = supervisorSeleccionado.value?.centro_salud?.id ?? '';
};

const resetCentroSaludSiNoCorresponde = () => {
    if (!formUsuario.value.centro_salud_id) {
        return;
    }

    const centroDisponible = centrosSaludFiltrados.value.some((centro) => String(centro.id) === String(formUsuario.value.centro_salud_id));

    if (!centroDisponible) {
        formUsuario.value.centro_salud_id = '';
    }
};

watch(() => formUsuario.value.supervisor_id, asignarPaisSupervisor);
watch(esPromotor, asignarPaisSupervisor);
watch(() => formUsuario.value.pais_id, resetCentroSaludSiNoCorresponde);
</script>

<template>
    <div class="row">
        <div class="cols-12 col-lg-6">
            <div class="row">
                <div class="col-12">
                    <div class="row mb-3">
                        <label for="tipo" class="col-12 col-xl-4 col-form-label">Tipo usuario</label>
                        <div class="col-12 col-xl-8">
                            <select id="tipo" name="tipo" class="form-select" :class="{ 'is-invalid': formUsuario.errors?.rol }" v-model="formUsuario.rol" @change="emit('changeRol')">
                                <option value="">Seleccionar tipo</option>
                                <option v-for="rol in roles" :key="rol.id" :value="rol.nombre">{{ rol.titulo }}</option>
                            </select>
                            <div class="invalid-feedback" v-if="formUsuario.errors?.rol">
                                {{ formUsuario.errors.rol }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12" v-if="formUsuario.rol === 'promotor'">
                    <div class="row mb-3">
                        <label for="supervisor" class="col-12 col-xl-4 col-form-label">Supervisor</label>
                        <div class="col-12 col-xl-8">
                            <select id="supervisor" name="supervisor" class="form-select" :class="{ 'is-invalid': formUsuario.errors?.supervisor_id }" v-model="formUsuario.supervisor_id">
                                <option value="">Seleccionar supervisor</option>
                                <option v-for="supervisor in supervisores" :key="supervisor.id" :value="supervisor.id">{{ supervisor.nombre }}</option>
                            </select>
                            <div class="invalid-feedback" v-if="formUsuario.errors?.supervisor_id">
                                {{ formUsuario.errors.supervisor_id }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="row mb-3">
                        <label for="documento" class="col-12 col-xl-4 col-form-label">DNI</label>
                        <div class="col-12 col-xl-8">
                            <input
                                type="text"
                                inputmode="numeric"
                                id="documento"
                                name="documento"
                                class="form-control"
                                :class="{ 'is-invalid': formUsuario.errors?.documento }"
                                v-model="formUsuario.documento"
                                maxlength="8"
                            />
                            <div class="invalid-feedback" v-if="formUsuario.errors?.documento">
                                {{ formUsuario.errors.documento }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="row mb-3">
                        <label for="email" class="col-12 col-xl-4 col-form-label">Correo electrónico</label>
                        <div class="col-12 col-xl-8">
                            <input type="email" id="email" name="email" class="form-control" :class="{ 'is-invalid': formUsuario.errors?.email }" v-model="formUsuario.email" />
                            <div class="invalid-feedback" v-if="formUsuario.errors?.email">
                                {{ formUsuario.errors.email }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12" v-if="formUsuario.id === null">
                    <div class="row mb-3">
                        <label for="password" class="col-12 col-xl-4 col-form-label">Contraseña</label>
                        <div class="col-12 col-xl-8">
                            <Password id="password" name="password" :error="formUsuario.errors?.password" v-model="formUsuario.password" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="cols-12 col-lg-6">
            <div class="row">
                <div class="col-12">
                    <div class="row mb-3">
                        <label for="nombre" class="col-12 col-xl-4 col-form-label">Nombre usuario</label>
                        <div class="col-12 col-xl-8">
                            <input type="text" id="nombre" name="nombre" class="form-control" :class="{ 'is-invalid': formUsuario.errors?.nombre }" v-model="formUsuario.nombre" />
                            <div class="invalid-feedback" v-if="formUsuario.errors?.nombre">
                                {{ formUsuario.errors.nombre }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="row mb-3">
                        <label for="sexo" class="col-12 col-xl-4 col-form-label">Género</label>
                        <div class="col-12 col-xl-8">
                            <select id="sexo" name="sexo" class="form-select" :class="{ 'is-invalid': formUsuario.errors?.sexo }" v-model="formUsuario.sexo">
                                <option value="">Seleccionar género</option>
                                <option value="F">Femenino</option>
                                <option value="M">Masculino</option>
                            </select>
                            <div class="invalid-feedback" v-if="formUsuario.errors?.sexo">
                                {{ formUsuario.errors.sexo }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="row mb-3">
                        <label for="pais" class="col-12 col-xl-4 col-form-label">País</label>
                        <div class="col-12 col-xl-8">
                            <select
                                id="pais"
                                name="pais"
                                class="form-select"
                                :class="{ 'is-invalid': formUsuario.errors?.pais_id }"
                                v-model="formUsuario.pais_id"
                                :disabled="paisBloqueado"
                            >
                                <option value="">Seleccionar país</option>
                                <option v-for="pais in paises" :key="pais.id" :value="pais.id">{{ pais.nombre }}</option>
                            </select>
                            <div class="invalid-feedback" v-if="formUsuario.errors?.pais_id">
                                {{ formUsuario.errors.pais_id }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12" v-if="centroSaludDisponible">
                    <div class="row mb-3">
                        <label for="centro_salud" class="col-12 col-xl-4 col-form-label">Centro de salud</label>
                        <div class="col-12 col-xl-8">
                            <select
                                id="centro_salud"
                                name="centro_salud"
                                class="form-select"
                                :class="{ 'is-invalid': formUsuario.errors?.centro_salud_id }"
                                v-model="formUsuario.centro_salud_id"
                                :disabled="centroSaludBloqueado"
                            >
                                <option value="">Seleccionar centro</option>
                                <option v-for="centro in centrosSaludFiltrados" :key="centro.id" :value="centro.id">{{ centro.nombre }}</option>
                            </select>
                            <div class="invalid-feedback" v-if="formUsuario.errors?.centro_salud_id">
                                {{ formUsuario.errors.centro_salud_id }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
