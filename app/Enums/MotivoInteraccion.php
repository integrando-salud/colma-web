<?php

namespace App\Enums;

enum MotivoInteraccion: string
{
    case AGENDAR_CITA_ENTRENAMIENTO = 'Agendar cita de entrenamiento';
    case CITA_ENTRENAMIENTO = 'Cita de entrenamiento';
    case SEGUIMIENTO_DOS_SEMANAS = 'Seguimiento 2 semanas luego del entrenamiento';
    case SEGUIMIENTO_MENSUAL = 'Seguimiento mensual';
    case AGENDAR_CITA_FINAL = 'Agendar cita final';
    case CITA_FINAL = 'Cita final';
    case REENTRENAMIENTO = 'Re-entrenamiento';
    case CONTACTO_INICIADO_POR_PARTICIPANTE = 'Contacto iniciado por el participante';
    case NO_COMPLETA_SESIONES = 'No completa las sesiones';
    case PROBLEMAS_CONEXION = 'Problemas de conexión';
    case ALERTA_CLINICA = 'Alerta clínica';
    case PEDIDO_AYUDA = 'Pedido de ayuda';

    public function esCitaRealizada(): bool
    {
        return in_array($this, [self::CITA_ENTRENAMIENTO, self::CITA_FINAL, self::REENTRENAMIENTO], true);
    }

    public function etiquetaCorta(): string
    {
        return match ($this) {
            self::AGENDAR_CITA_ENTRENAMIENTO, self::CITA_ENTRENAMIENTO => 'Entrenamiento',
            self::SEGUIMIENTO_DOS_SEMANAS => 'Seguimiento 2 semanas',
            default => $this->value,
        };
    }

    /**
     * Texto de la etapa que corresponde realizar después de esta interacción
     * dentro del circuito de entrenamiento.
     */
    public function siguienteMotivoContacto(
        int $cantidadSeguimientosMensuales = 0,
        int $maximoSeguimientosMensuales = 5,
    ): ?string
    {
        return match ($this) {
            self::AGENDAR_CITA_ENTRENAMIENTO => self::CITA_ENTRENAMIENTO->value,
            self::CITA_ENTRENAMIENTO => self::SEGUIMIENTO_DOS_SEMANAS->value,
            self::SEGUIMIENTO_DOS_SEMANAS => 'Seguimiento mensual 1',
            self::SEGUIMIENTO_MENSUAL => $cantidadSeguimientosMensuales < $maximoSeguimientosMensuales
                ? 'Seguimiento mensual '.($cantidadSeguimientosMensuales + 1)
                : self::AGENDAR_CITA_FINAL->value,
            self::AGENDAR_CITA_FINAL => self::CITA_FINAL->value,
            default => null,
        };
    }
}
