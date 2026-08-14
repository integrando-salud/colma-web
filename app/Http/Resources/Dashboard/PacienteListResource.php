<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class PacienteListResource extends PacienteResource
{
    /**
     * Incluye la clave únicamente en la respuesta del listado, que es la fuente
     * de datos del modal "Ver más". Las pantallas de detalle no reciben este
     * dato sensible; el reinicio AJAX usa este Resource para refrescar la fila.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'clave' => $this->clave ? $this->decryptClave() : null,
        ];
    }

    private function decryptClave(): ?string
    {
        try {
            return Crypt::decryptString($this->clave);
        } catch (DecryptException) {
            Log::warning('No se pudo descifrar la clave del paciente para el modal.', [
                'paciente_id' => $this->id,
            ]);

            return null;
        }
    }
}
