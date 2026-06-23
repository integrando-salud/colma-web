import { computed, reactive } from 'vue';
import { MOTIVO_CONTACTO } from '@/Constants/contacto';
import {
    VENTANA_CONTACTO_KEYS,
    VENTANA_CONTACTO_DIAS_SUMA_INICIO,
    VENTANA_CONTACTO_DIAS_SUMA_FIN,
} from '@/Constants/ventanaContacto';

const ENTRENAMIENTO_ACTUAL_FIELDS = {
    seguimientoDosSemanas: {
        inicio: 'seguimiento_dos_semanas_inicio',
        fin: 'seguimiento_dos_semanas_fin',
    },
    llamadaMensual: {
        inicio: 'llamada_mensual_1_inicio',
        fin: 'llamada_mensual_1_fin',
    },
    llamadaMensualDos: {
        inicio: 'llamada_mensual_2_inicio',
        fin: 'llamada_mensual_2_fin',
    },
    llamadaMensualTres: {
        inicio: 'llamada_mensual_3_inicio',
        fin: 'llamada_mensual_3_fin',
    },
    llamadaMensualCuatro: {
        inicio: 'llamada_mensual_4_inicio',
        fin: 'llamada_mensual_4_fin',
    },
    llamadaMensualCinco: {
        inicio: 'llamada_mensual_5_inicio',
        fin: 'llamada_mensual_5_fin',
    },
    citaFinal: {
        inicio: 'agendar_cita_final_inicio',
        fin: 'agendar_cita_final_fin',
    },
};

export const useVentanaContacto = ({ formContacto, entrenamientoActual, reagendaEntrenamiento }) => {
    const verVentanaContacto = computed(() => {
        return !!(formContacto.motivo === MOTIVO_CONTACTO.AGENDAR_CITA_ENTRENAMIENTO && formContacto.contactado || entrenamientoActual.value);
    });

    const ventanaContacto = reactive({
        fechaEntrenamiento: '',
        ...VENTANA_CONTACTO_KEYS.reduce((acc, key) => {
            acc[key] = { inicio: '', fin: '', color: 'text-black' };
            return acc;
        }, {}),
    });

    const parseFechaLocal = (fecha) => {
        if (!fecha) {
            return null;
        }

        const [dia, mes, anio] = fecha.split('/').map(Number);

        if (!dia || !mes || !anio) {
            return null;
        }

        return new Date(anio, mes - 1, dia);
    };

    const inicioDelDia = (fecha) => {
        return new Date(fecha.getFullYear(), fecha.getMonth(), fecha.getDate());
    };

    const esMismaFecha = (a, b) => {
        return a.getFullYear() === b.getFullYear()
            && a.getMonth() === b.getMonth()
            && a.getDate() === b.getDate();
    };

    const colorFecha = (fechaInicio, fechaFin) => {
        const inicio = parseFechaLocal(fechaInicio);
        const fin = parseFechaLocal(fechaFin);

        if (!inicio || !fin) {
            return 'text-black';
        }

        const hoy = inicioDelDia(new Date());

        if (hoy > fin) {
            return 'text-danger';
        }

        if (hoy >= inicio && hoy <= fin) {
            return 'text-success';
        }

        return 'text-black';
    };

    const colorFechaEntrenamiento = computed(() => {
        const hoy = new Date();

        if (!ventanaContacto.fechaEntrenamiento) {
            return 'text-black';
        }

        const [dia, mes, anio] = ventanaContacto.fechaEntrenamiento.split('/').map(Number);
        const fechaEntrenamientoDate = new Date(anio, mes - 1, dia);

        if (esMismaFecha(hoy, fechaEntrenamientoDate)) {
            return 'text-success';
        }

        if (hoy > fechaEntrenamientoDate) {
            return 'text-danger';
        }

        return 'text-black';
    });

    const isValidDate = (dateString) => {
        const regex = /^\d{2}\/\d{2}\/\d{4}$/;
        if (!regex.test(dateString)) return false;

        const [day, month, year] = dateString.split('/').map(Number);
        const date = new Date(year, month - 1, day);

        return (
            date.getFullYear() === year &&
            date.getMonth() === month - 1 &&
            date.getDate() === day
        );
    };

    const getFechaMasDias = (dias, fechaBase = null) => {
        let fecha = new Date();

        if (typeof fechaBase === 'string') {
            const [dia, mes, anio] = fechaBase.split('/').map(Number);
            fecha = new Date(anio, mes - 1, dia);
        }

        fecha.setDate(fecha.getDate() + dias);

        const dia = String(fecha.getDate()).padStart(2, '0');
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const anio = fecha.getFullYear();

        return `${dia}/${mes}/${anio}`;
    };

    const aplicarVentanaEntrenamientoActual = () => {
        if (!entrenamientoActual.value) return;

        ventanaContacto.fechaEntrenamiento = entrenamientoActual.value.fecha;

        Object.entries(ENTRENAMIENTO_ACTUAL_FIELDS).forEach(([key, fields]) => {
            ventanaContacto[key].inicio = entrenamientoActual.value[fields.inicio];
            ventanaContacto[key].fin = entrenamientoActual.value[fields.fin];
            ventanaContacto[key].color = colorFecha(ventanaContacto[key].inicio, ventanaContacto[key].fin);
        });
    };

    const setVentanasContacto = (fechaBase) => {
        if (entrenamientoActual.value) {
            if (!reagendaEntrenamiento.value) {
                aplicarVentanaEntrenamientoActual();
                return;
            }
        }

        ventanaContacto.fechaEntrenamiento = fechaBase;

        VENTANA_CONTACTO_KEYS.forEach(key => {
            ventanaContacto[key].inicio = getFechaMasDias(VENTANA_CONTACTO_DIAS_SUMA_INICIO[key], fechaBase);
            ventanaContacto[key].fin = getFechaMasDias(VENTANA_CONTACTO_DIAS_SUMA_FIN[key], fechaBase);
            ventanaContacto[key].color = colorFecha(ventanaContacto[key].inicio, ventanaContacto[key].fin);
        });
    };

    const resetVentanasContacto = () => {
        if (entrenamientoActual.value) {
            aplicarVentanaEntrenamientoActual();
            return;
        }

        ventanaContacto.fechaEntrenamiento = '';

        VENTANA_CONTACTO_KEYS.forEach(key => {
            ventanaContacto[key].inicio = '';
            ventanaContacto[key].fin = '';
        });
    };

    const fillDataEntrenamientoActual = () => {
        aplicarVentanaEntrenamientoActual();
    };

    return {
        ventanaContacto,
        verVentanaContacto,
        colorFechaEntrenamiento,
        fillDataEntrenamientoActual,
        getFechaMasDias,
        isValidDate,
        resetVentanasContacto,
        setVentanasContacto,
    };
};
