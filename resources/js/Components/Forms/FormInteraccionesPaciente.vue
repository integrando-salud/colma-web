<script setup>
import { ref, computed, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import { FORM_CONTACTO } from "@/Constants/paciente";
import { useNotificationStore } from "@/Stores/notification";
import DetalleAlertaInteraccion from "@/Components/DetalleAlertaInteraccion.vue";

const props = defineProps({
    idPaciente: {
        type: Number,
        required: true,
    },
    alerta: {
        type: Object,
        default: null,
    }
});

const notification = useNotificationStore();
const formContacto = useForm(structuredClone(FORM_CONTACTO));
const razonesNoContacto = ref([]);
const loadingRazonesNoContacto = ref(false);
const modalidades = ref([]);
const loadingModalidades = ref(false);
const acciones = ref([]);
const loadingAcciones = ref(false);
const motivosContacto = ref([]);
const loadingMotivosContacto = ref(false);
const motivosReprogramacion = ref([]);
const loadingMotivosReprogramacion = ref(false);

const verRazonNoContacto = computed(() => {
    return formContacto.contactado !== '' && formContacto.contactado == 0;
});

const disableMotivo = computed(() => {
    return props.alerta && props.alerta.tipo === 'clinica';
});

const getRazonesNoContacto = async () => {
    loadingRazonesNoContacto.value = true;

    try {
        const { data } = await axios.get(route('ajax.interacciones.razonesNoContacto'));
        razonesNoContacto.value = data;
    } catch (error) {
        notification.errorNotification('Error al obtener las razones de no contacto');
    } finally {
        loadingRazonesNoContacto.value = false;
    }
};

const getModalidades = async () => {
    loadingModalidades.value = true;

    try {
        const { data } = await axios.get(route('ajax.interacciones.modalidades'));
        modalidades.value = data;
    } catch (error) {
        notification.errorNotification('Error al obtener las modalidades');
    } finally {
        loadingModalidades.value = false;
    }
};

const getAcciones = async () => {
    loadingAcciones.value = true;

    try {
        const { data } = await axios.get(route('ajax.interacciones.acciones'));
        acciones.value = data;
    } catch (error) {
        notification.errorNotification('Error al obtener las acciones');
    } finally {
        loadingAcciones.value = false;
    }
};

const getMotivosContacto = async () => {
    loadingMotivosContacto.value = true;

    try {
        const { data } = await axios.get(route('ajax.interacciones.motivosContacto'));
        motivosContacto.value = data;
    } catch (error) {
        notification.errorNotification('Error al obtener los motivos de contacto');
    } finally {
        loadingMotivosContacto.value = false;
    }
};

const getMotivosReprogramacion = async () => {
    loadingMotivosReprogramacion.value = true;

    try {
        const { data } = await axios.get(route('ajax.interacciones.motivosReprogramacion'));
        motivosReprogramacion.value = data;
    } catch (error) {
        notification.errorNotification('Error al obtener los motivos de reprogramación');
    } finally {
        loadingMotivosReprogramacion.value = false;
    }
};

const submit = async () => {
    formContacto.post(route('interacciones.store'), {
        onError: () => {
            notification.errorNotification('El formulario contiene errores.');
        }
    })
};

defineExpose({ submit });

onMounted(async () => {
    await getRazonesNoContacto();
    await getModalidades();
    await getAcciones();
    await getMotivosContacto();
    await getMotivosReprogramacion();
    formContacto.paciente_id = props.idPaciente;

    if (props.alerta?.id) {
        formContacto.alerta_id = props.alerta.id;

        if (props.alerta.tipo === 'clinica') {
            formContacto.motivo = 'Alerta clínica';
        }
    }
});
</script>

<template>
    <div class="row">
        <div class="col-12">
            <h4 class="fs-5 text-primary-emphasis">Formulario de contacto</h4>
        </div>

        <DetalleAlertaInteraccion v-if="alerta" :alerta />

        <div class="col-12">
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="contacto_resultado" class="col-12 col-sm-4 col-xl-4 col-form-label">Resultado del contacto</label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <select
                                name="contacto_resultado"
                                id="contacto_resultado"
                                class="form-select"
                                :class="{ 'is-invalid': formContacto.errors?.contactado }"
                                v-model="formContacto.contactado"
                                @change="formContacto.razon_no_contacto = ''"
                            >
                                <option value="">Seleccionar resultado</option>
                                <option value="1">Contactado</option>
                                <option value="0">No contactado</option>
                            </select>
                            <div class="invalid-feedback" v-if="formContacto.errors?.contactado">
                                {{ formContacto.errors.contactado }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3" v-if="verRazonNoContacto">
                    <div class="row">
                        <label for="contacto_razon_no_contacto" class="col-12 col-sm-4 col-xl-4 col-form-label">
                            Razón de no contacto
                        </label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <select
                                name="contacto_razon_no_contacto"
                                id="contacto_razon_no_contacto"
                                class="form-select"
                                :class="{ 'is-invalid': formContacto.errors?.razon_no_contacto }"
                                v-model="formContacto.razon_no_contacto"
                                :disabled="loadingRazonesNoContacto"
                            >
                                <option value="">Seleccionar razón</option>
                                <option v-for="razon in razonesNoContacto" :value="razon">{{ razon }}</option>
                            </select>
                            <div class="invalid-feedback" v-if="formContacto.errors?.razon_no_contacto">
                                {{ formContacto.errors.razon_no_contacto }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="contacto_fecha" class="col-12 col-sm-4 col-xl-4 col-form-label">Fecha de contacto</label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <input type="date" name="contacto_fecha" id="contacto_fecha" class="form-control" :class="{ 'is-invalid': formContacto.errors?.fecha }" v-model="formContacto.fecha" />
                            <div class="invalid-feedback" v-if="formContacto.errors?.fecha">
                                {{ formContacto.errors.fecha }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="contacto_hora" class="col-12 col-sm-4 col-xl-4 col-form-label">Hora de contacto</label>
                        <div class="col-12 col-sm-8 col-xl-4">
                            <input type="time" name="contacto_hora" id="contacto_hora" class="form-control" :class="{ 'is-invalid': formContacto.errors?.hora }" v-model="formContacto.hora" />
                            <div class="invalid-feedback" v-if="formContacto.errors?.hora">
                                {{ formContacto.errors.hora }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <div class="col-12 mb-sm-3">
                            <div class="row">
                                <label for="contacto_modalidad" class="col-12 col-sm-4 col-xl-4 col-form-label">Modalidad</label>
                                <div class="col-12 col-sm-8 col-xl-6">
                                    <select
                                        name="contacto_modalidad"
                                        id="contacto_modalidad"
                                        class="form-select"
                                        :class="{ 'is-invalid': formContacto.errors?.modalidad }"
                                        v-model="formContacto.modalidad"
                                        :disabled="loadingModalidades"
                                    >
                                        <option value="">Seleccionar modalidad</option>
                                        <option v-for="modalidad in modalidades" :value="modalidad">{{ modalidad }}</option>
                                    </select>
                                    <div class="invalid-feedback" v-if="formContacto.errors?.modalidad">
                                        {{ formContacto.errors.modalidad }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="row">
                                <label for="contacto_motivo" class="col-12 col-sm-4 col-xl-4 col-form-label">Motivo</label>
                                <div class="col-12 col-sm-8 col-xl-6">
                                    <select
                                        name="contacto_motivo"
                                        id="contacto_motivo"
                                        class="form-select"
                                        :class="{ 'is-invalid': formContacto.errors?.motivo }"
                                        v-model="formContacto.motivo"
                                        :disabled="loadingMotivosContacto || disableMotivo"
                                    >
                                        <option value="">Seleccionar motivo</option>
                                        <option v-for="motivo in motivosContacto" :value="motivo">{{ motivo }}</option>
                                    </select>
                                    <div class="invalid-feedback" v-if="formContacto.errors?.motivo">
                                        {{ formContacto.errors.motivo }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-8 mb-sm-3">
                    <div class="row h-100">
                        <label for="contacto_observaciones" class="col-12 col-sm-4 col-md-2 col-form-label">Observaciones</label>
                        <div class="col-12 col-sm-8 col-md-10 col-xl-6">
                            <textarea id="contacto_observaciones" name="contacto_observaciones" class="form-control h-100" :class="{ 'is-invalid': formContacto.errors?.observaciones }" v-model="formContacto.observaciones"></textarea>
                            <div class="invalid-feedback" v-if="formContacto.errors?.observaciones">
                                {{ formContacto.errors.observaciones }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row" v-if="formContacto.motivo === 'Alerta clínica'">
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="contacto_accion" class="col-12 col-sm-4 col-xl-4 col-form-label">Acción</label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <select
                                name="contacto_accion"
                                id="contacto_accion"
                                class="form-select"
                                :class="{ 'is-invalid': formContacto.errors?.accion }"
                                v-model="formContacto.accion"
                                :disabled="loadingAcciones"
                            >
                                <option value="">Seleccionar acción</option>
                                <option v-for="accion in acciones" :value="accion">{{ accion }}</option>
                            </select>
                            <div class="invalid-feedback" v-if="formContacto.errors?.accion">
                                {{ formContacto.errors.accion }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <template v-if="formContacto.motivo === 'Reprogramación'">
                <div class="row">
                    <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                        <div class="row">
                            <label for="contacto_fecha_reprogramacion" class="col-12 col-sm-4 col-xl-4 col-form-label">Nueva fecha de contacto</label>
                            <div class="col-12 col-sm-8 col-xl-6">
                                <input type="date" name="contacto_fecha_reprogramacion" id="contacto_fecha_reprogramacion" class="form-control" :class="{ 'is-invalid': formContacto.errors?.fecha_reprogramacion }" v-model="formContacto.fecha_reprogramacion" />
                                <div class="invalid-feedback" v-if="formContacto.errors?.fecha_reprogramacion">
                                    {{ formContacto.errors.fecha_reprogramacion }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                        <div class="row">
                            <label for="contacto_motivo_reprogramacion" class="col-12 col-sm-4 col-xl-4 col-form-label">Motivo reprogramación</label>
                            <div class="col-12 col-sm-8 col-xl-6">
                                <select
                                    name="contacto_motivo_reprogramacion"
                                    id="contacto_motivo_reprogramacion"
                                    class="form-select"
                                    :class="{ 'is-invalid': formContacto.errors?.motivo_reprogramacion }"
                                    v-model="formContacto.motivo_reprogramacion"
                                    :disabled="loadingMotivosReprogramacion"
                                >
                                    <option value="">Seleccionar motivo</option>
                                    <option v-for="motivo in motivosReprogramacion" :value="motivo">{{ motivo }}</option>
                                </select>
                                <div class="invalid-feedback" v-if="formContacto.errors?.motivo_reprogramacion">
                                    {{ formContacto.errors.motivo_reprogramacion }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<style scoped>

</style>
