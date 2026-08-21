<?php

namespace App\Services;

use App\Models\Diagnostico;
use App\Models\Entrenamiento;
use App\Models\Paciente;
use App\Models\TratamientoMultimedia;
use App\Models\TratamientoSesion;
use App\Models\TratamientoVersion;
use Exception;
use Illuminate\Support\Facades\File;

class TratamientoService
{
    /**
     * Obtiene el tratamiento multimedia que corresponde al diagnóstico del paciente.
     *
     * Retorna `null` cuando el paciente no posee un diagnóstico con tratamiento
     * configurado.
     *
     * @throws Exception Cuando el paciente no tiene un país asociado.
     */
    public function getTratamientoPaciente(Paciente $paciente): ?array
    {
        $tratamiento = $this->getConfiguracionTratamientoPaciente($paciente);

        if (! $tratamiento) {
            return null;
        }

        [, , $tipo, $nombre] = $tratamiento;
        $entrenamiento = $paciente->entrenamientos()->where('activo', true)->latest()->first();
        $version = $entrenamiento?->tratamientoVersion
            ?? $this->getVersionTratamientoPaciente($paciente);

        // Los entrenamientos previos a este catálogo adoptan la versión vigente
        // la primera vez que la aplicación consulta su tratamiento.
        if ($entrenamiento && ! $entrenamiento->tratamiento_version_id && $version) {
            $entrenamiento->update(['tratamiento_version_id' => $version->id]);
        }

        return $this->getTratamiento($tipo, $nombre, $version);
    }

    /**
     * Cuenta las sesiones válidas disponibles para el tratamiento del paciente.
     *
     * Solo se consideran directorios con el formato `semana-N/sesion-N`.
     *
     * @throws Exception Cuando el paciente no tiene un país asociado.
     */
    public function getCantidadSesionesPaciente(Paciente $paciente): int
    {
        $tratamiento = $this->getConfiguracionTratamientoPaciente($paciente);

        if (! $tratamiento) {
            return 0;
        }

        return $this->getVersionTratamientoPaciente($paciente)?->sesiones()->count() ?? 0;
    }

    /** Obtiene o crea la versión vigente del catálogo asignado al paciente. */
    public function getVersionTratamientoPaciente(Paciente $paciente): ?TratamientoVersion
    {
        $tratamiento = $this->getConfiguracionTratamientoPaciente($paciente);

        if (! $tratamiento) {
            return null;
        }

        [$pais, $diagnosticoDirname] = $tratamiento;

        return $this->sincronizarCatalogo($pais, $diagnosticoDirname);
    }

    /**
     * Sincroniza en el catálogo todos los tratamientos existentes en disco.
     *
     * @param array<int, string>|null $paises Códigos de país a importar; `null` importa todos.
     * @param array<int, string>|null $tipos Directorios de tratamiento a importar; `null` importa todos.
     * @return array<int, array{pais: string, tipo: string, version_id: int, creada: bool}>
     */
    public function sincronizarCatalogos(?array $paises = null, ?array $tipos = null): array
    {
        $dirname = config('filesystems.multimedia.tratamientos_dirname');
        $basePath = storage_path("app/private/$dirname");

        if (! File::isDirectory($basePath)) {
            return [];
        }

        $paisesDisponibles = array_map('basename', File::directories($basePath));
        $paises = $paises === null ? $paisesDisponibles : array_intersect($paisesDisponibles, $paises);
        $tiposDisponibles = array_filter(array_unique([
            config('filesystems.multimedia.tratamientos_diabetes_dirname'),
            config('filesystems.multimedia.tratamientos_hipertension_dirname'),
            config('filesystems.multimedia.tratamientos_diabetes_hipertension_dirname'),
        ]));
        $tipos = $tipos === null ? $tiposDisponibles : array_intersect($tiposDisponibles, $tipos);
        $resultado = [];

        foreach ($paises as $pais) {
            foreach ($tipos as $tipo) {
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
        }

        return $resultado;
    }

    /** Cuenta sesiones cuyo conjunto completo de archivos fue leído en el entrenamiento. */
    public function getCantidadSesionesCompletadas(Entrenamiento $entrenamiento): int
    {
        if (! $entrenamiento->tratamiento_version_id) {
            return 0;
        }

        return TratamientoSesion::query()
            ->where('tratamiento_version_id', $entrenamiento->tratamiento_version_id)
            ->has('multimedia')
            ->withCount('multimedia')
            ->withCount(['multimedia as multimedia_leida_count' => fn ($query) => $query->whereHas(
                'lecturas',
                fn ($lecturas) => $lecturas->where('entrenamiento_id', $entrenamiento->id),
            )])
            ->get()
            ->filter(fn (TratamientoSesion $sesion) => $sesion->multimedia_count === $sesion->multimedia_leida_count)
            ->count();
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

    /**
     * Construye la estructura de respuesta del tratamiento a partir de su configuración.
     */
    private function getTratamiento(string $tipo, string $nombre, ?TratamientoVersion $version): array
    {
        $sesiones = $version
            ? $version->sesiones()->with('multimedia')->orderBy('semana')->orderBy('sesion')->get()
            : collect();
        $semanas = $sesiones->groupBy('semana')->map(fn ($sesionesSemana, $semana) => [
            'id' => (int) $semana,
            'title' => "Semana $semana",
            'sesiones' => $sesionesSemana->map(fn (TratamientoSesion $sesion) => [
                'id' => $sesion->sesion,
                'title' => "Sesión {$sesion->sesion}",
                'content' => $sesion->multimedia
                    ->sortBy('ruta', SORT_NATURAL | SORT_FLAG_CASE)
                    ->map(fn (TratamientoMultimedia $multimedia) => [
                        'tratamiento_multimedia_id' => $multimedia->id,
                        'type' => $multimedia->tipo,
                        'title' => basename($multimedia->ruta),
                        'link' => route('multimedia', ['path' => $multimedia->ruta]),
                    ])->values()->all(),
            ])->values()->all(),
        ])->values()->all();

        return [
            'tratamiento' => [
                'tipo' => $tipo,
                'nombre' => $nombre,
                'semanas' => $semanas,
            ]
        ];
    }

    /**
     * Importa la estructura de archivos y retorna una versión inmutable.
     * Un cambio en nombre, contenido o ubicación crea una nueva versión.
     */
    private function sincronizarCatalogo(string $pais, string $tipo): ?TratamientoVersion
    {
        $dirname = config('filesystems.multimedia.tratamientos_dirname');
        $basePath = storage_path("app/private/$dirname/$pais/$tipo");

        if (! File::isDirectory($basePath)) {
            return null;
        }

        $contenido = [];
        $sesiones = [];
        foreach ($this->getDirectoriosConNombre($basePath, 'semana-') as $semanaPath) {
            $semana = $this->getNumeroDesdeNombre(basename($semanaPath));
            foreach ($this->getDirectoriosConNombre($semanaPath, 'sesion-') as $sesionPath) {
                $sesion = $this->getNumeroDesdeNombre(basename($sesionPath));
                $sesiones[] = ['semana' => $semana, 'sesion' => $sesion];
                foreach (File::files($sesionPath) as $archivo) {
                    $ruta = $this->getPathRelativoStorage($archivo->getPathname());
                    $mime = File::mimeType($archivo->getPathname()) ?: 'application/octet-stream';
                    $contenido[] = [
                        'semana' => $semana,
                        'sesion' => $sesion,
                        'ruta' => $ruta,
                        'tipo' => $this->getTipoContenido($mime),
                        'checksum' => hash_file('sha256', $archivo->getPathname()),
                    ];
                }
            }
        }

        usort($contenido, fn (array $a, array $b) => [$a['semana'], $a['sesion'], $a['ruta']] <=> [$b['semana'], $b['sesion'], $b['ruta']]);
        usort($sesiones, fn (array $a, array $b) => [$a['semana'], $a['sesion']] <=> [$b['semana'], $b['sesion']]);
        $hash = hash('sha256', json_encode(['sesiones' => $sesiones, 'contenido' => $contenido], JSON_THROW_ON_ERROR));
        $version = TratamientoVersion::query()->firstOrCreate(['pais' => $pais, 'tipo' => $tipo, 'hash' => $hash]);

        if ($version->wasRecentlyCreated) {
            foreach ($sesiones as $datosSesion) {
                $archivos = collect($contenido)->where('semana', $datosSesion['semana'])->where('sesion', $datosSesion['sesion']);
                $sesion = $version->sesiones()->create($datosSesion);
                $sesion->multimedia()->createMany($archivos->map(fn (array $archivo) => [
                    'ruta' => $archivo['ruta'], 'tipo' => $archivo['tipo'], 'checksum' => $archivo['checksum'],
                ])->all());
            }
        }

        return $version;
    }

    /**
     * Filtra los subdirectorios cuyo nombre cumple el formato "{prefijo}N".
     */
    private function getDirectoriosConNombre(string $path, string $prefijo): array
    {
        return array_values(array_filter(
            File::directories($path),
            fn (string $directory) => preg_match('/^'.preg_quote($prefijo, '/').'\\d+$/', basename($directory)) === 1
        ));
    }

    /**
     * Extrae el primer número incluido en el nombre de un directorio.
     */
    private function getNumeroDesdeNombre(string $nombre): int
    {
        preg_match('/\d+/', $nombre, $matches);

        return (int) ($matches[0] ?? 0);
    }

    /**
     * Convierte un tipo MIME en una categoría consumible por el cliente.
     */
    private function getTipoContenido(string $mime): string
    {
        if (str_starts_with($mime, 'image/')) {
            return 'IMAGEN';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'VIDEO';
        }

        if (str_starts_with($mime, 'audio/')) {
            return 'AUDIO';
        }

        return 'ARCHIVO';
    }

    /**
     * Convierte una ruta absoluta dentro del almacenamiento privado en una ruta relativa.
     */
    private function getPathRelativoStorage(string $path): string
    {
        return str_replace(storage_path('app/private/'), '', $path);
    }
}
