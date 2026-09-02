<?php

namespace App\Services;

use App\Enums\MotivoInteraccion;
use App\Models\Alerta;
use App\Models\EstadoAnimo;
use App\Models\Glucosa;
use App\Models\Paciente;
use App\Models\PresionArterial;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AlertaService
{
    public const int DIAS_HASTA_CONTACTO_ALERTA_OPERATIVA = 3;

    /**
     * Devuelve las recomendaciones de seguimiento para cada motivo de alerta operativa.
     *
     * @return array<string, string> Recomendaciones indexadas por motivo de interacción.
     */
    public function recomendacionesOperativas(): array
    {
        return [
            MotivoInteraccion::NO_COMPLETA_SESIONES->value => "Contactar y preguntar si es que tiene problemas con el app o el celular o el internet. Intentar dar solución y/o motivarla a seguir usando el app.\nCompletar formulario de alerta operativa.",
            MotivoInteraccion::PROBLEMAS_CONEXION->value => "Contactar y preguntar si es que tiene problemas con el app o el celular o el internet. Intentar dar solución y/o motivarla a seguir usando el app.\nCompletar formulario de alerta operativa.",
            MotivoInteraccion::PEDIDO_AYUDA->value => "Contactar y explorar motivo del pedido de ayuda. Intentar dar solución.\nCompletar formulario de alerta operativa.",
        ];
    }

    /**
     * Calcula la fecha límite sugerida para contactar a un paciente por una alerta operativa.
     *
     * @param  CarbonInterface  $fechaBase  Fecha en la que se generó la alerta.
     */
    public function fechaContactoOperativa(CarbonInterface $fechaBase): CarbonInterface
    {
        return $fechaBase->copy()->addDays(self::DIAS_HASTA_CONTACTO_ALERTA_OPERATIVA);
    }

    /**
     * Obtiene las alertas de un paciente, agrupadas por tipo y con sus entidades precargadas.
     *
     * @return array{clinicas: LengthAwarePaginator, operativas: LengthAwarePaginator}
     */
    public function getAlertasPaciente(Paciente $paciente, bool $pendientes): array
    {
        $sufijoPagina = $pendientes ? '-pendientes' : '';

        $alertasClinicas = $this->getAlertasPacientePorTipo(
            $paciente,
            'clinica',
            $pendientes,
            "page-clinicas$sufijoPagina"
        );
        $alertasOperativas = $this->getAlertasPacientePorTipo(
            $paciente,
            'operativa',
            $pendientes,
            "page-operativas$sufijoPagina"
        );

        $this->cargarEntidades(
            $alertasClinicas->getCollection()->merge($alertasOperativas->getCollection())
        );

        return [
            'clinicas' => $alertasClinicas,
            'operativas' => $alertasOperativas,
        ];
    }

    /**
     * Obtiene y pagina las alertas de un paciente para un tipo y estado de revisión específicos.
     *
     * @param  string  $tipo  Tipo de alerta a consultar: clínica u operativa.
     * @param  bool  $pendientes  Indica si se deben obtener alertas pendientes o ya revisadas.
     * @param  string  $pageName  Nombre del parámetro de paginación para esta consulta.
     */
    private function getAlertasPacientePorTipo(
        Paciente $paciente,
        string $tipo,
        bool $pendientes,
        string $pageName,
    ): LengthAwarePaginator
    {
        $query = $paciente->alertas()
            ->with(['paciente', 'user', 'ultimaInteraccionContactada'])
            ->where('tipo', $tipo)
            ->when(
                $pendientes,
                fn ($query) => $query->whereNull('revisado'),
                fn ($query) => $query->whereNotNull('revisado')
            );

        if ($pendientes) {
            $query->orderByDesc('fecha')->orderByDesc('id');
        }

        $alertas = $query->paginate(config('app.rpp'), ['*'], $pageName);

        if ($pendientes) {
            $alertas->withQueryString();
        }

        return $alertas;
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

    /**
     * Obtiene la entidad clínica asociada a una alerta.
     *
     * Reutiliza la relación ya cargada cuando está disponible y valida que la entidad pertenezca
     * al mismo paciente de la alerta.
     */
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

    /**
     * Registra una alerta operativa cuando un paciente solicita ayuda.
     *
     * Asigna la recomendación correspondiente y la fecha sugerida de contacto.
     */
    public function savePedidoAyuda(int $idPaciente)
    {
        $fecha = now();
        $recomendaciones = $this->recomendacionesOperativas();

        return Alerta::create([
            'paciente_id' => $idPaciente,
            'tipo' => 'operativa',
            'descripcion' => MotivoInteraccion::PEDIDO_AYUDA->value,
            'fecha' => $fecha,
            'fecha_contacto' => $this->fechaContactoOperativa($fecha),
            'recomendacion' => $recomendaciones[MotivoInteraccion::PEDIDO_AYUDA->value],
        ]);
    }
}
