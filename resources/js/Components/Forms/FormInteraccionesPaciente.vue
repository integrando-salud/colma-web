<script setup>
import { computed, onMounted, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { FORM_CONTACTO } from "@/Constants/paciente";
import { useNotificationStore } from "@/Stores/notification";
import DetalleAlertaInteraccion from "@/Components/Pacientes/DetalleAlertaInteraccion.vue";
import { vMaska } from "maska/vue";

const props = defineProps({
    idPaciente: {
        type: Number,
        required: true,
    },
    alerta: {
        type: Object,
        default: null,
    },
    interaccion: {
        type: Object,
        default: null,
    }
});

const notification = useNotificationStore();
const formContacto = useForm(structuredClone(FORM_CONTACTO));

const modalidades = [
    'Llamada por teléfono / WhatsApp',
    'Visita domiciliaria',
];

const motivosReprogramacion = [
    'No responde',
    'Teléfono apagado',
    'Número equivocado / cambió de número',
    'Pidió que se le llame más tarde',
];

const dificultadesNoAdherencia = [
    'Problemas con el internet (no tiene Wi-Fi o datos móviles para subir la información de las sesiones)',
    'Problemas con la señal (completó las sesiones, pero la información no se subió)',
    'Problemas para usar el celular (ej. no sabe cómo abrir el aplicativo)',
    'El celular no funciona y no puede usar el aplicativo',
    'Le robaron el celular',
    'Falta de interés',
    'Falta de tiempo',
    'Ya no quiere participar en el proyecto',
    'Otro',
];

const dificultadesProblemasConexion = [
    'Problemas con el internet (no tiene Wi-Fi o datos móviles para subir la información de las sesiones)',
    'Problemas con la señal (completó las sesiones, pero la información no se subió)',
    'Problemas para usar el celular (ej. no sabe cómo abrir el aplicativo)',
    'El celular no funciona y no puede usar el aplicativo',
    'Le robaron el celular',
    'Otro',
];

const dificultadesPedidoAyuda = [
    'Problemas con el celular',
    'Problemas con el aplicativo',
    'Problemas con la conexión a internet',
    'Ya no quiere participar en el proyecto',
    'Otro',
];

const motivosContacto = computed(() => {
    if (props.alerta?.id) {
        if (props.alerta.tipo === 'clinica') {
            return [ 'Alerta clínica' ];
        } else {
            return [
                'No completa las sesiones',
                'Problemas de conexión',
                'Pedido de ayuda a través del aplicativo',
            ];
        }
    } else {
        return [
            'Agendar cita de entrenamiento',
            'Cita de entrenamiento',
            'Seguimiento 2 semanas luego del entrenamiento',
            'Seguimiento mensual',
            'Agendar cita final',
            'Cita final',
            'Re-entrenamiento',
            'Contacto iniciado por el participante',
        ];
    }
});

const fechaProgramadaCitaTitle = computed(() => {
    let title = 'Fecha programada para cita';

    if (formContacto.motivo === 'Agendar cita de entrenamiento') {
        title += ' de entrenamiento';
    }

    if (formContacto.motivo === 'Agendar cita final') {
        title += ' final';
    }

    return title;
});

const lugarAcordadoCitaTitle = computed(() => {
    let title = 'Lugar acordado para cita';

    if (formContacto.motivo === 'Agendar cita de entrenamiento') {
        title += ' de entrenamiento';
    }

    if (formContacto.motivo === 'Agendar cita final') {
        title += ' final';
    }

    return title;
});

const handleChangeResultado = () => {
    if (formContacto.contactado !== 0) {
        formContacto.motivo_reprogramacion = '';
        formContacto.nueva_fecha_contacto = null;
    }
};

const handleChangeMotivoContacto = () => {
    if (props.alerta?.id) {
        if (formContacto.motivo !== 'No completa las sesiones') {
            formContacto.no_adherencia_pregunta_a = [];
            formContacto.no_adherencia_pregunta_b = '';
        }

        if (formContacto.motivo !== 'Problemas de conexión') {
            formContacto.problemas_conexion_pregunta_a = [];
            formContacto.problemas_conexion_pregunta_b = '';
        }

        if (formContacto.motivo !== 'Alerta clínica') {
            formContacto.alerta_clinica_pregunta_a = '';
        }

        if (formContacto.motivo !== 'Pedido de ayuda a través del aplicativo') {
            formContacto.pedido_ayuda_pregunta_a = [];
            formContacto.pedido_ayuda_pregunta_b = null;
        }
    } else {
        if (formContacto.motivo !== 'Cita de entrenamiento' && formContacto.motivo !== 'Cita final' && formContacto.motivo !== 'Re-entrenamiento') {
            formContacto.fecha_cita_final = null;
            formContacto.hora_cita_final = null;
            formContacto.lugar_cita_final = '';
            formContacto.lugar_cita_final_otro = null;
        }

        if (formContacto.motivo !== 'Seguimiento 2 semanas luego del entrenamiento' && formContacto.motivo !== 'Seguimiento mensual') {
            formContacto.seguimiento_pregunta_a = '';
            formContacto.seguimiento_pregunta_b = null;
            formContacto.seguimiento_pregunta_c = '';
            formContacto.seguimiento_pregunta_d = null;
            formContacto.seguimiento_pregunta_e = '';
            formContacto.seguimiento_pregunta_f = null;
        }

        if (formContacto.motivo !== 'Contacto iniciado por el participante') {
            formContacto.razon_contacto_participante = '';
            formContacto.razon_contacto_participante_otro = null;
            formContacto.contacto_participante_acciones = null;
        }
    }
};

const handleChangeSeguimientoPreguntaA = () => {
    if (formContacto.seguimiento_pregunta_a !== 'Si') {
        formContacto.seguimiento_pregunta_b = null;
        formContacto.seguimiento_pregunta_c = '';
    }
};

const handleChangeSeguimientoPreguntaC = () => {
    if (formContacto.seguimiento_pregunta_c !== 'No') {
        formContacto.seguimiento_pregunta_f = null;
    }
};

const handleChangeSeguimientoPreguntaE = () => {
    if (formContacto.seguimiento_pregunta_e !== 'No') {
        formContacto.seguimiento_pregunta_f = null;
    }
};

const handleChangeLugarCita = () => {
    if (formContacto.lugar_cita !== 'Otro') {
        formContacto.lugar_cita_otro = null;
    }
};

const handleChangeLugarCitaFinal = () => {
    if (formContacto.lugar_cita_final !== 'Otro') {
        formContacto.lugar_cita_final_otro = null;
    }
};

const handleChangeRazonContactoParticipante = () => {
    if (formContacto.razon_contacto_participante !== 'Otro') {
        formContacto.razon_contacto_participante_otro = null;
        formContacto.contacto_participante_acciones = null;
    }
};

const handleChangeNoAdherenciaPreguntaA = () => {
    if (!formContacto.no_adherencia_pregunta_a.includes('Otro')) {
        formContacto.no_adherencia_otro = null;
    }

    if (!formContacto.no_adherencia_pregunta_a.includes('Ya no quiere participar en el proyecto')) {
        formContacto.no_adherencia_pregunta_b = '';
        formContacto.no_adherencia_pregunta_c = null;
    }
};

const handleChangeNoAdherenciaPreguntaB = () => {
    if (formContacto.no_adherencia_pregunta_b !== 'No') {
        formContacto.no_adherencia_pregunta_c = null;
    }
};

const handleChangeProblemasConexionPreguntaA = () => {
    if (!formContacto.problemas_conexion_pregunta_a.includes('Otro')) {
        formContacto.problemas_conexion_otro = null;
    }
};

const handleChangeProblemasConexionPreguntaB = () => {
    if (formContacto.problemas_conexion_pregunta_b !== 'No') {
        formContacto.problemas_conexion_pregunta_c = null;
    }
};

const handleChangeAlertaClinicaPreguntaA = () => {
    if (formContacto.alerta_clinica_pregunta_a !== 'No') {
        formContacto.alerta_clinica_pregunta_b = '';
    }

    if (formContacto.alerta_clinica_pregunta_a !== 'Si') {
        formContacto.alerta_clinica_pregunta_c = '';
    }
};

const handleChangeAlertaClinicaPreguntaC = () => {
    if (formContacto.alerta_clinica_pregunta_c !== 'No') {
        formContacto.alerta_clinica_pregunta_d = null;
    }

    if (formContacto.alerta_clinica_pregunta_c !== 'Si') {
        formContacto.alerta_clinica_pregunta_e = '';
        formContacto.alerta_clinica_pregunta_f = null;
    }
};

const handleChangeAlertaClinicaPreguntaE = () => {
    if (formContacto.alerta_clinica_pregunta_e !== 'No') {
        formContacto.alerta_clinica_pregunta_f = null;
    }
};

const handleChangePedidoAyudaPreguntaA = () => {
    if (!formContacto.pedido_ayuda_pregunta_a.includes('Otro')) {
        formContacto.pedido_ayuda_otro = null;
    }

    if (!formContacto.pedido_ayuda_pregunta_a.includes('Ya no quiere participar en el proyecto')) {
        formContacto.pedido_ayuda_pregunta_b = null;
        formContacto.pedido_ayuda_pregunta_c = '';
    }
};

const handleChangePedidoAyudaPreguntaC = () => {
    if (formContacto.pedido_ayuda_pregunta_c !== 'No') {
        formContacto.pedido_ayuda_pregunta_d = null;
    }
};

const submit = async () => {
    formContacto.post(route('interacciones.store'), {
        onError: () => {
            notification.errorNotification('El formulario contiene errores.');
        }
    })
};

const fillDataInteraccion = () => {
    formContacto.id = props.interaccion.id;
    formContacto.paciente_id = props.interaccion.paciente.id;
    formContacto.alerta_id = props.interaccion.alerta.id;
    formContacto.contactado = props.interaccion.contactado;
    formContacto.fecha = props.interaccion.fecha;
    formContacto.hora = props.interaccion.hora;
    formContacto.modalidad = props.interaccion.modalidad;
    formContacto.motivo = props.interaccion.motivo;
    formContacto.observaciones = props.interaccion.observaciones;
    formContacto.motivo_reprogramacion = props.interaccion.motivo_reprogramacion;
    formContacto.fecha_cita = props.interaccion.fecha_cita;
    formContacto.lugar_cita = props.interaccion.lugar_cita;
    formContacto.lugar_cita_otro = props.interaccion.lugar_cita_otro;
    formContacto.fecha_cita_final = props.interaccion.fecha_cita_final;
    formContacto.hora_cita_final = props.interaccion.hora_cita_final;
    formContacto.lugar_cita_final = props.interaccion.lugar_cita_final;
    formContacto.lugar_cita_final_otro = props.interaccion.lugar_cita_final_otro;
    formContacto.seguimiento_pregunta_a = props.interaccion.seguimiento_pregunta_a;
    formContacto.seguimiento_pregunta_b = props.interaccion.seguimiento_pregunta_b;
    formContacto.seguimiento_pregunta_c = props.interaccion.seguimiento_pregunta_c;
    formContacto.seguimiento_pregunta_d = props.interaccion.seguimiento_pregunta_d;
    formContacto.seguimiento_pregunta_e = props.interaccion.seguimiento_pregunta_e;
    formContacto.seguimiento_pregunta_f = props.interaccion.seguimiento_pregunta_f;
    formContacto.razon_contacto_participante = props.interaccion.razon_contacto_participante;
    formContacto.razon_contacto_participante_otro = props.interaccion.razon_contacto_participante_otro;
    formContacto.contacto_participante_acciones = props.interaccion.contacto_participante_acciones;
    formContacto.nueva_fecha_contacto = props.interaccion.nueva_fecha_contacto;
    formContacto.no_adherencia_pregunta_a = props.interaccion.no_adherencia_pregunta_a;
    formContacto.no_adherencia_otro = props.interaccion.no_adherencia_otro;
    formContacto.no_adherencia_pregunta_b = props.interaccion.no_adherencia_pregunta_b;
    formContacto.no_adherencia_pregunta_c = props.interaccion.no_adherencia_pregunta_c;
    formContacto.problemas_conexion_pregunta_a = props.interaccion.problemas_conexion_pregunta_a;
    formContacto.problemas_conexion_otro = props.interaccion.problemas_conexion_otro;
    formContacto.problemas_conexion_pregunta_b = props.interaccion.problemas_conexion_pregunta_b;
    formContacto.problemas_conexion_pregunta_c = props.interaccion.problemas_conexion_pregunta_c;
    formContacto.alerta_clinica_pregunta_a = props.interaccion.alerta_clinica_pregunta_a;
    formContacto.alerta_clinica_pregunta_b = props.interaccion.alerta_clinica_pregunta_b;
    formContacto.alerta_clinica_pregunta_c = props.interaccion.alerta_clinica_pregunta_c;
    formContacto.alerta_clinica_pregunta_d = props.interaccion.alerta_clinica_pregunta_d;
    formContacto.alerta_clinica_pregunta_e = props.interaccion.alerta_clinica_pregunta_e;
    formContacto.alerta_clinica_pregunta_f = props.interaccion.alerta_clinica_pregunta_f;
    formContacto.pedido_ayuda_pregunta_a = props.interaccion.pedido_ayuda_pregunta_a;
    formContacto.pedido_ayuda_otro = props.interaccion.pedido_ayuda_otro;
    formContacto.pedido_ayuda_pregunta_b = props.interaccion.pedido_ayuda_pregunta_b;
    formContacto.pedido_ayuda_pregunta_c = props.interaccion.pedido_ayuda_pregunta_c;
    formContacto.pedido_ayuda_pregunta_d = props.interaccion.pedido_ayuda_pregunta_d;
};

defineExpose({ submit });

onMounted(async () => {
    formContacto.paciente_id = props.idPaciente;

    if (props.interaccion) {
        fillDataInteraccion();
    }

    if (props.alerta?.id) {
        formContacto.alerta_id = props.alerta.id;

        if (props.alerta.tipo === 'clinica') {
            formContacto.motivo = 'Alerta clínica';
        } else if (props.alerta.tipo === 'operativa') {
            switch (props.alerta.descripcion) {
                case 'No completa sesiones':
                    formContacto.motivo = 'No completa las sesiones';
                    break;
                case 'No se conecta':
                    formContacto.motivo = 'Problemas de conexión';
                    break;
                case 'Pedido de ayuda':
                    formContacto.motivo = 'Pedido de ayuda a través del aplicativo';
                    break;
            }
        }
    }
});

watch(() => formContacto.motivo, () => {
    handleChangeMotivoContacto();
});

watch(() => formContacto.contactado, () => {
    handleChangeResultado();
});

watch(() => formContacto.seguimiento_pregunta_a, () => {
    handleChangeSeguimientoPreguntaA();
});

watch(() => formContacto.seguimiento_pregunta_c, () => {
    handleChangeSeguimientoPreguntaC();
});

watch(() => formContacto.seguimiento_pregunta_e, () => {
    handleChangeSeguimientoPreguntaE();
});

watch(() => formContacto.lugar_cita, () => {
    handleChangeLugarCita();
});

watch(() => formContacto.lugar_cita_final, () => {
    handleChangeLugarCitaFinal();
});

watch(() => formContacto.razon_contacto_participante, () => {
    handleChangeRazonContactoParticipante();
});

watch(() => formContacto.no_adherencia_pregunta_a, () => {
    handleChangeNoAdherenciaPreguntaA();
});

watch(() => formContacto.no_adherencia_pregunta_b, () => {
    handleChangeNoAdherenciaPreguntaB();
});

watch(() => formContacto.problemas_conexion_pregunta_a, () => {
    handleChangeProblemasConexionPreguntaA();
});

watch(() => formContacto.problemas_conexion_pregunta_b, () => {
    handleChangeProblemasConexionPreguntaB();
});

watch(() => formContacto.alerta_clinica_pregunta_a, () => {
    handleChangeAlertaClinicaPreguntaA();
});

watch(() => formContacto.alerta_clinica_pregunta_c, () => {
    handleChangeAlertaClinicaPreguntaC();
});

watch(() => formContacto.alerta_clinica_pregunta_e, () => {
    handleChangeAlertaClinicaPreguntaE();
});

watch(() => formContacto.pedido_ayuda_pregunta_a, () => {
    handleChangePedidoAyudaPreguntaA();
});

watch(() => formContacto.pedido_ayuda_pregunta_c, () => {
    handleChangePedidoAyudaPreguntaC();
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
                <div class="col-12 col-lg-6 mb-sm-3">
                    <div class="row">
                        <label for="contacto_resultado" class="col-12 col-sm-4 col-form-label">
                            Resultado del contacto
                        </label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <select
                                name="contacto_resultado"
                                id="contacto_resultado"
                                class="form-select"
                                :class="{ 'is-invalid': formContacto.errors?.contactado }"
                                v-model="formContacto.contactado"
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
            </div>
        </div>
        <div class="col-12">
            <div class="row">
                <div class="col-6 col-sm-12 col-lg-6 mb-sm-3">
                    <div class="row">
                        <label for="contacto_fecha" class="col-12 col-sm-4 col-form-label">
                            Fecha de contacto
                        </label>
                        <div class="col-12 col-sm-4 col-md-3 col-lg-6 col-xl-4">
                            <input
                                type="text"
                                inputmode="numeric"
                                name="contacto_fecha"
                                id="contacto_fecha"
                                class="form-control"
                                :class="{ 'is-invalid': formContacto.errors?.fecha }"
                                v-model="formContacto.fecha"
                                v-maska="'##/##/####'"
                                data-maska-eager
                            />
                            <div class="invalid-feedback" v-if="formContacto.errors?.fecha">
                                {{ formContacto.errors.fecha }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-12 col-lg-6 mb-sm-3">
                    <div class="row">
                        <label for="contacto_hora" class="col-12 col-sm-4 col-form-label">
                            Hora de contacto
                        </label>
                        <div class="col-12 col-sm-3 col-md-2 col-lg-4 col-xl-3">
                            <input
                                type="text"
                                inputmode="numeric"
                                name="contacto_hora"
                                id="contacto_hora"
                                class="form-control"
                                :class="{ 'is-invalid': formContacto.errors?.hora }"
                                v-model="formContacto.hora"
                                v-maska="'##:##'"
                                data-maska-eager
                            />
                            <div class="invalid-feedback" v-if="formContacto.errors?.hora">
                                {{ formContacto.errors.hora }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="row">
                <div class="col-12 col-lg-6 mb-sm-3">
                    <div class="row">
                        <div class="col-12 mb-sm-3">
                            <div class="row">
                                <label for="contacto_modalidad" class="col-12 col-sm-4 col-form-label">
                                    Medio de contacto
                                </label>
                                <div class="col-12 col-sm-8 col-xl-7">
                                    <select
                                        name="contacto_modalidad"
                                        id="contacto_modalidad"
                                        class="form-select"
                                        :class="{ 'is-invalid': formContacto.errors?.modalidad }"
                                        v-model="formContacto.modalidad"
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
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="row">
                <div class="col-12 col-lg-8 mb-sm-3">
                    <div class="row">
                        <div class="col-12">
                            <div class="row">
                                <label for="contacto_motivo" class="col-12 col-sm-4 col-lg-3 col-form-label">
                                    Motivo de contacto
                                </label>
                                <div class="col-12 col-sm-8 col-xl-7">
                                    <select
                                        name="contacto_motivo"
                                        id="contacto_motivo"
                                        class="form-select"
                                        :class="{ 'is-invalid': formContacto.errors?.motivo }"
                                        v-model="formContacto.motivo"
                                        :disabled="alerta"
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
            </div>
        </div>
        <template v-if="formContacto.contactado === 1">
            <div
                v-if="formContacto.motivo === 'Agendar cita de entrenamiento' || formContacto.motivo === 'Agendar cita final'"
                class="col-12"
            >
                <div class="row">
                    <div class="col-12 col-lg-6 mb-sm-3">
                        <div class="row">
                            <label for="contacto_fecha_cita" class="col-12 col-sm-4 col-form-label">
                                {{ fechaProgramadaCitaTitle }}
                            </label>
                            <div class="col-6 col-sm-4 col-md-3 col-lg-6 col-xl-4">
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    name="contacto_fecha_cita"
                                    id="contacto_fecha_cita"
                                    class="form-control"
                                    :class="{ 'is-invalid': formContacto.errors?.fecha_cita }"
                                    v-model="formContacto.fecha_cita"
                                    v-maska="'##/##/####'"
                                    data-maska-eager
                                />
                                <div class="invalid-feedback" v-if="formContacto.errors?.fecha_cita">
                                    {{ formContacto.errors.fecha_cita }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6 mb-sm-3">
                        <div class="row">
                            <label for="contacto_lugar_cita" class="col-12 col-sm-4 col-lg-4 col-form-label">
                                {{ lugarAcordadoCitaTitle }}
                            </label>
                            <div class="col-12 col-sm-8 col-lg-8 col-xl-6">
                                <select
                                    name="contacto_lugar_cita"
                                    id="contacto_lugar_cita"
                                    class="form-select"
                                    :class="{ 'is-invalid': formContacto.errors?.lugar_cita }"
                                    v-model="formContacto.lugar_cita"
                                >
                                    <option value="">Seleccionar lugar</option>
                                    <option value="Centro de salud">Centro de salud</option>
                                    <option value="Vivienda del participante">Vivienda del participante</option>
                                    <option value="Otro">Otro</option>
                                </select>
                                <div class="invalid-feedback" v-if="formContacto.errors?.lugar_cita">
                                    {{ formContacto.errors.lugar_cita }}
                                </div>
                            </div>
                            <div
                                v-if="formContacto.lugar_cita === 'Otro'"
                                class="col-12 col-sm-8 col-lg-5 col-xl-4 offset-sm-4 offset-lg-0 mt-2 mt-lg-0"
                            >
                                <input
                                    type="text"
                                    name="contacto_lugar_cita_otro"
                                    id="contacto_lugar_cita_otro"
                                    class="form-control"
                                    placeholder="Escriba el lugar de la cita"
                                    :class="{ 'is-invalid': formContacto.errors?.lugar_cita_otro }"
                                    v-model="formContacto.lugar_cita_otro"
                                />
                                <div class="invalid-feedback" v-if="formContacto.errors?.lugar_cita_otro">
                                    {{ formContacto.errors.lugar_cita_otro }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div
                v-if="formContacto.motivo === 'Cita de entrenamiento' || formContacto.motivo === 'Cita final' || formContacto.motivo === 'Re-entrenamiento'"
                class="col-12"
            >
                <div class="row">
                    <div class="col-6 col-sm-12 col-lg-6 mb-sm-3">
                        <div class="row">
                            <label for="contacto_fecha_cita_final" class="col-12 col-sm-4 col-form-label">
                                Fecha
                            </label>
                            <div class="col-12 col-sm-4 col-md-3 col-lg-6 col-xl-4">
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    name="contacto_fecha_cita_final"
                                    id="contacto_fecha_cita_final"
                                    class="form-control"
                                    :class="{ 'is-invalid': formContacto.errors?.fecha_cita_final }"
                                    v-model="formContacto.fecha_cita_final"
                                    v-maska="'##/##/####'"
                                    data-maska-eager
                                />
                                <div class="invalid-feedback" v-if="formContacto.errors?.fecha_cita_final">
                                    {{ formContacto.errors.fecha_cita_final }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-12 col-lg-6 mb-sm-3">
                        <div class="row">
                            <label for="contacto_hora_cita_final" class="col-12 col-sm-4 col-form-label">
                                Hora de inicio
                            </label>
                            <div class="col-12 col-sm-3 col-md-2 col-lg-4 col-xl-3">
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    name="contacto_hora_cita_final"
                                    id="contacto_hora_cita_final"
                                    class="form-control"
                                    :class="{ 'is-invalid': formContacto.errors?.hora_cita_final }"
                                    v-model="formContacto.hora_cita_final"
                                    v-maska="'##:##'"
                                    data-maska-eager
                                />
                                <div class="invalid-feedback" v-if="formContacto.errors?.hora_cita_final">
                                    {{ formContacto.errors.hora_cita_final }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-lg-8 mb-sm-3">
                        <div class="row">
                            <label for="contacto_lugar_cita_final" class="col-12 col-sm-4 col-lg-3 col-form-label">
                                Lugar
                            </label>
                            <div class="col-12 col-sm-8 col-lg-6">
                                <select
                                    name="contacto_lugar_cita_final"
                                    id="contacto_lugar_cita_final"
                                    class="form-select"
                                    :class="{ 'is-invalid': formContacto.errors?.lugar_cita_final }"
                                    v-model="formContacto.lugar_cita_final"
                                >
                                    <option value="">Seleccionar lugar</option>
                                    <option value="Centro de salud">Centro de salud</option>
                                    <option value="Vivienda del participante">Vivienda del participante</option>
                                    <option value="Otro">Otro</option>
                                </select>
                                <div class="invalid-feedback" v-if="formContacto.errors?.lugar_cita_final">
                                    {{ formContacto.errors.lugar_cita_final }}
                                </div>
                            </div>
                            <div
                                v-if="formContacto.lugar_cita_final === 'Otro'"
                                class="col-12 col-sm-8 col-lg-6 col-xl-4 offset-sm-4 offset-lg-0 mt-2 mt-lg-0"
                            >
                                <input
                                    type="text"
                                    name="contacto_lugar_cita_final_otro"
                                    id="contacto_lugar_cita_final_otro"
                                    class="form-control"
                                    placeholder="Escriba el lugar de la cita"
                                    :class="{ 'is-invalid': formContacto.errors?.lugar_cita_final_otro }"
                                    v-model="formContacto.lugar_cita_final_otro"
                                />
                                <div class="invalid-feedback" v-if="formContacto.errors?.lugar_cita_final_otro">
                                    {{ formContacto.errors.lugar_cita_final_otro }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div
                v-if="formContacto.motivo === 'Seguimiento 2 semanas luego del entrenamiento' || formContacto.motivo === 'Seguimiento mensual'"
                class="col-12"
            >
                <div class="row">
                    <div class="col-12 py-2">
                        <label for="contacto_seguimiento_pregunta_a" class="form-label me-2">
                            ¿El/la participante tuvo dificultades utilizando el aplicativo desde su último contacto?
                        </label>
                        <select
                            name="contacto_seguimiento_pregunta_a"
                            id="contacto_seguimiento_pregunta_a"
                            class="form-select w-auto d-inline-block"
                            v-model="formContacto.seguimiento_pregunta_a"
                            :class="{ 'is-invalid': formContacto.errors?.seguimiento_pregunta_a }"
                        >
                            <option value=""></option>
                            <option value="Si">Si</option>
                            <option value="No">No</option>
                        </select>
                        <div class="invalid-feedback" v-if="formContacto.errors?.seguimiento_pregunta_a">
                            {{ formContacto.errors.seguimiento_pregunta_a }}
                        </div>
                    </div>
                    <template v-if="formContacto.seguimiento_pregunta_a === 'Si'">
                        <div class="col-12 col-lg-10 col-xl-7 py-2">
                            <label for="contacto_seguimiento_pregunta_b" class="form-label me-2">
                                En caso si, ¿qué dificultades tuvo?
                            </label>
                            <input
                                type="text"
                                name="contacto_seguimiento_pregunta_b"
                                id="contacto_seguimiento_pregunta_b"
                                class="form-control"
                                v-model="formContacto.seguimiento_pregunta_b"
                                :class="{ 'is-invalid': formContacto.errors?.seguimiento_pregunta_b }"
                            >
                            <div class="invalid-feedback" v-if="formContacto.errors?.seguimiento_pregunta_b">
                                {{ formContacto.errors.seguimiento_pregunta_b }}
                            </div>
                        </div>
                        <div class="col-12 col-lg-10 col-xl-7 py-2">
                            <label for="contacto_seguimiento_pregunta_c" class="form-label me-2">
                                ¿El/la participante pudo resolver estas dificultades?
                            </label>
                            <select
                                name="contacto_seguimiento_pregunta_c"
                                id="contacto_seguimiento_pregunta_c"
                                class="form-select w-auto d-inline-block"
                                v-model="formContacto.seguimiento_pregunta_c"
                                :class="{ 'is-invalid': formContacto.errors?.seguimiento_pregunta_c }"
                            >
                                <option value=""></option>
                                <option value="Si">Si</option>
                                <option value="No">No</option>
                            </select>
                            <div class="invalid-feedback" v-if="formContacto.errors?.seguimiento_pregunta_c">
                                {{ formContacto.errors.seguimiento_pregunta_c }}
                            </div>
                        </div>
                        <template v-if="formContacto.seguimiento_pregunta_c === 'Si'">
                            <div class="col-12 col-lg-10 col-xl-7 py-2">
                                <label for="contacto_seguimiento_pregunta_d" class="form-label me-2">
                                    En caso si, ¿cómo resolvió estas dificultades?
                                </label>
                                <input
                                    type="text"
                                    name="contacto_seguimiento_pregunta_d"
                                    id="contacto_seguimiento_pregunta_d"
                                    class="form-control"
                                    v-model="formContacto.seguimiento_pregunta_d"
                                    :class="{ 'is-invalid': formContacto.errors?.seguimiento_pregunta_d }"
                                >
                                <div class="invalid-feedback" v-if="formContacto.errors?.seguimiento_pregunta_d">
                                    {{ formContacto.errors.seguimiento_pregunta_d }}
                                </div>
                            </div>
                        </template>
                        <template v-if="formContacto.seguimiento_pregunta_c === 'No'">
                            <div class="col-12 col-lg-10 col-xl-7 py-2">
                                <label for="contacto_seguimiento_pregunta_e" class="form-label me-2">
                                    ¿Pudiste ayudar al participante a resolver estas dificultades durante la llamada/visita?
                                </label>
                                <select
                                    name="contacto_seguimiento_pregunta_e"
                                    id="contacto_seguimiento_pregunta_e"
                                    class="form-select w-auto d-inline-block"
                                    v-model="formContacto.seguimiento_pregunta_e"
                                    :class="{ 'is-invalid': formContacto.errors?.seguimiento_pregunta_e }"
                                >
                                    <option value=""></option>
                                    <option value="Si">Si</option>
                                    <option value="No">No</option>
                                </select>
                                <div class="invalid-feedback" v-if="formContacto.errors?.seguimiento_pregunta_e">
                                    {{ formContacto.errors.seguimiento_pregunta_e }}
                                </div>
                            </div>
                            <template v-if="formContacto.seguimiento_pregunta_e === 'No'">
                                <div class="col-12 col-lg-10 col-xl-7 py-2">
                                    <label for="contacto_seguimiento_pregunta_f" class="form-label me-2">
                                        Agenda una visita domiciliaria o un re-entrenamiento
                                    </label>
                                    <input
                                        type="text"
                                        inputmode="numeric"
                                        name="contacto_seguimiento_pregunta_f"
                                        id="contacto_seguimiento_pregunta_f"
                                        class="form-control w-auto d-inline-block"
                                        v-model="formContacto.seguimiento_pregunta_f"
                                        :class="{ 'is-invalid': formContacto.errors?.seguimiento_pregunta_f }"
                                        v-maska="'##/##/####'"
                                        data-maska-eager
                                    />
                                    <div class="invalid-feedback" v-if="formContacto.errors?.seguimiento_pregunta_f">
                                        {{ formContacto.errors.seguimiento_pregunta_f }}
                                    </div>
                                </div>
                            </template>
                        </template>
                    </template>
                </div>
            </div>
            <div
                v-if="formContacto.motivo === 'Contacto iniciado por el participante'"
                class="col-12"
            >
                <div class="row">
                    <div class="col-12 col-lg-12 mb-sm-3">
                        <div class="row">
                            <label for="contacto_razon_contacto_participante" class="col-12 col-sm-4 col-lg-2 col-form-label">
                                Razón de contacto del participante
                            </label>
                            <div class="col-12 col-sm-8 col-lg-8 col-xl-6">
                                <select
                                    name="contacto_razon_contacto_participante"
                                    id="contacto_razon_contacto_participante"
                                    class="form-select"
                                    v-model="formContacto.razon_contacto_participante"
                                    :class="{ 'is-invalid': formContacto.errors?.razon_contacto_participante }"
                                >
                                    <option value=""></option>
                                    <option value="Problemas con el celular">Problemas con el celular</option>
                                    <option value="Problemas de conexión">Problemas de conexión</option>
                                    <option value="Problemas con el aplicativo">Problemas con el aplicativo</option>
                                    <option value="Problemas relacionados a su atención en el centro de salud">Problemas relacionados a su atención en el centro de salud</option>
                                    <option value="Otro">Otro</option>
                                </select>
                                <div class="invalid-feedback" v-if="formContacto.errors?.razon_contacto_participante">
                                    {{ formContacto.errors.razon_contacto_participante }}
                                </div>
                            </div>
                            <div
                                v-if="formContacto.razon_contacto_participante === 'Otro'"
                                class="col-12 col-sm-8 col-lg-8 col-xl-6 offset-sm-4 offset-lg-2 mt-2 mt-lg-0"
                            >
                                <input
                                    type="text"
                                    name="contacto_razon_contacto_participante_otro"
                                    id="contacto_razon_contacto_participante_otro"
                                    class="form-control"
                                    placeholder="Escriba el motivo de contacto"
                                    :class="{ 'is-invalid': formContacto.errors?.razon_contacto_participante_otro }"
                                    v-model="formContacto.razon_contacto_participante_otro"
                                />
                                <div class="invalid-feedback" v-if="formContacto.errors?.razon_contacto_participante_otro">
                                    {{ formContacto.errors.razon_contacto_participante_otro }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-12 mb-sm-3">
                        <div class="row">
                            <label for="contacto_contacto_participante_acciones" class="col-12 col-sm-4 col-lg-2 col-form-label">
                                Escribe las acciones realizadas
                            </label>
                            <div class="col-12 col-sm-8 col-lg-10 col-xl-7">
                                <textarea
                                    id="contacto_contacto_participante_acciones"
                                    name="contacto_contacto_participante_acciones"
                                    class="form-control"
                                    :class="{ 'is-invalid': formContacto.errors?.contacto_participante_acciones }"
                                    v-model="formContacto.contacto_participante_acciones"
                                    rows="4"
                                ></textarea>
                                <div class="invalid-feedback" v-if="formContacto.errors?.contacto_participante_acciones">
                                    {{ formContacto.errors.contacto_participante_acciones }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div
                v-if="formContacto.motivo === 'No completa las sesiones'"
                class="col-12"
            >
                <div class="row">
                    <label for="contacto_no_adherencia_pregunta_a" class="col-12 col-sm-4 col-xl-3 col-form-label">
                        ¿Qué dificultades tuvo el/la participante con las sesiones?
                        Selecciona todas las que aplique
                    </label>
                    <div class="col-12 col-sm-8 col-xl-6">
                        <div
                            v-for="dificultad in dificultadesNoAdherencia"
                            :key="dificultad"
                            class="form-check mb-2"
                        >
                            <input
                                class="form-check-input border-primary"
                                type="checkbox"
                                :id="'contacto_no_adherencia_pregunta_a_' + dificultad"
                                :value="dificultad"
                                v-model="formContacto.no_adherencia_pregunta_a"
                            />
                            <label
                                class="form-check-label"
                                :for="'contacto_no_adherencia_pregunta_a_' + dificultad"
                            >
                                {{ dificultad }}
                            </label>
                        </div>
                        <div class="invalid-feedback d-block" v-if="formContacto.errors?.no_adherencia_pregunta_a">
                            {{ formContacto.errors.no_adherencia_pregunta_a }}
                        </div>
                        <input
                            type="text"
                            id="contacto_no_adherencia_otro"
                            name="contacto_no_adherencia_otro"
                            class="form-control"
                            v-model="formContacto.no_adherencia_otro"
                            :class="{ 'is-invalid': formContacto.errors?.no_adherencia_otro }"
                            :disabled="!formContacto.no_adherencia_pregunta_a.includes('Otro')"
                        />
                        <div class="invalid-feedback" v-if="formContacto.errors?.no_adherencia_otro">
                            {{ formContacto.errors.no_adherencia_otro }}
                        </div>
                    </div>
                    <div
                        v-if="formContacto.no_adherencia_pregunta_a.includes('Ya no quiere participar en el proyecto')"
                        class="col-12"
                    >
                        <div class="alert alert-danger mt-2">
                            Informar al supervisor o supervisora que el participante no desea seguir en el proyecto.
                        </div>
                    </div>
                </div>
                <template v-if="!formContacto.no_adherencia_pregunta_a.includes('Ya no quiere participar en el proyecto')">
                    <div class="row">
                        <div class="col-12 py-2">
                            <label for="contacto_no_adherencia_pregunta_b" class="form-label me-2">
                                ¿Pudiste ayudar al participante a resolver estas dificultades durante la llamada/visita?
                            </label>
                            <select
                                name="contacto_no_adherencia_pregunta_b"
                                id="contacto_no_adherencia_pregunta_b"
                                v-model="formContacto.no_adherencia_pregunta_b"
                                class="form-select w-auto d-inline-block"
                                :class="{ 'is-invalid': formContacto.errors?.no_adherencia_pregunta_b }"
                            >
                                <option value=""></option>
                                <option value="Si">Si</option>
                                <option value="No">No</option>
                            </select>
                            <div class="invalid-feedback" v-if="formContacto.errors?.no_adherencia_pregunta_b">
                                {{ formContacto.errors.no_adherencia_pregunta_b }}
                            </div>
                        </div>
                    </div>
                    <div class="row" v-if="formContacto.no_adherencia_pregunta_b === 'No'">
                        <div class="col-12 py-2">
                            <label for="contacto_no_adherencia_pregunta_c" class="form-label me-2">
                                Agenda una visita domiciliaria o un re-entrenamiento
                            </label>
                            <input
                                type="text"
                                inputmode="numeric"
                                name="contacto_no_adherencia_pregunta_c"
                                id="contacto_no_adherencia_pregunta_c"
                                class="form-control w-auto d-inline-block"
                                v-model="formContacto.no_adherencia_pregunta_c"
                                :class="{ 'is-invalid': formContacto.errors?.no_adherencia_pregunta_c }"
                                v-maska="'##/##/####'"
                                data-maska-eager
                            />
                            <div class="invalid-feedback" v-if="formContacto.errors?.no_adherencia_pregunta_c">
                                {{ formContacto.errors.no_adherencia_pregunta_c }}
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <div
                v-if="formContacto.motivo === 'Problemas de conexión'"
                class="col-12"
            >
                <div class="row">
                    <label for="contacto_problemas_conexion_pregunta_a" class="col-12 col-sm-4 col-xl-3 col-form-label">
                        ¿Qué dificultades tuvo el/la participante con las sesiones?
                        Selecciona todas las que aplique
                    </label>
                    <div class="col-12 col-sm-8 col-xl-6">
                        <div
                            v-for="dificultad in dificultadesProblemasConexion"
                            :key="dificultad"
                            class="form-check mb-2"
                        >
                            <input
                                class="form-check-input border-primary"
                                type="checkbox"
                                :id="'contacto_problemas_conexion_pregunta_a' + dificultad"
                                :value="dificultad"
                                v-model="formContacto.problemas_conexion_pregunta_a"
                            />
                            <label
                                class="form-check-label"
                                :for="'contacto_problemas_conexion_pregunta_a' + dificultad"
                            >
                                {{ dificultad }}
                            </label>
                        </div>
                        <div class="invalid-feedback d-block" v-if="formContacto.errors?.problemas_conexion_pregunta_a">
                            {{ formContacto.errors.problemas_conexion_pregunta_a }}
                        </div>
                        <input
                            type="text"
                            id="contacto_problemas_conexion_otro"
                            name="contacto_problemas_conexion_otro"
                            class="form-control"
                            :class="{ 'is-invalid': formContacto.errors?.problemas_conexion_otro }"
                            v-model="formContacto.problemas_conexion_otro"
                            :disabled="!formContacto.problemas_conexion_pregunta_a.includes('Otro')"
                        />
                        <div class="invalid-feedback" v-if="formContacto.errors?.problemas_conexion_otro">
                            {{ formContacto.errors.problemas_conexion_otro }}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 py-2">
                        <label for="contacto_problemas_conexion_pregunta_b" class="form-label me-2">
                            ¿Pudiste ayudar al participante a resolver estas dificultades durante la llamada/visita?
                        </label>
                        <select
                            name="contacto_problemas_conexion_pregunta_b"
                            id="contacto_problemas_conexion_pregunta_b"
                            v-model="formContacto.problemas_conexion_pregunta_b"
                            class="form-select w-auto d-inline-block"
                            :class="{ 'is-invalid': formContacto.errors?.problemas_conexion_pregunta_b }"
                        >
                            <option value=""></option>
                            <option value="Si">Si</option>
                            <option value="No">No</option>
                        </select>
                        <div class="invalid-feedback" v-if="formContacto.errors?.problemas_conexion_pregunta_b">
                            {{ formContacto.errors.problemas_conexion_pregunta_b }}
                        </div>
                    </div>
                </div>
                <div class="row" v-if="formContacto.problemas_conexion_pregunta_b === 'No'">
                    <div class="col-12 py-2">
                        <label for="contacto_problemas_conexion_pregunta_c" class="form-label me-2">
                            Agenda una visita domiciliaria o un re-entrenamiento
                        </label>
                        <input
                            type="text"
                            inputmode="numeric"
                            name="contacto_problemas_conexion_pregunta_c"
                            id="contacto_problemas_conexion_pregunta_c"
                            class="form-control w-auto d-inline-block"
                            :class="{ 'is-invalid': formContacto.errors?.problemas_conexion_pregunta_c }"
                            v-model="formContacto.problemas_conexion_pregunta_c"
                            v-maska="'##/##/####'"
                            data-maska-eager
                        />
                        <div class="invalid-feedback" v-if="formContacto.errors?.problemas_conexion_pregunta_c">
                            {{ formContacto.errors.problemas_conexion_pregunta_c }}
                        </div>
                    </div>
                </div>
            </div>
            <div
                v-if="formContacto.motivo === 'Alerta clínica'"
                class="col-12"
            >
                <div class="row">
                    <div class="col-12 py-2">
                        <label for="contacto_alerta_clinica_pregunta_a" class="form-label me-2">
                            ¿El/la participante vio la recomendación de buscar atención en el aplicativo?
                        </label>
                        <select
                            name="contacto_alerta_clinica_pregunta_a"
                            id="contacto_alerta_clinica_pregunta_a"
                            v-model="formContacto.alerta_clinica_pregunta_a"
                            class="form-select w-auto d-inline-block"
                            :class="{ 'is-invalid': formContacto.errors?.alerta_clinica_pregunta_a }"
                        >
                            <option value=""></option>
                            <option value="Si">Si</option>
                            <option value="No">No</option>
                        </select>
                        <div class="invalid-feedback" v-if="formContacto.errors?.alerta_clinica_pregunta_a">
                            {{ formContacto.errors.alerta_clinica_pregunta_a }}
                        </div>
                    </div>
                    <template v-if="formContacto.alerta_clinica_pregunta_a === 'No'">
                        <div class="col-12">
                            <div class="alert alert-danger mt-2">
                                Recomienda al participante que busque atención en su centro de salud.
                            </div>
                        </div>
                        <div class="col-12 py-2">
                            <label for="contacto_alerta_clinica_pregunta_b" class="form-label me-2">
                                ¿Recomendaste al participante que busque atención?
                            </label>
                            <select
                                name="contacto_alerta_clinica_pregunta_b"
                                id="contacto_alerta_clinica_pregunta_b"
                                v-model="formContacto.alerta_clinica_pregunta_b"
                                class="form-select w-auto d-inline-block"
                                :class="{ 'is-invalid': formContacto.errors?.alerta_clinica_pregunta_b }"
                            >
                                <option value=""></option>
                                <option value="Si">Si</option>
                                <option value="No">No</option>
                            </select>
                            <div class="invalid-feedback" v-if="formContacto.errors?.alerta_clinica_pregunta_b">
                                {{ formContacto.errors.alerta_clinica_pregunta_b }}
                            </div>
                        </div>
                        <div
                            v-if="formContacto.alerta_clinica_pregunta_b === 'No'"
                            class="col-12 py-2 text-danger"
                        >
                            Indique las razones en el campo "Observaciones"
                        </div>
                    </template>
                    <template v-if="formContacto.alerta_clinica_pregunta_a === 'Si'">
                        <div class="col-12 py-2">
                            <label for="contacto_alerta_clinica_pregunta_c" class="form-label me-2">
                                ¿El/la participante siguió la recomendación indicada por el aplicativo?
                            </label>
                            <select
                                name="contacto_alerta_clinica_pregunta_c"
                                id="contacto_alerta_clinica_pregunta_c"
                                v-model="formContacto.alerta_clinica_pregunta_c"
                                class="form-select w-auto d-inline-block"
                                :class="{ 'is-invalid': formContacto.errors?.alerta_clinica_pregunta_c }"
                            >
                                <option value=""></option>
                                <option value="Si">Si</option>
                                <option value="No">No</option>
                            </select>
                            <div class="invalid-feedback" v-if="formContacto.errors?.alerta_clinica_pregunta_c">
                                {{ formContacto.errors.alerta_clinica_pregunta_c }}
                            </div>
                        </div>
                        <template v-if="formContacto.alerta_clinica_pregunta_c === 'Si'">
                            <div class="col-12 py-2">
                                <label for="contacto_alerta_clinica_pregunta_e" class="form-label me-2">
                                    ¿El/la participante pudo acceder a la atención recomendada?
                                </label>
                                <select
                                    name="contacto_alerta_clinica_pregunta_e"
                                    id="contacto_alerta_clinica_pregunta_e"
                                    v-model="formContacto.alerta_clinica_pregunta_e"
                                    class="form-select w-auto d-inline-block"
                                    :class="{ 'is-invalid': formContacto.errors?.alerta_clinica_pregunta_e }"
                                >
                                    <option value=""></option>
                                    <option value="Si">Si</option>
                                    <option value="No">No</option>
                                </select>
                                <div class="invalid-feedback" v-if="formContacto.errors?.alerta_clinica_pregunta_e">
                                    {{ formContacto.errors.alerta_clinica_pregunta_e }}
                                </div>
                            </div>
                            <div
                                v-if="formContacto.alerta_clinica_pregunta_e === 'No'"
                                class="col-12 col-lg-10 col-xl-6 py-2"
                            >
                                <label for="contacto_alerta_clinica_pregunta_f" class="form-label me-2">
                                    ¿Qué dificultades encontró para acceder a la atención recomendada?
                                </label>
                                <input
                                    type="text"
                                    name="contacto_alerta_clinica_pregunta_f"
                                    id="contacto_alerta_clinica_pregunta_f"
                                    class="form-control"
                                    v-model="formContacto.alerta_clinica_pregunta_f"
                                    :class="{ 'is-invalid': formContacto.errors?.alerta_clinica_pregunta_f }"
                                >
                                <div class="invalid-feedback" v-if="formContacto.errors?.alerta_clinica_pregunta_f">
                                    {{ formContacto.errors.alerta_clinica_pregunta_f }}
                                </div>
                            </div>
                        </template>
                        <div
                            v-if="formContacto.alerta_clinica_pregunta_c === 'No'"
                            class="col-12 col-lg-8 col-xl-6 py-2"
                        >
                            <label for="contacto_alerta_clinica_pregunta_d" class="form-label me-2">
                                ¿Por qué motivo(s) el participante no pudo seguir la recomendación del aplicativo?
                            </label>
                            <input
                                type="text"
                                name="contacto_alerta_clinica_pregunta_d"
                                id="contacto_alerta_clinica_pregunta_d"
                                class="form-control"
                                v-model="formContacto.alerta_clinica_pregunta_d"
                                :class="{ 'is-invalid': formContacto.errors?.alerta_clinica_pregunta_d }"
                            >
                            <div class="invalid-feedback" v-if="formContacto.errors?.alerta_clinica_pregunta_d">
                                {{ formContacto.errors.alerta_clinica_pregunta_d }}
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div
                v-if="formContacto.motivo === 'Pedido de ayuda a través del aplicativo'"
                class="col-12"
            >
                <div class="row">
                    <label for="contacto_pedido_ayuda_pregunta_a" class="col-12 col-sm-4 col-xl-3 col-form-label">
                        ¿Qué dificultades tuvo el/la participante?
                        Selecciona todas las que aplique
                    </label>
                    <div class="col-12 col-sm-8 col-xl-6">
                        <div
                            v-for="dificultad in dificultadesPedidoAyuda"
                            :key="dificultad"
                            class="form-check mb-2"
                        >
                            <input
                                class="form-check-input border-primary"
                                type="checkbox"
                                :id="'contacto_pedido_ayuda_pregunta_a' + dificultad"
                                :value="dificultad"
                                v-model="formContacto.pedido_ayuda_pregunta_a"
                            />
                            <label
                                class="form-check-label"
                                :for="'contacto_pedido_ayuda_pregunta_a' + dificultad"
                            >
                                {{ dificultad }}
                            </label>
                        </div>
                        <div class="invalid-feedback d-block" v-if="formContacto.errors?.pedido_ayuda_pregunta_a">
                            {{ formContacto.errors.pedido_ayuda_pregunta_a }}
                        </div>
                        <input
                            type="text"
                            id="contacto_pedido_ayuda_otro"
                            name="contacto_pedido_ayuda_otro"
                            class="form-control"
                            :class="{ 'is-invalid': formContacto.errors?.pedido_ayuda_otro }"
                            v-model="formContacto.pedido_ayuda_otro"
                            :disabled="!formContacto.pedido_ayuda_pregunta_a.includes('Otro')"
                        />
                        <div class="invalid-feedback" v-if="formContacto.errors?.pedido_ayuda_otro">
                            {{ formContacto.errors.pedido_ayuda_otro }}
                        </div>
                    </div>
                    <div
                        v-if="formContacto.pedido_ayuda_pregunta_a.includes('Ya no quiere participar en el proyecto')"
                        class="col-12"
                    >
                        <div class="alert alert-danger mt-2">
                            Informar al supervisor o supervisora que el participante no desea seguir en el proyecto.
                        </div>
                    </div>
                </div>
                <template v-if="!formContacto.pedido_ayuda_pregunta_a.includes('Ya no quiere participar en el proyecto')">
                    <div class="row">
                        <div class="col-12 col-lg-8 col-xl-6 py-2">
                            <label for="contacto_pedido_ayuda_pregunta_b" class="form-label me-2">
                                Describe las dificultades que tuvo el/la participante
                            </label>
                            <input
                                type="text"
                                name="contacto_pedido_ayuda_pregunta_b"
                                id="contacto_pedido_ayuda_pregunta_b"
                                class="form-control"
                                :class="{ 'is-invalid': formContacto.errors?.pedido_ayuda_pregunta_b }"
                                v-model="formContacto.pedido_ayuda_pregunta_b"
                            >
                            <div class="invalid-feedback" v-if="formContacto.errors?.pedido_ayuda_pregunta_b">
                                {{ formContacto.errors.pedido_ayuda_pregunta_b }}
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-lg-10 col-xl-7 py-2">
                            <label for="contacto_pedido_ayuda_pregunta_c" class="form-label me-2">
                                ¿Pudiste ayudar al participante a resolver estas dificultades durante la llamada/visita?
                            </label>
                            <select
                                name="contacto_pedido_ayuda_pregunta_c"
                                id="contacto_pedido_ayuda_pregunta_c"
                                class="form-select w-auto d-inline-block"
                                :class="{ 'is-invalid': formContacto.errors?.pedido_ayuda_pregunta_c }"
                                v-model="formContacto.pedido_ayuda_pregunta_c"
                            >
                                <option value=""></option>
                                <option value="Si">Si</option>
                                <option value="No">No</option>
                            </select>
                            <div class="invalid-feedback" v-if="formContacto.errors?.pedido_ayuda_pregunta_c">
                                {{ formContacto.errors.pedido_ayuda_pregunta_c }}
                            </div>
                        </div>
                    </div>
                    <div class="row" v-if="formContacto.pedido_ayuda_pregunta_c === 'No'">
                        <div class="col-12 col-lg-10 col-xl-7 py-2">
                            <label for="contacto_pedido_ayuda_pregunta_d" class="form-label me-2">
                                Agenda una visita domiciliaria o un re-entrenamiento
                            </label>
                            <input
                                type="text"
                                inputmode="numeric"
                                name="contacto_pedido_ayuda_pregunta_d"
                                id="contacto_pedido_ayuda_pregunta_d"
                                class="form-control w-auto d-inline-block"
                                :class="{ 'is-invalid': formContacto.errors?.pedido_ayuda_pregunta_d }"
                                v-model="formContacto.pedido_ayuda_pregunta_d"
                                v-maska="'##/##/####'"
                                data-maska-eager
                            />
                            <div class="invalid-feedback" v-if="formContacto.errors?.pedido_ayuda_pregunta_d">
                                {{ formContacto.errors.pedido_ayuda_pregunta_d }}
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>
        <template v-if="formContacto.contactado === 0">
            <div class="col-12 col-lg-8 mb-sm-3">
                <div class="row">
                    <label for="contacto_motivo_reprogramacion" class="col-12 col-sm-4 col-lg-3 col-form-label">
                        Motivo de reprogramación
                    </label>
                    <div class="col-12 col-sm-8 col-xl-7">
                        <select
                            name="contacto_motivo_reprogramacion"
                            id="contacto_motivo_reprogramacion"
                            class="form-select"
                            :class="{ 'is-invalid': formContacto.errors?.motivo_reprogramacion }"
                            v-model="formContacto.motivo_reprogramacion"
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
            <div class="col-12 col-lg-4 mb-sm-3">
                <div class="row">
                    <label for="contacto_nueva_fecha_contacto" class="col-12 col-sm-4 col-xl-4 col-form-label">
                        Nueva fecha de contacto
                    </label>
                    <div class="col-6 col-sm-4 col-md-3 col-lg-8 col-xl-6">
                        <input
                            type="text"
                            inputmode="numeric"
                            name="contacto_nueva_fecha_contacto"
                            id="contacto_nueva_fecha_contacto"
                            class="form-control"
                            :class="{ 'is-invalid': formContacto.errors?.nueva_fecha_contacto }"
                            v-model="formContacto.nueva_fecha_contacto"
                            v-maska="'##/##/####'"
                            data-maska-eager
                        />
                        <div class="invalid-feedback" v-if="formContacto.errors?.nueva_fecha_contacto">
                            {{ formContacto.errors.nueva_fecha_contacto }}
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <div class="col-12">
            <div class="row">
                <label for="contacto_observaciones" class="col-12 col-sm-4 col-lg-2 col-form-label">
                    Observaciones
                </label>
                <div class="col-12 col-sm-8 col-lg-10 col-xl-6">
                    <textarea
                        id="contacto_observaciones"
                        name="contacto_observaciones"
                        class="form-control"
                        :class="{ 'is-invalid': formContacto.errors?.observaciones }"
                        v-model="formContacto.observaciones"
                        rows="4"
                    ></textarea>
                    <div class="invalid-feedback" v-if="formContacto.errors?.observaciones">
                        {{ formContacto.errors.observaciones }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
