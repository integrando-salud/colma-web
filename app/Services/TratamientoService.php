<?php

namespace App\Services;

use App\Models\Diagnostico;
use App\Models\Paciente;
use Exception;
use Illuminate\Support\Facades\File;

class TratamientoService
{
    /**
     * Obtiene el tratamiento multimedia que corresponde al diagnóstico del paciente.
     *
     * Retorna `null` cuando el paciente no posee un diagnóstico con tratamiento
     * configurado.
     */
    public function getTratamientoPaciente(Paciente $paciente): ?array
    {
        $tratamiento = $this->getConfiguracionTratamientoPaciente($paciente);

        if (! $tratamiento) {
            return null;
        }

        return $this->getTratamiento(...$tratamiento);
    }

    /**
     * Cuenta las sesiones válidas disponibles para el tratamiento del paciente.
     *
     * Solo se consideran directorios con el formato `semana-N/sesion-N`.
     */
    public function getCantidadSesionesPaciente(Paciente $paciente): int
    {
        $tratamiento = $this->getConfiguracionTratamientoPaciente($paciente);

        if (! $tratamiento) {
            return 0;
        }

        [$pais, $diagnosticoDirname] = $tratamiento;
        $tratamientosDirname = config('filesystems.multimedia.tratamientos_dirname');
        $path = storage_path("app/private/$tratamientosDirname/$pais/$diagnosticoDirname");

        if (! File::isDirectory($path)) {
            return 0;
        }

        return collect($this->getDirectoriosConNombre($path, 'semana-'))
            ->sum(fn (string $semanaPath) => count($this->getDirectoriosConNombre($semanaPath, 'sesion-')));
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
    private function getTratamiento(string $pais, string $diagnosticoDirname, string $tipo, string $nombre): array
    {
        $tratamientosDirname = config('filesystems.multimedia.tratamientos_dirname');
        $path = storage_path("app/private/$tratamientosDirname/$pais/$diagnosticoDirname");
        $semanas = File::isDirectory($path)
            ? $this->getSemanas($path)
            : [];

        return [
            'tratamiento' => [
                'tipo' => $tipo,
                'nombre' => $nombre,
                'semanas' => $semanas,
            ]
        ];
    }

    /**
     * Obtiene las semanas válidas del tratamiento, ordenadas por su número.
     */
    private function getSemanas(string $path): array
    {
        $semanas = array_map(function (string $semanaPath) {
            $numeroSemana = $this->getNumeroDesdeNombre(basename($semanaPath));

            return [
                'id' => $numeroSemana,
                'title' => "Semana $numeroSemana",
                'sesiones' => $this->getSesiones($semanaPath),
            ];
        }, $this->getDirectoriosConNombre($path, 'semana-'));

        usort($semanas, fn (array $a, array $b) => $a['id'] <=> $b['id']);

        return $semanas;
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
     * Obtiene las sesiones válidas de una semana, ordenadas por su número.
     */
    private function getSesiones(string $path): array
    {
        $sesiones = array_map(function (string $sesionPath) {
            $numeroSesion = $this->getNumeroDesdeNombre(basename($sesionPath));

            return [
                'id' => $numeroSesion,
                'title' => "Sesión $numeroSesion",
                'content' => $this->getContenido($sesionPath),
            ];
        }, $this->getDirectoriosConNombre($path, 'sesion-'));

        usort($sesiones, fn (array $a, array $b) => $a['id'] <=> $b['id']);

        return $sesiones;
    }

    /**
     * Lista los archivos de una sesión, clasificados por su tipo MIME.
     */
    private function getContenido(string $path): array
    {
        $contenido = array_map(function ($archivo) {
            $archivoPath = $archivo->getPathname();
            $mime = File::mimeType($archivoPath) ?: 'application/octet-stream';

            return [
                'type' => $this->getTipoContenido($mime),
                'title' => $archivo->getFilename(),
                'link' => route('multimedia', ['path' => $this->getPathRelativoStorage($archivoPath)]),
            ];
        }, File::files($path));

        usort($contenido, fn (array $a, array $b) => strnatcasecmp($a['title'], $b['title']));

        return $contenido;
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
