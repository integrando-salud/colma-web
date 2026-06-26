<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MultimediaService
{
    public function descargarMultimedia(string $path): BinaryFileResponse
    {
        $path = $this->normalizarPathMultimedia($path);

        if (! str_starts_with($path,  config('filesystems.multimedia.tratamientos_dirname') . '/')) {
            abort(404, 'No se encontró el archivo');
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            abort(404, 'No se encontró el archivo');
        }

        $mime = $disk->mimeType($path) ?: 'application/octet-stream';

        return response()->download($disk->path($path), basename($path), [
            'Content-Type' => $mime,
        ]);
    }

    private function normalizarPathMultimedia(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        $segmentos = explode('/', $path);

        if (
            $path === ''
            || str_contains($path, "\0")
            || in_array('..', $segmentos, true)
        ) {
            abort(404, 'No se encontró el archivo');
        }

        return $path;
    }
}
