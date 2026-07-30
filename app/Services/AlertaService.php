<?php

namespace App\Services;

use App\Enums\MotivoInteraccion;
use App\Models\Alerta;
use App\Models\EstadoAnimo;
use App\Models\Glucosa;
use App\Models\PresionArterial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AlertaService
{
    public function queryGetAlertas(array $filters = [])
    {
        $query = Alerta::query();

        if (! empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (! empty($filters['paciente_id'])) {
            $query->where('paciente_id', $filters['paciente_id']);
        }

        if (isset($filters['revisado'])) {
            if ($filters['revisado']) {
                $query->whereNotNull('revisado');
            } else {
                $query->whereNull('revisado');
            }
        }

        $orderBy = data_get($filters, 'orderBy', 'id');
        $orderDir = data_get($filters, 'orderDir', 'desc');

        return $query->orderBy($orderBy, $orderDir);
    }

    public function countAlertasPendientes(int $idPaciente)
    {
        return $this->queryGetAlertas([
            'paciente_id' => $idPaciente,
            'revisado' => false,
        ])->count();
    }

    /**
     * Precarga las entidades clínicas asociadas a las alertas.
     *
     * Se consulta una vez por tipo de entidad, en lugar de una vez por alerta.
     */
    public function cargarEntidades(Collection $alertas): void
    {
        $idsPorTipo = $alertas
            ->filter(fn (Alerta $alerta) => $alerta->entidad_tipo !== null && $alerta->entidad_id !== null)
            ->groupBy('entidad_tipo')
            ->map(fn (Collection $alertasTipo) => $alertasTipo->pluck('entidad_id')->unique()->values());

        $clasesPorTipo = [
            'glucosa' => Glucosa::class,
            'presion_arterial' => PresionArterial::class,
            'estado_animo' => EstadoAnimo::class,
        ];

        $entidadesPorTipo = [];
        foreach ($clasesPorTipo as $tipo => $clase) {
            if (! $idsPorTipo->has($tipo)) {
                continue;
            }

            $entidadesPorTipo[$tipo] = $clase::query()
                ->whereKey($idsPorTipo->get($tipo))
                ->get()
                ->keyBy('id');
        }

        foreach ($alertas as $alerta) {
            $entidad = $entidadesPorTipo[$alerta->entidad_tipo][$alerta->entidad_id] ?? null;

            $alerta->setRelation(
                'entidad',
                $entidad && (int) $entidad->paciente_id === (int) $alerta->paciente_id ? $entidad : null
            );
        }
    }

    public function getEntidad(Alerta $alerta): ?Model
    {
        if ($alerta->relationLoaded('entidad')) {
            return $alerta->getRelation('entidad');
        }

        $query = match ($alerta->entidad_tipo) {
            'glucosa' => Glucosa::query(),
            'presion_arterial' => PresionArterial::query(),
            'estado_animo' => EstadoAnimo::query(),
            default => null,
        };

        return $query?->where('paciente_id', $alerta->paciente_id)
            ->find($alerta->entidad_id);
    }

    public function savePedidoAyuda(int $idPaciente)
    {
        return Alerta::create([
            'paciente_id' => $idPaciente,
            'tipo' => 'operativa',
            'descripcion' => MotivoInteraccion::PEDIDO_AYUDA->value,
            'fecha' => now()->format('Y-m-d'),
            'fecha_contacto' => now()->addDays(3)->format('Y-m-d'),
            'recomendacion' => "Contactar y explorar motivo del pedido de ayuda. Intentar dar solución.\nCompletar formulario de alerta operativa.",
        ]);
    }
}
