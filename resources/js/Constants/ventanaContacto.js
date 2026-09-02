const LLAMADAS_MENSUALES_KEYS = [
    'seguimientoDosSemanas',
    'llamadaMensual',
    'llamadaMensualDos',
    'llamadaMensualTres',
    'llamadaMensualCuatro',
    'llamadaMensualCinco',
];

export const getVentanaContactoKeys = (seguimientosMensuales = 5) => [
    LLAMADAS_MENSUALES_KEYS[0],
    ...LLAMADAS_MENSUALES_KEYS.slice(1, seguimientosMensuales + 1),
    'citaFinal',
];

// Se conserva para los lugares que necesitan el circuito estándar completo.
export const VENTANA_CONTACTO_KEYS = getVentanaContactoKeys();

export const VENTANA_CONTACTO_LABELS = {
    seguimientoDosSemanas: 'Seguimiento 2 semanas luego del entrenamiento',
    llamadaMensual: 'Seguimiento mensual 1',
    llamadaMensualDos: 'Seguimiento mensual 2',
    llamadaMensualTres: 'Seguimiento mensual 3',
    llamadaMensualCuatro: 'Seguimiento mensual 4',
    llamadaMensualCinco: 'Seguimiento mensual 5',
    citaFinal: 'Agendar cita final',
};

export const VENTANA_CONTACTO_DIAS_SUMA_INICIO = {
    seguimientoDosSemanas: 14,
    llamadaMensual: 28,
    llamadaMensualDos: 56,
    llamadaMensualTres: 84,
    llamadaMensualCuatro: 112,
    llamadaMensualCinco: 140,
    citaFinal: 168,
};

export const VENTANA_CONTACTO_DIAS_SUMA_FIN = {
    seguimientoDosSemanas: 21,
    llamadaMensual: 42,
    llamadaMensualDos: 70,
    llamadaMensualTres: 98,
    llamadaMensualCuatro: 126,
    llamadaMensualCinco: 154,
    citaFinal: 182,
};

export const getVentanaContactoDiasSuma = (seguimientosMensuales = 5) => ({
    inicio: {
        ...VENTANA_CONTACTO_DIAS_SUMA_INICIO,
        citaFinal: 28 * (seguimientosMensuales + 1),
    },
    fin: {
        ...VENTANA_CONTACTO_DIAS_SUMA_FIN,
        citaFinal: (28 * (seguimientosMensuales + 1)) + 14,
    },
});
