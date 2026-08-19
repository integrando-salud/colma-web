import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    FORM_CONTACTO,
    MOTIVO_CONTACTO,
    MOTIVOS_CONTACTO_ALERTA_OPERATIVA,
    MOTIVO_CONTACTO_ALERTA_CLINICA,
    MOTIVOS_CONTACTO_SIN_ALERTA,
    TIPO_ALERTA,
    ALERTA_DESCRIPCION,
    DIFICULTADES_NO_ADHERENCIA,
    DIFICULTADES_PROBLEMAS_CONEXION,
    DIFICULTADES_PEDIDO_AYUDA,
    OPCION_SI,
    OPCION_NO,
    LUGAR_CITA,
    RAZON_CONTACTO_PARTICIPANTE,
} from '@/Constants/contacto';
import { useNotificationStore } from '@/Stores/notification';

const HORA_FIELDS = [
    {
        value: 'hora',
        display: 'hora_display',
        meridiano: 'hora_meridiano',
    },
    {
        value: 'hora_cita',
        display: 'hora_cita_display',
        meridiano: 'hora_cita_meridiano',
    },
    {
        value: 'hora_cita_final',
        display: 'hora_cita_final_display',
        meridiano: 'hora_cita_final_meridiano',
    },
    {
        value: 'hora_reentrenamiento',
        display: 'hora_reentrenamiento_display',
        meridiano: 'hora_reentrenamiento_meridiano',
    },
];

const INTERACCION_DIRECT_FIELDS = [
    'id',
    'contactado',
    'fecha',
    'modalidad',
    'motivo',
    'observaciones',
    'motivo_reprogramacion',
    'fecha_cita',
    'lugar_cita',
    'lugar_cita_otro',
    'fecha_cita_final',
    'seguimiento_pregunta_a',
    'seguimiento_pregunta_b',
    'seguimiento_pregunta_c',
    'seguimiento_pregunta_d',
    'seguimiento_pregunta_e',
    'fecha_reentrenamiento',
    'lugar_reentrenamiento',
    'lugar_reentrenamiento_otro',
    'razon_contacto_participante',
    'razon_contacto_participante_otro',
    'contacto_participante_acciones',
    'nueva_fecha_contacto',
    'no_adherencia_pregunta_a',
    'no_adherencia_otro',
    'no_adherencia_pregunta_b',
    'no_adherencia_pregunta_c',
    'problemas_conexion_pregunta_a',
    'problemas_conexion_otro',
    'problemas_conexion_pregunta_b',
    'problemas_conexion_pregunta_c',
    'alerta_clinica_pregunta_a',
    'alerta_clinica_pregunta_b',
    'alerta_clinica_pregunta_c',
    'alerta_clinica_pregunta_d',
    'alerta_clinica_pregunta_e',
    'alerta_clinica_pregunta_f',
    'pedido_ayuda_pregunta_a',
    'pedido_ayuda_pregunta_b',
    'pedido_ayuda_pregunta_c',
    'pedido_ayuda_pregunta_d',
];

const convertirHora12a24 = (hora12, meridiano) => {
    if (!hora12 || !meridiano) {
        return null;
    }

    const [horasStr, minutos] = hora12.split(':');

    if (!horasStr || !minutos) {
        return null;
    }

    let horas = parseInt(horasStr, 10);
    const minutosNumber = parseInt(minutos, 10);

    if (
        Number.isNaN(horas) ||
        Number.isNaN(minutosNumber) ||
        horas < 1 ||
        horas > 12 ||
        minutosNumber < 0 ||
        minutosNumber > 59
    ) {
        return null;
    }

    if (meridiano === 'AM') {
        if (horas === 12) {
            horas = 0;
        }
    } else if (meridiano === 'PM') {
        if (horas < 12) {
            horas += 12;
        }
    }

    const horas24 = horas.toString().padStart(2, '0');

    return `${horas24}:${minutos}`;
};

const convertirHora24a12 = (hora24) => {
    if (!hora24) {
        return {
            hora12: null,
            meridiano: '',
        };
    }

    const [horasStr, minutos] = hora24.split(':');

    if (!horasStr || !minutos) {
        return {
            hora12: null,
            meridiano: '',
        };
    }

    let horas = parseInt(horasStr, 10);

    if (Number.isNaN(horas)) {
        return {
            hora12: null,
            meridiano: '',
        };
    }

    let meridiano = 'AM';

    if (horas === 0) {
        horas = 12;
        meridiano = 'AM';
    } else if (horas === 12) {
        meridiano = 'PM';
    } else if (horas > 12) {
        horas -= 12;
        meridiano = 'PM';
    }

    const horas12 = horas.toString().padStart(2, '0');

    return {
        hora12: `${horas12}:${minutos}`,
        meridiano,
    };
};

export const useFormInteraccionPaciente = ({ props, entrenamientoActual }) => {
    const notification = useNotificationStore();
    const formContacto = useForm(structuredClone(FORM_CONTACTO));

    const reagendaEntrenamiento = computed(() => {
        return entrenamientoActual.value && formContacto.motivo === MOTIVO_CONTACTO.CITA_ENTRENAMIENTO && formContacto.contactado === 0;
    });

    const motivosContacto = computed(() => {
        if (props.alerta?.id) {
            if (props.alerta.tipo === TIPO_ALERTA.CLINICA) {
                return MOTIVO_CONTACTO_ALERTA_CLINICA;
            }

            return MOTIVOS_CONTACTO_ALERTA_OPERATIVA;
        }

        return MOTIVOS_CONTACTO_SIN_ALERTA;
    });

    const verFilaFechaProximoContacto = computed(() => {
        if (formContacto.motivo === MOTIVO_CONTACTO.AGENDAR_CITA_ENTRENAMIENTO) {
            return false;
        }

        return formContacto.contactado === 0
            || formContacto.motivo === MOTIVO_CONTACTO.SEGUIMIENTO_DOS_SEMANAS
            || formContacto.motivo === MOTIVO_CONTACTO.SEGUIMIENTO_MENSUAL;
    });

    const verFechaHoraContacto = computed(() => {
        return formContacto.motivo !== MOTIVO_CONTACTO.CITA_ENTRENAMIENTO
            && formContacto.motivo !== MOTIVO_CONTACTO.CITA_FINAL
            && formContacto.motivo !== MOTIVO_CONTACTO.REENTRENAMIENTO
            || formContacto.contactado !== 1;
    });

    const fillDataInteraccion = (interaccion) => {
        formContacto.paciente_id = interaccion.paciente.id;
        formContacto.alerta_id = interaccion.alerta?.id ?? null;

        INTERACCION_DIRECT_FIELDS.forEach(field => {
            formContacto[field] = interaccion[field];
        });

        HORA_FIELDS.forEach(({ value, display, meridiano }) => {
            const datosHora = convertirHora24a12(interaccion[value]);

            formContacto[value] = interaccion[value];
            formContacto[display] = datosHora.hora12;
            formContacto[meridiano] = datosHora.meridiano;
        });
    };

    const applyAlertaDefaults = (alerta) => {
        if (!alerta?.id) {
            return;
        }

        formContacto.alerta_id = alerta.id;

        if (alerta.tipo === TIPO_ALERTA.CLINICA) {
            formContacto.motivo = MOTIVO_CONTACTO.ALERTA_CLINICA;
            return;
        }

        if (alerta.tipo !== TIPO_ALERTA.OPERATIVA) {
            return;
        }

        switch (alerta.descripcion) {
            case ALERTA_DESCRIPCION.PROBLEMAS_CONEXION:
                formContacto.motivo = MOTIVO_CONTACTO.PROBLEMAS_CONEXION;
                break;
            case ALERTA_DESCRIPCION.NO_SE_CONECTA:
                formContacto.motivo = MOTIVO_CONTACTO.NO_COMPLETA_SESIONES;
                break;
            case ALERTA_DESCRIPCION.PEDIDO_AYUDA:
                formContacto.motivo = MOTIVO_CONTACTO.PEDIDO_AYUDA;
                break;
        }
    };

    const submit = async () => {
        HORA_FIELDS.forEach(({ value, display, meridiano }) => {
            if (!formContacto[display] || !formContacto[meridiano]) {
                formContacto[value] = null;
                return;
            }

            const hora24 = convertirHora12a24(formContacto[display], formContacto[meridiano]);

            formContacto[value] = hora24;
        });

        if (entrenamientoActual.value) {
            formContacto.id_entrenamiento = entrenamientoActual.value.id;
        }

        formContacto.post(route('interacciones.store'), {
            onError: () => {
                notification.errorNotification('El formulario contiene errores.');
            }
        });
    };

    const registerFormWatchers = ({
        getFechaMasDias,
        isValidDate,
        resetVentanasContacto,
        setVentanasContacto,
    }) => {
        const setFechaProximoContacto = () => {
            if (formContacto.motivo === MOTIVO_CONTACTO.AGENDAR_CITA_ENTRENAMIENTO) {
                formContacto.nueva_fecha_contacto = null;
                return;
            }

            if (esCitaEntrenamientoContactado()) {
                formContacto.nueva_fecha_contacto = formContacto.fecha_cita_final && isValidDate(formContacto.fecha_cita_final)
                    ? getFechaMasDias(14, formContacto.fecha_cita_final)
                    : null;
                return;
            }

            if (reagendaEntrenamiento.value) {
                formContacto.nueva_fecha_contacto = getFechaMasDias(14);
                return;
            }

            if (entrenamientoActual.value === null && formContacto.contactado === 0) {
                formContacto.nueva_fecha_contacto = null;
                return;
            }

            if (
                formContacto.motivo === MOTIVO_CONTACTO.AGENDAR_CITA_ENTRENAMIENTO ||
                formContacto.motivo === MOTIVO_CONTACTO.SEGUIMIENTO_DOS_SEMANAS
            ) {
                formContacto.nueva_fecha_contacto = getFechaMasDias(14);
            } else if (formContacto.motivo === MOTIVO_CONTACTO.SEGUIMIENTO_MENSUAL) {
                formContacto.nueva_fecha_contacto = getFechaMasDias(30);
            } else {
                formContacto.nueva_fecha_contacto = null;
            }
        };

        const esCitaEntrenamientoContactado = () => {
            return formContacto.contactado === 1 &&
                formContacto.motivo === MOTIVO_CONTACTO.CITA_ENTRENAMIENTO;
        };

        const actualizarVentanasContacto = () => {
            if (esCitaEntrenamientoContactado()) {
                if (formContacto.fecha_cita_final && isValidDate(formContacto.fecha_cita_final)) {
                    setVentanasContacto(formContacto.fecha_cita_final);
                } else {
                    resetVentanasContacto();
                }

                return;
            }

            if (reagendaEntrenamiento.value && isValidDate(formContacto.nueva_fecha_contacto)) {
                setVentanasContacto(formContacto.nueva_fecha_contacto);
                return;
            }

            setVentanasContacto(formContacto.nueva_fecha_contacto);
        };

        const handleChangeResultado = () => {
            if (formContacto.contactado !== 0) {
                formContacto.motivo_reprogramacion = '';
            }

            setFechaProximoContacto();

            const esCitaEntrenamiento = formContacto.motivo === MOTIVO_CONTACTO.CITA_ENTRENAMIENTO;

            const debeActualizarVentanasContacto =
                esCitaEntrenamientoContactado() ||
                esCitaEntrenamiento ||
                reagendaEntrenamiento.value;

            if (debeActualizarVentanasContacto) {
                actualizarVentanasContacto();
                return;
            }

            const debeResetearVentanasContacto =
                formContacto.contactado !== 1 &&
                entrenamientoActual.value === null;

            if (debeResetearVentanasContacto) {
                resetVentanasContacto();
            }
        };

        const handleChangeMotivoContacto = () => {
            if (props.alerta?.id) {
                if (formContacto.motivo !== MOTIVO_CONTACTO.NO_COMPLETA_SESIONES) {
                    formContacto.no_adherencia_pregunta_a = [];
                    formContacto.no_adherencia_pregunta_b = '';
                }

                if (formContacto.motivo !== MOTIVO_CONTACTO.PROBLEMAS_CONEXION) {
                    formContacto.problemas_conexion_pregunta_a = [];
                    formContacto.problemas_conexion_pregunta_b = '';
                }

                if (formContacto.motivo !== MOTIVO_CONTACTO.ALERTA_CLINICA) {
                    formContacto.alerta_clinica_pregunta_a = '';
                }

                if (formContacto.motivo !== MOTIVO_CONTACTO.PEDIDO_AYUDA) {
                    formContacto.pedido_ayuda_pregunta_a = [];
                    formContacto.pedido_ayuda_pregunta_b = null;
                }
            } else {
                if (formContacto.motivo !== MOTIVO_CONTACTO.CITA_ENTRENAMIENTO && formContacto.motivo !== MOTIVO_CONTACTO.CITA_FINAL && formContacto.motivo !== MOTIVO_CONTACTO.REENTRENAMIENTO) {
                    formContacto.fecha_cita_final = null;
                    formContacto.hora_cita_final = null;
                }

                if (formContacto.motivo !== MOTIVO_CONTACTO.SEGUIMIENTO_DOS_SEMANAS && formContacto.motivo !== MOTIVO_CONTACTO.SEGUIMIENTO_MENSUAL) {
                    formContacto.seguimiento_pregunta_a = '';
                    formContacto.seguimiento_pregunta_b = null;
                    formContacto.seguimiento_pregunta_c = '';
                    formContacto.seguimiento_pregunta_d = null;
                    formContacto.seguimiento_pregunta_e = '';
                    formContacto.fecha_reentrenamiento = null;
                    formContacto.hora_reentrenamiento = null;
                    formContacto.lugar_reentrenamiento = '';
                    formContacto.lugar_reentrenamiento_otro = null;
                }

                if (formContacto.motivo !== MOTIVO_CONTACTO.INICIADO_POR_PACIENTE) {
                    formContacto.razon_contacto_participante = '';
                    formContacto.razon_contacto_participante_otro = null;
                    formContacto.contacto_participante_acciones = null;
                }
            }

            setFechaProximoContacto();

            if (esCitaEntrenamientoContactado() || reagendaEntrenamiento.value) {
                actualizarVentanasContacto();
                return;
            }

            if (entrenamientoActual.value && !reagendaEntrenamiento.value) {
                resetVentanasContacto();
            }
        };

        const fieldWatchers = {
            modalidad: () => {
                formContacto.motivo_reprogramacion = '';
            },
            seguimiento_pregunta_a: () => {
                if (formContacto.seguimiento_pregunta_a !== OPCION_SI) {
                    formContacto.seguimiento_pregunta_b = null;
                    formContacto.seguimiento_pregunta_c = '';
                }
            },
            seguimiento_pregunta_e: () => {
                if (formContacto.seguimiento_pregunta_e !== OPCION_NO) {
                    formContacto.fecha_reentrenamiento = null;
                    formContacto.hora_reentrenamiento = null;
                    formContacto.lugar_reentrenamiento = '';
                    formContacto.lugar_reentrenamiento_otro = null;
                }
            },
            lugar_cita: () => {
                if (formContacto.lugar_cita !== LUGAR_CITA.OTRO) {
                    formContacto.lugar_cita_otro = null;
                }
            },
            lugar_reentrenamiento: () => {
                if (formContacto.lugar_reentrenamiento !== LUGAR_CITA.OTRO) {
                    formContacto.lugar_reentrenamiento_otro = null;
                }
            },
            fecha_cita_final: () => {
                if (esCitaEntrenamientoContactado()) {
                    setFechaProximoContacto();
                    actualizarVentanasContacto();
                }
            },
            razon_contacto_participante: () => {
                if (formContacto.razon_contacto_participante !== RAZON_CONTACTO_PARTICIPANTE.OTRO) {
                    formContacto.razon_contacto_participante_otro = null;
                    formContacto.contacto_participante_acciones = null;
                }
            },
            no_adherencia_pregunta_a: () => {
                if (!formContacto.no_adherencia_pregunta_a.includes(DIFICULTADES_NO_ADHERENCIA.OTRO)) {
                    formContacto.no_adherencia_otro = null;
                }

                if (!formContacto.no_adherencia_pregunta_a.includes(DIFICULTADES_NO_ADHERENCIA.NO_QUIERE_PARTICIPAR)) {
                    formContacto.no_adherencia_pregunta_b = '';
                    formContacto.no_adherencia_pregunta_c = null;
                }
            },
            no_adherencia_pregunta_b: () => {
                if (formContacto.no_adherencia_pregunta_b !== OPCION_NO) {
                    formContacto.no_adherencia_pregunta_c = null;
                }
            },
            problemas_conexion_pregunta_a: () => {
                if (!formContacto.problemas_conexion_pregunta_a.includes(DIFICULTADES_PROBLEMAS_CONEXION.OTRO)) {
                    formContacto.problemas_conexion_otro = null;
                }
            },
            problemas_conexion_pregunta_b: () => {
                if (formContacto.problemas_conexion_pregunta_b !== OPCION_NO) {
                    formContacto.problemas_conexion_pregunta_c = null;
                }
            },
            alerta_clinica_pregunta_a: () => {
                if (formContacto.alerta_clinica_pregunta_a !== OPCION_NO) {
                    formContacto.alerta_clinica_pregunta_b = '';
                }

                if (formContacto.alerta_clinica_pregunta_a !== OPCION_SI) {
                    formContacto.alerta_clinica_pregunta_c = '';
                }
            },
            alerta_clinica_pregunta_c: () => {
                if (formContacto.alerta_clinica_pregunta_c !== OPCION_NO) {
                    formContacto.alerta_clinica_pregunta_d = null;
                }

                if (formContacto.alerta_clinica_pregunta_c !== OPCION_SI) {
                    formContacto.alerta_clinica_pregunta_e = '';
                    formContacto.alerta_clinica_pregunta_f = null;
                }
            },
            alerta_clinica_pregunta_e: () => {
                if (formContacto.alerta_clinica_pregunta_e !== OPCION_NO) {
                    formContacto.alerta_clinica_pregunta_f = null;
                }
            },
            pedido_ayuda_pregunta_a: () => {
                if (!formContacto.pedido_ayuda_pregunta_a.includes(DIFICULTADES_PEDIDO_AYUDA.NO_QUIERE_PARTICIPAR)) {
                    formContacto.pedido_ayuda_pregunta_b = null;
                    formContacto.pedido_ayuda_pregunta_c = '';
                }
            },
            pedido_ayuda_pregunta_c: () => {
                if (formContacto.pedido_ayuda_pregunta_c !== OPCION_NO) {
                    formContacto.pedido_ayuda_pregunta_d = null;
                }
            },
        };

        watch(() => formContacto.motivo, handleChangeMotivoContacto);

        watch(() => formContacto.contactado, handleChangeResultado);

        Object.entries(fieldWatchers).forEach(([field, handler]) => {
            watch(() => formContacto[field], handler);
        });

        watch(() => formContacto.nueva_fecha_contacto, (newValue) => {
            if (newValue === null) {
                resetVentanasContacto();
                return;
            }

            if (
                esCitaEntrenamientoContactado() && formContacto.fecha_cita_final && isValidDate(formContacto.fecha_cita_final)
                || reagendaEntrenamiento.value && isValidDate(newValue)
            ) {
                actualizarVentanasContacto();
            }
        });
    };

    return {
        formContacto,
        motivosContacto,
        reagendaEntrenamiento,
        submit,
        verFechaHoraContacto,
        verFilaFechaProximoContacto,
        applyAlertaDefaults,
        fillDataInteraccion,
        registerFormWatchers,
    };
};
