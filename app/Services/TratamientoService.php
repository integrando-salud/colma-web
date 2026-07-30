<?php

namespace App\Services;

use App\Models\Diagnostico;
use App\Models\Paciente;
use Exception;
use Illuminate\Support\Facades\File;

class TratamientoService
{
    public function getTratamientoPaciente(Paciente $paciente)
    {
        $tratamiento = $this->getConfiguracionTratamientoPaciente($paciente);

        if (! $tratamiento) {
            return null;
        }

        return $this->getTratamiento(...$tratamiento);
    }

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

    private function getTratamiento(string $pais, ?string $diagnosticoDirname, string $tipo, string $nombre): array
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

    private function getSemanas(string $path): array
    {
        $semanas = array_map(function (string $semanaPath) {
            $numeroSemana = $this->getNumeroDesdeNombre(basename($semanaPath));

            return [
                'id' => $numeroSemana,
                'title' => "Semana $numeroSemana",
                'sesiones' => $this->getSesiones($semanaPath),
            ];
        }, File::directories($path));

        usort($semanas, fn (array $a, array $b) => $a['id'] <=> $b['id']);

        return $semanas;
    }

    private function getDirectoriosConNombre(string $path, string $prefijo): array
    {
        return array_values(array_filter(
            File::directories($path),
            fn (string $directory) => preg_match('/^'.preg_quote($prefijo, '/').'\\d+$/', basename($directory)) === 1
        ));
    }

    private function getSesiones(string $path): array
    {
        $sesiones = array_map(function (string $sesionPath) {
            $numeroSesion = $this->getNumeroDesdeNombre(basename($sesionPath));

            return [
                'id' => $numeroSesion,
                'title' => "Sesión $numeroSesion",
                'content' => $this->getContenido($sesionPath),
            ];
        }, File::directories($path));

        usort($sesiones, fn (array $a, array $b) => $a['id'] <=> $b['id']);

        return $sesiones;
    }

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

    private function getNumeroDesdeNombre(string $nombre): int
    {
        preg_match('/\d+/', $nombre, $matches);

        return (int) ($matches[0] ?? 0);
    }

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

    private function getPathRelativoStorage(string $path): string
    {
        return str_replace(storage_path('app/private/'), '', $path);
    }
}
