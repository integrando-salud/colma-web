<script setup>
import { ref, computed, onMounted } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { FORM_PACIENTE } from "@/Constants/paciente";
import Password from "@/Components/Form/Password.vue";
import { useNotificationStore } from "@/Stores/notification";

const props = defineProps({
    paciente: {
        type: Object,
        default: null
    },
});

const notification = useNotificationStore();
const page = usePage();
const formPaciente = useForm(structuredClone(FORM_PACIENTE));
const centrosSalud = ref([]);
const promotores = ref([]);
const diagnosticos = ref([]);
const paquetes = ref([]);
const cargandoCentrosSalud = ref(false);
const cargandoPromotores = ref(false);
const cargandoDiagnosticos = ref(false);
const cargandoPaquetes = ref(false);

const soloLectura = computed(() => {
    return props.paciente?.activo === false || page.props.usuario.rol?.nombre === 'promotor' && page.props.usuario.pais?.id === 2;
});

const verUsuarioPassword = computed(() => {
    return page.props.usuario.rol?.nombre !== 'promotor' || (page.props.usuario.rol?.nombre === 'promotor' && page.props.usuario.pais?.id !== 2);
});

const promotoresFiltered = computed(() => {
    return promotores.value.filter(promotor => promotor.centro_salud.id === formPaciente.centro_salud_id);
});

const resetPromotor = () => {
    formPaciente.promotor_id = '';
};

const submit = async () => {
    formPaciente.post(route('dashboard.pacientes.store'), {
        onError: () => {
            notification.errorNotification('El formulario contiene errores.');
        }
    });
};

const getCentrosSalud = async () => {
    cargandoCentrosSalud.value = true;

    try {
        const response = await axios.get(route('ajax.centrosSalud.get'), {
            params: {
                activo: 1,
                orderBy: 'nombre',
                orderDir: 'asc',
            }
        });

        centrosSalud.value = response.data;
    } catch (error) {
        notification.errorNotification('Error al obtener los centros de salud.');
    } finally {
        cargandoCentrosSalud.value = false;
    }
}

const getPromotores = async () => {
    cargandoPromotores.value = true;

    try {
        const response = await axios.get(route('ajax.usuarios.get'), {
            params: {
                rol: 'promotor',
                orderBy: 'nombre',
                orderDir: 'asc',
            }
        });

        promotores.value = response.data;
    } catch (error) {
        notification.errorNotification('Error al obtener los promotores.');
    } finally {
        cargandoPromotores.value = false;
    }
};

const getDiagnosticos = async () => {
    cargandoDiagnosticos.value = true;

    try {
        const response = await axios.get(route('ajax.diagnosticos.get'), {
            params: { activo: 1 }
        });

        diagnosticos.value = response.data;
    } catch (error) {
        notification.errorNotification('Error al obtener los diagnósticos.');
    } finally {
        cargandoDiagnosticos.value = false;
    }
};

const getPaquetes = async () => {
    cargandoPaquetes.value = true;

    try {
        const response = await axios.get(route('ajax.paquetes.get'), {
            params: {
                activo: 1,
                orderBy: 'nombre',
                orderDir: 'asc',
            }
        });

        paquetes.value = response.data;
    } catch (error) {
        notification.errorNotification('Error al obtener los paquetes.');
    } finally {
        cargandoPaquetes.value = false;
    }
};

const fillDataPaciente = () => {
    formPaciente.id = props.paciente.id;
    formPaciente.nombre = props.paciente.nombre;
    formPaciente.apellido = props.paciente.apellido;
    formPaciente.fecha_nacimiento = props.paciente.fecha_nacimiento;
    formPaciente.sexo = props.paciente.sexo;
    formPaciente.documento = props.paciente.documento;
    formPaciente.telefono = props.paciente.telefono;
    formPaciente.telefono2 = props.paciente.telefono2;
    formPaciente.email = props.paciente.email;
    formPaciente.direccion = props.paciente.direccion;
    formPaciente.referencia = props.paciente.referencia;
    formPaciente.observaciones = props.paciente.observaciones;
    formPaciente.centro_salud_id = props.paciente.centro_salud.id;
    formPaciente.promotor_id = props.paciente.promotor.id;
    formPaciente.fecha_enrolamiento = props.paciente.fecha_enrolamiento;
    formPaciente.diagnosticos = props.paciente.diagnosticos.map(diagnostico => diagnostico.id);
    formPaciente.paquete_id = formPaciente.paquete?.id;
};

defineExpose({ submit });

onMounted(async () => {
    await getCentrosSalud();
    await getPromotores();
    await getDiagnosticos();
    await getPaquetes();

    if (props.paciente) {
        fillDataPaciente();
    }
});
</script>

<template>
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
                            <input
                                type="text"
                                name="paciente_nombre"
                                id="paciente_nombre"
                                class="form-control"
                                :class="{ 'is-invalid': formPaciente.errors?.nombre }"
                                v-model="formPaciente.nombre"
                                :disabled="soloLectura"
                            />
                            <div class="invalid-feedback" v-if="formPaciente.errors?.nombre">
                                {{ formPaciente.errors.nombre }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="paciente_apellido" class="col-12 col-sm-4 col-xl-4 col-form-label">Apellido</label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <input
                                type="text"
                                name="paciente_apellido"
                                id="paciente_apellido"
                                class="form-control"
                                :class="{ 'is-invalid': formPaciente.errors?.apellido }"
                                v-model="formPaciente.apellido"
                                :disabled="soloLectura"
                            />
                            <div class="invalid-feedback" v-if="formPaciente.errors?.apellido">
                                {{ formPaciente.errors.apellido }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="paciente_fecha_nacimiento" class="col-12 col-sm-4 col-xl-4 col-form-label">Fecha de nacimiento</label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <input
                                type="date"
                                name="paciente_fecha_nacimiento"
                                id="paciente_fecha_nacimiento"
                                class="form-control"
                                :class="{ 'is-invalid': formPaciente.errors?.fecha_nacimiento }"
                                v-model="formPaciente.fecha_nacimiento"
                                :disabled="soloLectura"
                            />
                            <div class="invalid-feedback" v-if="formPaciente.errors?.fecha_nacimiento">
                                {{ formPaciente.errors.fecha_nacimiento }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="paciente_sexo" class="col-12 col-sm-4 col-xl-4 col-form-label">Género</label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <select
                                name="paciente_sexo"
                                id="paciente_sexo"
                                class="form-select"
                                :class="{ 'is-invalid': formPaciente.errors?.sexo }"
                                v-model="formPaciente.sexo"
                                :disabled="soloLectura"
                            >
                                <option value="">Seleccionar género</option>
                                <option value="F">Femenino</option>
                                <option value="M">Masculino</option>
                            </select>
                            <div class="invalid-feedback" v-if="formPaciente.errors?.sexo">
                                {{ formPaciente.errors.sexo }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="paciente_telefono" class="col-12 col-sm-4 col-xl-4 col-form-label">Teléfono 1</label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <input
                                type="text"
                                name="paciente_telefono"
                                id="paciente_telefono"
                                class="form-control"
                                :class="{ 'is-invalid': formPaciente.errors?.telefono }"
                                v-model="formPaciente.telefono"
                            />
                            <div class="invalid-feedback" v-if="formPaciente.errors?.telefono">
                                {{ formPaciente.errors.telefono }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="paciente_telefono_2" class="col-12 col-sm-4 col-xl-4 col-form-label">Teléfono 2</label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <input
                                type="text"
                                name="paciente_telefono_2"
                                id="paciente_telefono_2"
                                class="form-control"
                                :class="{ 'is-invalid': formPaciente.errors?.telefono2 }"
                                v-model="formPaciente.telefono2"
                            />
                            <div class="invalid-feedback" v-if="formPaciente.errors?.telefono2">
                                {{ formPaciente.errors.telefono2 }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="paciente_email" class="col-12 col-sm-4 col-xl-4 col-form-label">Correo electrónico</label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <input
                                type="text"
                                name="paciente_email"
                                id="paciente_email"
                                class="form-control"
                                :class="{ 'is-invalid': formPaciente.errors?.email }"
                                v-model="formPaciente.email"
                            />
                            <div class="invalid-feedback" v-if="formPaciente.errors?.email">
                                {{ formPaciente.errors.email }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="paciente_direccion" class="col-12 col-sm-4 col-xl-4 col-form-label">Dirección</label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <input
                                type="text"
                                name="paciente_direccion"
                                id="paciente_direccion"
                                class="form-control"
                                :class="{ 'is-invalid': formPaciente.errors?.direccion }"
                                v-model="formPaciente.direccion"
                            />
                            <div class="invalid-feedback" v-if="formPaciente.errors?.direccion">
                                {{ formPaciente.errors.direccion }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="paciente_referencia" class="col-12 col-sm-4 col-xl-4 col-form-label">Referencia</label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <input
                                type="text"
                                name="paciente_referencia"
                                id="paciente_referencia"
                                class="form-control"
                                :class="{ 'is-invalid': formPaciente.errors?.referencia }"
                                v-model="formPaciente.referencia"
                            />
                            <div class="invalid-feedback" v-if="formPaciente.errors?.referencia">
                                {{ formPaciente.errors.referencia }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-8 mb-sm-3">
                    <div class="row">
                        <label for="paciente_observaciones" class="col-12 col-sm-4 col-md-2 col-lg-2 col-form-label">Observaciones</label>
                        <div class="col-12 col-sm-8 col-md-10 col-lg-10 col-xl-9">
                            <textarea id="paciente_observaciones" name="paciente_observaciones" class="form-control h-100" v-model="formPaciente.observaciones"></textarea>
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
                            <select
                                name="paciente_centro_salud"
                                id="paciente_centro_salud"
                                class="form-select"
                                :class="{ 'is-invalid': formPaciente.errors?.centro_salud_id }"
                                v-model="formPaciente.centro_salud_id"
                                @change="resetPromotor"
                                :disabled="cargandoCentrosSalud || soloLectura"
                            >
                                <option value="">Seleccionar centro</option>
                                <option v-for="centro in centrosSalud" :key="centro.id" :value="centro.id">
                                    {{ centro.nombre }}
                                </option>
                            </select>
                            <div class="invalid-feedback" v-if="formPaciente.errors?.centro_salud_id">
                                {{ formPaciente.errors.centro_salud_id }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="paciente_promotor" class="col-12 col-sm-4 col-xl-4 col-form-label">Promotor asignado</label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <select
                                name="paciente_promotor"
                                id="paciente_promotor"
                                class="form-select"
                                :class="{ 'is-invalid': formPaciente.errors?.promotor_id }"
                                v-model="formPaciente.promotor_id"
                                :disabled="cargandoPromotores || soloLectura"
                            >
                                <option value="">Seleccionar</option>
                                <option v-for="promotor in promotoresFiltered" :key="promotor.id" :value="promotor.id">
                                    {{ promotor.nombre }}
                                </option>
                            </select>
                            <div class="invalid-feedback" v-if="formPaciente.errors?.promotor_id">
                                {{ formPaciente.errors.promotor_id }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="paciente_fecha_enrolamiento" class="col-12 col-sm-4 col-xl-4 col-form-label">Fecha enrolamiento</label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <input
                                type="date"
                                name="paciente_fecha_enrolamiento"
                                id="paciente_fecha_enrolamiento"
                                class="form-control"
                                :class="{ 'is-invalid': formPaciente.errors?.fecha_enrolamiento }"
                                v-model="formPaciente.fecha_enrolamiento"
                                :disabled="soloLectura"
                            />
                            <div class="invalid-feedback" v-if="formPaciente.errors?.fecha_enrolamiento">
                                {{ formPaciente.errors.fecha_enrolamiento }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label class="col-12 col-sm-4 col-xl-4 col-form-label">Diagnósticos</label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <template v-if="cargandoDiagnosticos">
                                <span class="text-body-secondary">
                                    <i class="fa-solid fa-spinner fa-spin"></i>
                                    Buscando diagnósticos
                                </span>
                            </template>
                            <template v-else>
                                <div class="form-check" :class="{ 'is-invalid': formPaciente.errors.diagnosticos }" v-for="diagnostico in diagnosticos" :key="diagnostico.id">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        :value="diagnostico.id"
                                        :id="`diagnostico-${diagnostico.id}`"
                                        v-model="formPaciente.diagnosticos"
                                        :disabled="soloLectura"
                                    />
                                    <label :for="`diagnostico-${diagnostico.id}`" class="form-check-label">
                                        {{ diagnostico.nombre }}
                                    </label>
                                </div>
                                <div class="invalid-feedback" v-if="formPaciente.errors?.diagnosticos">
                                    {{ formPaciente.errors.diagnosticos }}
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="paciente_paquete" class="col-12 col-sm-4 col-xl-4 col-form-label">Paquete asignado</label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <select
                                name="paciente_paquete"
                                id="paciente_paquete"
                                class="form-select"
                                :class="{ 'is-invalid': formPaciente.errors.paquete_id }"
                                v-model="formPaciente.paquete_id"
                                :disabled="cargandoPaquetes || soloLectura"
                            >
                                <option value="">Seleccionar</option>
                                <option v-for="paquete in paquetes" :key="paquete.id" :value="paquete.id">
                                    {{ paquete.nombre }}
                                </option>
                            </select>
                            <div class="invalid-feedback" v-if="formPaciente.errors?.paquete_id">
                                {{ formPaciente.errors.paquete_id }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <template v-if="verUsuarioPassword">
            <div class="col-12 mt-3 mt-sm-0">
                <h4 class="fs-5 text-primary-emphasis">Asignar usuario y contraseña</h4>
            </div>
            <div class="col-12">
                <div class="row">
                    <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                        <div class="row">
                            <label for="paciente_documento" class="col-12 col-sm-4 col-xl-4 col-form-label">DNI</label>
                            <div class="col-12 col-sm-8 col-xl-6">
                                <input
                                    type="number"
                                    name="paciente_documento"
                                    id="paciente_documento"
                                    class="form-control"
                                    :class="{ 'is-invalid': formPaciente.errors.documento }"
                                    v-model="formPaciente.documento"
                                    :disabled="soloLectura"
                                />
                                <div class="invalid-feedback" v-if="formPaciente.errors?.documento">
                                    {{ formPaciente.errors.documento }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4" v-if="!paciente">
                        <div class="row">
                            <label for="paciente_password" class="col-12 col-sm-4 col-xl-4 col-form-label">Contraseña</label>
                            <div class="col-12 col-sm-8 col-xl-6">
                                <Password
                                    name="paciente_password"
                                    id="paciente_password"
                                    :error="formPaciente.errors.password"
                                    v-model="formPaciente.password"
                                    :disabled="soloLectura"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>

</style>
