<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MultimediaService
{
    /**
     * Genera una respuesta de descarga para un archivo del directorio de tratamientos.
     *
     * El path solicitado se valida y se resuelve en el disco local para impedir que se
     * descarguen archivos ajenos al directorio multimedia configurado.
     *
     * @param  string  $path  Ruta relativa del archivo dentro del disco local.
     * @return BinaryFileResponse Respuesta que descarga el archivo con su nombre y MIME.
     */
    public function descargarMultimedia(string $path): BinaryFileResponse
    {
        $path = $this->normalizarPathMultimedia($path);
        $tratamientosDirname = $this->getTratamientosDirname();

        if (! str_starts_with($path, $tratamientosDirname . '/')) {
            abort(404, 'No se encontró el archivo');
        }

        $disk = Storage::disk('local');

        if (! $disk->fileExists($path)) {
            abort(404, 'No se encontró el archivo');
        }

        $this->validarUbicacionArchivo($disk->path($path), $disk->path($tratamientosDirname));

        $mime = $disk->mimeType($path) ?: 'application/octet-stream';

        return response()->download($disk->path($path), basename($path), [
            'Content-Type' => $mime,
        ]);
    }

    /**
     * Obtiene el nombre del directorio que contiene el material de tratamientos.
     *
     * @return string Nombre de directorio relativo, sin barras finales.
     */
    private function getTratamientosDirname(): string
    {
        $dirname = config('filesystems.multimedia.tratamientos_dirname');

        if (! is_string($dirname) || trim($dirname, '/') === '') {
            abort(404, 'No se encontró el archivo');
        }

        return trim(str_replace('\\', '/', $dirname), '/');
    }

    /**
     * Comprueba que el archivo resuelto permanezca dentro del directorio autorizado.
     *
     * La resolución de enlaces simbólicos evita exponer archivos externos aun cuando
     * el enlace esté creado dentro del directorio de tratamientos.
     *
     * @param  string  $archivoPath  Ruta absoluta del archivo solicitado.
     * @param  string  $directorioPath  Ruta absoluta del directorio autorizado.
     * @return void
     */
    private function validarUbicacionArchivo(string $archivoPath, string $directorioPath): void
    {
        $archivoRealPath = realpath($archivoPath);
        $directorioRealPath = realpath($directorioPath);

        if (
            $archivoRealPath === false
            || $directorioRealPath === false
            || ! str_starts_with($archivoRealPath, $directorioRealPath . DIRECTORY_SEPARATOR)
        ) {
            abort(404, 'No se encontró el archivo');
        }
    }

    /**
     * Normaliza y valida una ruta relativa recibida desde la solicitud.
     *
     * @param  string  $path  Ruta indicada por el cliente.
     * @return string Ruta con separadores normalizados y sin barra inicial.
     */
    private function normalizarPathMultimedia(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        $segmentos = explode('/', $path);

        if (
            $path === ''
            || str_contains($path, "\0")
            || in_array('..', $segmentos, true)
            || in_array('.', $segmentos, true)
        ) {
            abort(404, 'No se encontró el archivo');
        }

        return $path;
    }
}
