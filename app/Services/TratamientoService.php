<?php

namespace App\Services;

use App\Models\Diagnostico;
use App\Models\Entrenamiento;
use App\Models\Paciente;
use App\Models\TratamientoMultimedia;
use App\Models\TratamientoSesion;
use App\Models\TratamientoVersion;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class TratamientoService
{
    /**
     * Obtiene el catálogo exclusivamente desde la versión del entrenamiento activo.
     *
     * Retorna `null` si el paciente no tiene un entrenamiento activo con una
     * versión persistida. No infiere ni adopta versiones por diagnóstico.
     */
    public function getTratamientoPaciente(Paciente $paciente): ?array
    {
        $entrenamiento = $paciente->entrenamientos()
            ->where('activo', true)
            ->latest()
            ->with('tratamientoVersion')
            ->first();

        if (! $entrenamiento?->tratamiento_version_id || ! $entrenamiento->tratamientoVersion) {
            return null;
        }

        $version = $entrenamiento->tratamientoVersion;
        $nombre = is_array($version->contenido)
            ? ($version->contenido['name'] ?? $version->tipo)
            : $version->tipo;

        return $this->getTratamiento($version->tipo, (string) $nombre, $version);
    }

    /** Obtiene o crea la versión vigente del catálogo asignado al paciente. */
    public function getVersionTratamientoPaciente(Paciente $paciente): ?TratamientoVersion
    {
        $tratamiento = $this->getConfiguracionTratamientoPaciente($paciente);

        if (! $tratamiento) {
            return null;
        }

        [$pais, , $tipo] = $tratamiento;

        return $this->sincronizarCatalogo($pais, $tipo);
    }

    /**
     * Sincroniza el catálogo declarado en content.json.
     *
     * @param array<int, string>|null $paises Códigos de país a importar; `null` importa todos.
     * @param array<int, string>|null $tipos Tipos lógicos de tratamiento a importar; `null` importa todos.
     * @return array<int, array{pais: string, tipo: string, version_id: int, creada: bool}>
     */
    public function sincronizarCatalogos(?array $paises = null, ?array $tipos = null): array
    {
        $catalogo = $this->leerCatalogo();
        $seleccionados = [];
        $rutasUsadas = [];
        foreach ($catalogo['countries'] as $pais => $tratamientos) {
            if ($paises !== null && ! in_array($pais, $paises, true)) {
                continue;
            }
            foreach ($tratamientos as $tratamiento) {
                if (! is_array($tratamiento) || ! isset($tratamiento['id']) || ! is_string($tratamiento['id'])) {
                    throw new RuntimeException("$pais/tratamiento: id debe ser un string no vacío.");
                }
                if ($tipos !== null && ! in_array($tratamiento['id'], $tipos, true)) {
                    continue;
                }
                $this->validarYNormalizarTratamiento($pais, $tratamiento, $rutasUsadas);
                $seleccionados[] = ['pais' => $pais, 'tipo' => $tratamiento['id']];
            }
        }
        $resultado = [];

        foreach ($seleccionados as ['pais' => $pais, 'tipo' => $tipo]) {
                $version = $this->sincronizarCatalogo($pais, $tipo);

                if (! $version) {
                    continue;
                }

                $resultado[] = [
                    'pais' => $pais,
                    'tipo' => $tipo,
                    'version_id' => $version->id,
                    'creada' => $version->wasRecentlyCreated,
                ];
        }

        return $resultado;
    }

    /** Cuenta las sesiones con elementos cuyo progreso está completamente marcado. */
    public function getCantidadSesionesCompletadas(Entrenamiento $entrenamiento): int
    {
        return $this->getSesionesConProgreso($entrenamiento)
            ->filter(fn (TratamientoSesion $sesion) => $this->sesionEstaCompleta($sesion))
            ->count();
    }

    /** Cuenta semanas cuyas sesiones reales con contenido están completas. */
    public function getCantidadSemanasCompletadas(Entrenamiento $entrenamiento): int
    {
        return $this->getSesionesConProgreso($entrenamiento)
            ->groupBy('semana')
            ->filter(fn ($sesiones) => $sesiones->isNotEmpty() && $sesiones->every(
                fn (TratamientoSesion $sesion) => $this->sesionEstaCompleta($sesion)
            ))
            ->count();
    }

    /**
     * Resume el estado derivado del entrenamiento, sin persistir resultados
     * que podrían quedar obsoletos tras una reversión.
     *
     * @return array{sesiones_totales: int, sesiones_completadas: int, semanas_totales: int, semanas_completadas: int}
     */
    public function getResumenProgresoEntrenamiento(Entrenamiento $entrenamiento): array
    {
        $sesiones = $this->getSesionesConProgreso($entrenamiento);
        $sesionesCompletadas = $sesiones->filter(fn (TratamientoSesion $sesion) => $this->sesionEstaCompleta($sesion));

        return [
            'sesiones_totales' => $sesiones->count(),
            'sesiones_completadas' => $sesionesCompletadas->count(),
            'semanas_totales' => $sesiones->pluck('semana')->unique()->count(),
            'semanas_completadas' => $sesiones->groupBy('semana')
                ->filter(fn ($sesionesSemana) => $sesionesSemana->isNotEmpty() && $sesionesSemana->every(
                    fn (TratamientoSesion $sesion) => $this->sesionEstaCompleta($sesion)
                ))
                ->count(),
        ];
    }

    /** @return \Illuminate\Support\Collection<int, TratamientoSesion> */
    private function getSesionesConProgreso(Entrenamiento $entrenamiento)
    {
        if (! $entrenamiento->tratamiento_version_id) {
            return collect();
        }

        if ($entrenamiento->relationLoaded('tratamientoVersion')
            && $entrenamiento->tratamientoVersion?->relationLoaded('sesiones')
            && $entrenamiento->relationLoaded('progresoElementos')) {
            $completados = $entrenamiento->progresoElementos
                ->where('completado', true)
                ->pluck('tratamiento_elemento_id');

            return $entrenamiento->tratamientoVersion->sesiones
                ->filter(fn (TratamientoSesion $sesion) => $sesion->relationLoaded('elementos') && $sesion->elementos->isNotEmpty())
                ->map(function (TratamientoSesion $sesion) use ($completados) {
                    $sesion->setAttribute('elementos_count', $sesion->elementos->count());
                    $sesion->setAttribute('elementos_completados_count', $sesion->elementos->pluck('id')->intersect($completados)->count());

                    return $sesion;
                })->values();
        }

        return TratamientoSesion::query()
            ->where('tratamiento_version_id', $entrenamiento->tratamiento_version_id)
            ->has('elementos')
            ->withCount('elementos')
            ->withCount(['elementos as elementos_completados_count' => fn ($query) => $query->whereHas(
                'progresos',
                fn ($progresos) => $progresos
                    ->where('paciente_id', $entrenamiento->paciente_id)
                    ->where('entrenamiento_id', $entrenamiento->id)
                    ->where('completado', true),
            )])
            ->orderBy('semana')
            ->orderBy('sesion')
            ->get();
    }

    private function sesionEstaCompleta(TratamientoSesion $sesion): bool
    {
        return $sesion->elementos_count > 0
            && $sesion->elementos_count === $sesion->elementos_completados_count;
    }

    /**
     * Determina si una ruta multimedia pertenece al tratamiento del paciente.
     *
     * La comprobación limita el acceso al país y a la variante de tratamiento
     * que resultan de los diagnósticos del paciente. La validación completa de
     * la ruta y de la existencia del archivo corresponde a MultimediaService.
     *
     * @param  Paciente  $paciente  Paciente que solicita el archivo.
     * @param  string  $path  Ruta relativa recibida en la URL.
     * @return bool `true` si la ruta está dentro del material autorizado.
     *
     * @throws Exception Cuando el paciente no tiene un país asociado.
     */
    public function puedeAccederAMultimedia(Paciente $paciente, string $path): bool
    {
        $tratamiento = $this->getConfiguracionTratamientoPaciente($paciente);

        if (! $tratamiento) {
            return false;
        }

        [$pais, $diagnosticoDirname] = $tratamiento;
        $tratamientosDirname = trim((string) config('filesystems.multimedia.tratamientos_dirname'), '/');
        $path = ltrim(str_replace('\\', '/', $path), '/');
        $directorioAutorizado = "$tratamientosDirname/$pais/$diagnosticoDirname/";

        return str_starts_with($path, $directorioAutorizado);
    }

    /**
     * Determina la ubicación y los metadatos del tratamiento según los
     * diagnósticos del paciente.
     *
     * @return array{0: string, 1: string, 2: string, 3: string}|null
     *
     * @throws Exception Cuando el paciente no tiene un país asociado.
     */
    private function getConfiguracionTratamientoPaciente(Paciente $paciente): ?array
    {
        $diagnosticosIds = $paciente->diagnosticos->pluck('id');
        $tieneDiabetes = $diagnosticosIds->contains(Diagnostico::DIABETES);
        $tieneHipertensionArterial = $diagnosticosIds->contains(Diagnostico::HIPERTENSION_ARTERIAL);
        $tieneAmbos = $tieneDiabetes && $tieneHipertensionArterial;

        $pais = $paciente->centroSalud?->pais?->codigo;

        if (! $pais) {
            throw new Exception('No se pudo determinar el país del paciente', 500);
        }

        if ($tieneAmbos) {
            return [$pais, config('filesystems.multimedia.tratamientos_diabetes_hipertension_dirname'), 'DBT_HTA', 'Diabetes e hipertensión arterial'];
        }

        if ($tieneDiabetes) {
            return [$pais, config('filesystems.multimedia.tratamientos_diabetes_dirname'), 'DIABETES', 'Diabetes'];
        }

        if ($tieneHipertensionArterial) {
            return [$pais, config('filesystems.multimedia.tratamientos_hipertension_dirname'), 'HTA', 'Hipertensión arterial'];
        }

        return null;
    }

    /** Construye la respuesta desde la versión inmutable ya persistida. */
    private function getTratamiento(string $tipo, string $nombre, ?TratamientoVersion $version): array
    {
        $sesiones = $version
            ? $version->sesiones()
                ->with([
                    'elementos' => fn ($query) => $query->orderBy('orden'),
                    'elementos.multimedia',
                ])
                ->orderBy('semana')
                ->orderBy('sesion')
                ->get()
            : collect();
        $semanasContenido = $version?->contenido['sessions'] ?? [];
        $semanas = $sesiones->groupBy('semana')->map(function ($sesionesSemana, $semana) use ($semanasContenido) {
            $semanaContenido = $semanasContenido[$semana - 1] ?? [];
            unset($semanaContenido['subSessions']);

            return array_merge($semanaContenido, [
                // La semana se identifica por la posición persistida, no por Session.id.
                'id' => (int) $semana,
                'title' => $semanaContenido['title'] ?? "Semana $semana",
                'sesiones' => $sesionesSemana->map(function (TratamientoSesion $sesion) {
                    $sesionContenido = $sesion->contenido;
                    unset($sesionContenido['multimedia']);

                    return array_merge($sesionContenido, [
                        'id' => $sesion->sesion,
                        'title' => $sesionContenido['title'] ?? "Sesión {$sesion->sesion}",
                        'content' => $sesion->elementos
                            ->map(fn ($elemento) => $this->transformarElementoParaApi($elemento))
                            ->values()
                            ->all(),
                    ]);
                })->values()->all(),
            ]);
        })->values()->all();

        return [
            'tratamiento' => [
                'tipo' => $tipo,
                'nombre' => $nombre,
                'semanas' => $semanas,
            ]
        ];
    }

    /**
     * Conserva el contenido lógico del elemento y reemplaza únicamente sus
     * referencias de recursos por enlaces construidos desde las asociaciones
     * persistidas.
     *
     * @return array<string, mixed>
     */
    private function transformarElementoParaApi($elemento): array
    {
        $contenido = $elemento->contenido;
        $recursos = $elemento->multimedia->keyBy('tipo');
        $resultado = array_merge($contenido, [
            'tratamiento_elemento_id' => $elemento->id,
        ]);

        foreach (['AUDIO' => 'audio', 'IMAGE' => 'image', 'VIDEO' => 'video'] as $tipo => $campo) {
            /** @var TratamientoMultimedia|null $recurso */
            $recurso = $recursos->get($tipo);
            if ($recurso) {
                $resultado[$campo] = $this->linkMultimedia($recurso);
            }
        }

        if ($elemento->tipo === 'VIDEO' && ($video = $recursos->get('VIDEO'))) {
            $resultado['link'] = $this->linkMultimedia($video);
        }

        return $resultado;
    }

    private function linkMultimedia(TratamientoMultimedia $multimedia): string
    {
        $dirname = trim((string) config('filesystems.multimedia.tratamientos_dirname'), '/');

        return route('multimedia', ['path' => "$dirname/{$multimedia->ruta}"]);
    }

    /**
     * Importa el tratamiento lógico y sus recursos explícitamente declarados.
     * Cualquier cambio de contenido lógico, ruta o bytes de un recurso crea una versión.
     */
    private function sincronizarCatalogo(string $pais, string $tipo): ?TratamientoVersion
    {
        $catalogo = $this->leerCatalogo();
        $tratamiento = collect($catalogo['countries'][$pais] ?? [])->firstWhere('id', $tipo);
        if ($tratamiento === null) {
            return null;
        }

        $normalizado = $this->validarYNormalizarTratamiento($pais, $tratamiento);
        $hash = hash('sha256', json_encode([
            'tratamiento' => $tratamiento,
            'recursos' => $normalizado['recursos'],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $version = TratamientoVersion::query()->firstOrCreate([
            'pais' => $pais, 'tipo' => $tipo, 'hash' => $hash,
        ], ['contenido' => $tratamiento]);

        if ($version->wasRecentlyCreated) {
            DB::transaction(function () use ($version, $normalizado): void {
                foreach ($normalizado['sesiones'] as $datosSesion) {
                    $sesion = $version->sesiones()->create([
                        'semana' => $datosSesion['semana'],
                        'sesion' => $datosSesion['sesion'],
                        'contenido' => $datosSesion['contenido'],
                    ]);
                    foreach ($datosSesion['elementos'] as $datosElemento) {
                        $elemento = $sesion->elementos()->create([
                            'tipo' => $datosElemento['tipo'],
                            'orden' => $datosElemento['orden'],
                            'contenido' => $datosElemento['contenido'],
                        ]);
                        $elemento->multimedia()->createMany($datosElemento['recursos']);
                    }
                }
            });
        }

        return $version;
    }

    /** @return array{countries: array<string, array<int, array<string, mixed>>>} */
    private function leerCatalogo(): array
    {
        $dirname = trim((string) config('filesystems.multimedia.tratamientos_dirname'), '/');
        $path = storage_path("app/private/$dirname/content.json");
        if (! File::isFile($path)) {
            throw new RuntimeException("No se encontró content.json en $path.");
        }

        try {
            $catalogo = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException("content.json no contiene JSON válido: {$exception->getMessage()}", previous: $exception);
        }

        if (! is_array($catalogo) || ! isset($catalogo['countries']) || ! is_array($catalogo['countries'])) {
            throw new RuntimeException('content.json: falta la estructura obligatoria countries.');
        }

        return $catalogo;
    }

    /**
     * Valida el contrato y prepara los datos persistibles sin usar el filesystem
     * para deducir ninguna asociación lógica.
     *
     * @param array<string, mixed> $tratamiento
     * @return array{sesiones: array<int, array<string, mixed>>, recursos: array<int, array{ruta: string, checksum: string}>}
     */
    private function validarYNormalizarTratamiento(string $pais, array $tratamiento, ?array &$rutasGlobales = null): array
    {
        $contexto = "$pais/tratamiento";
        $this->requerirString($tratamiento, 'id', $contexto);
        $this->requerirString($tratamiento, 'name', $contexto);
        if (! isset($tratamiento['sessions']) || ! is_array($tratamiento['sessions'])) {
            throw new RuntimeException("$contexto: sessions debe ser un array.");
        }

        $sesiones = [];
        $recursosHash = [];
        $rutasUsadas = &$rutasGlobales;
        if ($rutasUsadas === null) {
            $rutasUsadas = [];
        }
        foreach ($tratamiento['sessions'] as $indiceSemana => $semanaContenido) {
            $contextoSemana = "$contexto/semana[$indiceSemana]";
            if (! is_array($semanaContenido)) {
                throw new RuntimeException("$contextoSemana debe ser un objeto.");
            }
            $this->requerirEntero($semanaContenido, 'id', $contextoSemana);
            if (! isset($semanaContenido['subSessions']) || ! is_array($semanaContenido['subSessions'])) {
                throw new RuntimeException("$contextoSemana: subSessions debe ser un array.");
            }
            foreach ($semanaContenido['subSessions'] as $indiceSubSesion => $subSesion) {
                $contextoSesion = "$contextoSemana/subSession[$indiceSubSesion]";
                if (! is_array($subSesion)) {
                    throw new RuntimeException("$contextoSesion debe ser un objeto.");
                }
                $numeroSesion = $this->requerirEntero($subSesion, 'id', $contextoSesion);
                if (! isset($subSesion['multimedia']) || ! is_array($subSesion['multimedia'])) {
                    throw new RuntimeException("$contextoSesion: multimedia debe ser un array.");
                }

                $elementos = [];
                foreach ($subSesion['multimedia'] as $orden => $elemento) {
                    $contextoElemento = "$contextoSesion/multimedia[$orden]";
                    if (! is_array($elemento)) {
                        throw new RuntimeException("$contextoElemento debe ser un objeto.");
                    }
                    $tipo = $this->requerirString($elemento, 'type', $contextoElemento);
                    $recursos = $this->validarRecursosElemento($tipo, $elemento, $contextoElemento, $rutasUsadas, $recursosHash);
                    $elementos[] = ['tipo' => $tipo, 'orden' => $orden, 'contenido' => $elemento, 'recursos' => $recursos];
                }
                $sesiones[] = [
                    'semana' => $indiceSemana + 1,
                    'sesion' => $numeroSesion,
                    'contenido' => $subSesion,
                    'elementos' => $elementos,
                ];
            }
        }

        return ['sesiones' => $sesiones, 'recursos' => $recursosHash];
    }

    /**
     * @param array<string, mixed> $elemento
     * @param array<string, true> $rutasUsadas
     * @param array<int, array{ruta: string, checksum: string}> $recursosHash
     * @return array<int, array{ruta: string, tipo: string, checksum: string}>
     */
    private function validarRecursosElemento(string $tipo, array $elemento, string $contexto, array &$rutasUsadas, array &$recursosHash): array
    {
        $campos = ['video', 'audio', 'image'];
        if (! in_array($tipo, ['DESCRIPTION', 'VIDEO', 'QUIZ', 'METAS'], true)) {
            throw new RuntimeException("$contexto: tipo de contenido desconocido '$tipo'.");
        }

        $permitidos = match ($tipo) {
            'DESCRIPTION' => ['audio' => 'AUDIO', 'image' => 'IMAGE'],
            'VIDEO' => ['video' => 'VIDEO'],
            'QUIZ', 'METAS' => [],
        };
        foreach ($campos as $campo) {
            if (array_key_exists($campo, $elemento) && ! array_key_exists($campo, $permitidos)) {
                throw new RuntimeException("$contexto: $tipo no puede tener recurso $campo.");
            }
        }
        if ($tipo === 'VIDEO' && (! array_key_exists('video', $elemento) || ! is_string($elemento['video']) || trim($elemento['video']) === '')) {
            throw new RuntimeException("$contexto: VIDEO debe tener un video no vacío.");
        }

        $recursos = [];
        foreach ($permitidos as $campo => $tipoRecurso) {
            if (! array_key_exists($campo, $elemento)) {
                continue;
            }
            $ruta = $elemento[$campo];
            if (! is_string($ruta) || trim($ruta) === '') {
                throw new RuntimeException("$contexto: el path $campo debe ser un string no vacío.");
            }
            $ruta = trim($ruta);
            if (str_starts_with($ruta, '/') || str_contains($ruta, '\\') || in_array('..', explode('/', $ruta), true)) {
                throw new RuntimeException("$contexto: el path $campo no es relativo y seguro.");
            }
            if (isset($rutasUsadas[$ruta])) {
                throw new RuntimeException("$contexto: el path multimedia '$ruta' está repetido.");
            }
            $pathFisico = storage_path('app/private/'.trim((string) config('filesystems.multimedia.tratamientos_dirname'), '/')."/$ruta");
            if (! File::isFile($pathFisico)) {
                throw new RuntimeException("$contexto: no existe el recurso físico '$ruta'.");
            }
            $rutasUsadas[$ruta] = true;
            $checksum = hash_file('sha256', $pathFisico);
            $recursosHash[] = ['ruta' => $ruta, 'checksum' => $checksum];
            $recursos[] = ['ruta' => $ruta, 'tipo' => $tipoRecurso, 'checksum' => $checksum];
        }

        return $recursos;
    }

    /** @param array<string, mixed> $datos */
    private function requerirString(array $datos, string $campo, string $contexto): string
    {
        if (! isset($datos[$campo]) || ! is_string($datos[$campo]) || trim($datos[$campo]) === '') {
            throw new RuntimeException("$contexto: $campo debe ser un string no vacío.");
        }
        return $datos[$campo];
    }

    /** @param array<string, mixed> $datos */
    private function requerirEntero(array $datos, string $campo, string $contexto): int
    {
        if (! isset($datos[$campo]) || ! is_int($datos[$campo])) {
            throw new RuntimeException("$contexto: $campo debe ser un entero.");
        }
        return $datos[$campo];
    }
}
