export const FORM_CONTACTO = {
    id: null,
    paciente_id: null,
    alerta_id: null,
    contactado: '',
    fecha: null,
    hora: null,
    hora_display: null,
    hora_meridiano: 'AM',
    modalidad: '',
    motivo: '',
    observaciones: null,
    motivo_reprogramacion: '',
    fecha_cita: null,
    lugar_cita: '',
    hora_cita: null,
    hora_cita_display: null,
    hora_cita_meridiano: 'AM',
    lugar_cita_otro: null,
    fecha_cita_final: null,
    hora_cita_final: null,
    hora_cita_final_display: null,
    hora_cita_final_meridiano: 'AM',
    seguimiento_pregunta_a: '',
    seguimiento_pregunta_b: null,
    seguimiento_pregunta_c: '',
    seguimiento_pregunta_d: null,
    seguimiento_pregunta_e: '',
    fecha_reentrenamiento: null,
    hora_reentrenamiento: null,
    hora_reentrenamiento_meridiano: 'AM',
    lugar_reentrenamiento: null,
    razon_contacto_participante: '',
    razon_contacto_participante_otro: null,
    contacto_participante_acciones: null,
    nueva_fecha_contacto: null,
    no_adherencia_pregunta_a: [],
    no_adherencia_otro: null,
    no_adherencia_pregunta_b: '',
    no_adherencia_pregunta_c: null,
    problemas_conexion_pregunta_a: [],
    problemas_conexion_otro: null,
    problemas_conexion_pregunta_b: '',
    problemas_conexion_pregunta_c: null,
    alerta_clinica_pregunta_a: '',
    alerta_clinica_pregunta_b: '',
    alerta_clinica_pregunta_c: '',
    alerta_clinica_pregunta_d: null,
    alerta_clinica_pregunta_e: '',
    alerta_clinica_pregunta_f: null,
    pedido_ayuda_pregunta_a: [],
    pedido_ayuda_pregunta_b: null,
    pedido_ayuda_pregunta_c: '',
    pedido_ayuda_pregunta_d: null,
    id_entrenamiento: null,
};

export const OPCION_SI = 'Si';

export const OPCION_NO = 'No';

export const OPCION_OTRO = 'Otro';

export const MOTIVO_CONTACTO = {
    AGENDAR_CITA_ENTRENAMIENTO: 'Agendar cita de entrenamiento',
    CITA_ENTRENAMIENTO: 'Cita de entrenamiento',
    SEGUIMIENTO_DOS_SEMANAS: 'Seguimiento 2 semanas luego del entrenamiento',
    SEGUIMIENTO_MENSUAL: 'Seguimiento mensual',
    AGENDAR_CITA_FINAL: 'Agendar cita final',
    CITA_FINAL: 'Cita final',
    REENTRENAMIENTO: 'Re-entrenamiento',
    INICIADO_POR_PACIENTE: 'Contacto iniciado por el participante',
    ALERTA_CLINICA: 'Alerta clínica',
    NO_COMPLETA_SESIONES: 'No completa las sesiones',
    PROBLEMAS_CONEXION: 'Problemas de conexión',
    PEDIDO_AYUDA: 'Pedido de ayuda',
    CALLS: 'calls',
};

export const MOTIVO_CONTACTO_ALERTA_CLINICA = [
    MOTIVO_CONTACTO.ALERTA_CLINICA,
];

export const MOTIVOS_CONTACTO_ALERTA_OPERATIVA = [
    MOTIVO_CONTACTO.NO_COMPLETA_SESIONES,
    MOTIVO_CONTACTO.PROBLEMAS_CONEXION,
    MOTIVO_CONTACTO.PEDIDO_AYUDA,
];

export const MOTIVOS_CONTACTO_CALLS = [
    MOTIVO_CONTACTO.SEGUIMIENTO_DOS_SEMANAS,
    MOTIVO_CONTACTO.SEGUIMIENTO_MENSUAL,
];


export const MOTIVOS_CONTACTO_SIN_ALERTA = [
    MOTIVO_CONTACTO.AGENDAR_CITA_ENTRENAMIENTO,
    MOTIVO_CONTACTO.CITA_ENTRENAMIENTO,
    MOTIVO_CONTACTO.SEGUIMIENTO_DOS_SEMANAS,
    MOTIVO_CONTACTO.SEGUIMIENTO_MENSUAL,
    MOTIVO_CONTACTO.AGENDAR_CITA_FINAL,
    MOTIVO_CONTACTO.CITA_FINAL,
    MOTIVO_CONTACTO.REENTRENAMIENTO,
    MOTIVO_CONTACTO.INICIADO_POR_PACIENTE,
];

export const TIPO_ALERTA = {
    CLINICA: 'clinica',
    OPERATIVA: 'operativa',
    CALLS: 'calls',
}

export const ALERTA_DESCRIPCION = {
    PROBLEMAS_CONEXION: 'Problemas de conexión',
    NO_SE_CONECTA: 'No se conecta',
    PEDIDO_AYUDA: 'Pedido de ayuda',
};

export const MODALIDAD = {
    TELEFONO: 'Llamada por teléfono / WhatsApp',
    PRESENCIAL: 'Encuentro presencial / en persona',
};

export const MOTIVO_REPROGRAMACION_MODALIDAD_TELEFONO = {
    NO_RESPONDE: 'No responde',
    TELEFONO_APAGADO: 'Teléfono apagado',
    NUMERO_EQUIVOCADO: 'Número equivocado / cambió de número',
    LLAMAR_OTRO_MOMENTO: 'Pidió que se le llame en otro momento',
};

export const MOTIVO_REPROGRAMACION_MODALIDAD_PRESENCIAL = {
    NADIE_EN_DOMICILIO: 'No hay nadie en el domicilio',
    AUSENTE_DOMICILIO: 'No se encuentra en el domicilio',
    MUDANZA: 'Se mudó',
    DOMICILIO_ERRONEO: 'Domicilio equivocado / dirección no existe',
    AUSENTE_CITA: 'No acudió a la cita',
    VISITAR_OTRO_MOMENTO: 'Pidió que se le llame / visite en otro momento',
};

export const DIFICULTADES = {
    PROBLEMAS_INTERNET: 'Problemas con el internet (no tiene Wi-Fi o datos móviles para subir la información de las sesiones)',
    PROBLEMAS_SENIAL: 'Problemas con la señal (completó las sesiones, pero la información no se subió)',
    PROBLEMAS_USO: 'Problemas para usar el celular (ej. no sabe cómo abrir el aplicativo)',
    CELULAR_ROTO: 'El celular no funciona y no puede usar el aplicativo',
    CELULAR_ROBADO: 'Le robaron el celular',
    OTRO: OPCION_OTRO,
    FALTA_INTERES: 'Falta de interés',
    FALTA_TIEMPO: 'Falta de tiempo',
    NO_QUIERE_PARTICIPAR: 'Ya no quiere participar en el proyecto',
    PROBLEMAS_CELULAR: 'Problemas con el celular',
    PROBLEMAS_APP: 'Problemas con el aplicativo',
    PROBLEMAS_CONEXION_INTERNET: 'Problemas con la conexión a internet',
};

export const DIFICULTADES_NO_ADHERENCIA = {
    PROBLEMAS_INTERNET: DIFICULTADES.PROBLEMAS_INTERNET,
    PROBLEMAS_SENIAL: DIFICULTADES.PROBLEMAS_SENIAL,
    PROBLEMAS_USO: DIFICULTADES.PROBLEMAS_USO,
    CELULAR_ROTO: DIFICULTADES.CELULAR_ROTO,
    CELULAR_ROBADO: DIFICULTADES.CELULAR_ROBADO,
    FALTA_INTERES: DIFICULTADES.FALTA_INTERES,
    FALTA_TIEMPO: DIFICULTADES.FALTA_TIEMPO,
    NO_QUIERE_PARTICIPAR: DIFICULTADES.NO_QUIERE_PARTICIPAR,
    OTRO: DIFICULTADES.OTRO,
};

export const DIFICULTADES_PROBLEMAS_CONEXION = {
    PROBLEMAS_INTERNET: DIFICULTADES.PROBLEMAS_INTERNET,
    PROBLEMAS_SENIAL: DIFICULTADES.PROBLEMAS_SENIAL,
    PROBLEMAS_USO: DIFICULTADES.PROBLEMAS_USO,
    CELULAR_ROTO: DIFICULTADES.CELULAR_ROTO,
    CELULAR_ROBADO: DIFICULTADES.CELULAR_ROBADO,
    OTRO: DIFICULTADES.OTRO,
};

export const DIFICULTADES_PEDIDO_AYUDA = {
    PROBLEMAS_CELULAR: DIFICULTADES.PROBLEMAS_CELULAR,
    PROBLEMAS_APP: DIFICULTADES.PROBLEMAS_APP,
    PROBLEMAS_CONEXION_INTERNET: DIFICULTADES.PROBLEMAS_CONEXION_INTERNET,
    NO_QUIERE_PARTICIPAR: DIFICULTADES.NO_QUIERE_PARTICIPAR,
    OTRO: DIFICULTADES.OTRO,
};

export const LUGAR_CITA = {
    CENTRO_SALUD: 'Centro de salud',
    VIVIENDA: 'Vivienda del participante',
    OTRO: OPCION_OTRO,
};

export const RAZON_CONTACTO_PARTICIPANTE = {
    PROBLEMAS_CELULAR: 'Problemas con el celular',
    PROBLEMAS_CONEXION: 'Problemas de conexión',
    PROBLEMAS_APP: 'Problemas con el aplicativo',
    PROBLEMAS_ATENCION: 'Problemas relacionados a su atención en el centro de salud',
    OTRO: OPCION_OTRO,
};
