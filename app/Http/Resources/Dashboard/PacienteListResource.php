<?php

namespace App\Http\Resources\Dashboard;

class PacienteListResource extends PacienteResource
{
    /** El listado no muestra registros clínicos. */
    protected bool $includeDatosClinicos = false;
}
