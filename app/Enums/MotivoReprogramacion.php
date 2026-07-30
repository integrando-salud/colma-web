<?php

namespace App\Enums;

enum MotivoReprogramacion: string
{
    case NO_RESPONDE = 'No responde';
    case TELEFONO_APAGADO = 'Teléfono apagado';
    case NUMERO_EQUIVOCADO = 'Número equivocado / cambió de número';
    case LLAMAR_OTRO_MOMENTO = 'Pidió que se le llame en otro momento';
    case NADIE_EN_DOMICILIO = 'No hay nadie en el domicilio';
    case NO_EN_DOMICILIO = 'No se encuentra en el domicilio';
    case SE_MUDO = 'Se mudó';
    case DOMICILIO_EQUIVOCADO = 'Domicilio equivocado / dirección no existe';
    case NO_ACUDIO_A_CITA = 'No acudió a la cita';
    case LLAMAR_O_VISITAR_OTRO_MOMENTO = 'Pidió que se le llame / visite en otro momento';
}
