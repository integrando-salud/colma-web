<?php

namespace App\Services;

use App\Models\MobileApiLog;
use Illuminate\Support\Facades\Log;
use Throwable;

class MobileApiLogService
{
    /** Captura el cuerpo antes de validaciones o normalizaciones. */
    public function body(string $body): array
    {
        $decoded = json_decode($body, true);
        if ($body === '') {
            return ['data' => null, 'bytes' => 0, 'truncated' => false];
        }
        if (json_last_error() !== JSON_ERROR_NONE) {
            // No persistir texto arbitrario que no se pueda depurar de secretos.
            return ['invalid_json' => true, 'bytes' => strlen($body), 'sha256' => hash('sha256', $body)];
        }
        $safe = $this->redact($decoded);
        $encoded = json_encode($safe, JSON_INVALID_UTF8_SUBSTITUTE);
        $limit = (int) config('mobile_api_logs.max_body_bytes', 0);

        return $limit > 0 && strlen($encoded) > $limit
            ? ['preview' => mb_strcut($encoded, 0, $limit, 'UTF-8'), 'bytes' => strlen($body), 'truncated' => true]
            : ['data' => $safe, 'bytes' => strlen($body), 'truncated' => false];
    }

    private function redact(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        foreach ($value as $key => $item) {
            $value[$key] = preg_match('/password|passwd|token|authorization|cookie|secret/i', (string) $key)
                ? '[REDACTED]' : $this->redact($item);
        }

        return $value;
    }

    /** Los errores de observabilidad nunca cambian el resultado clínico. */
    public function persist(string $requestId, array $data): void
    {
        try {
            MobileApiLog::updateOrCreate(['request_id' => $requestId], $data);
        } catch (Throwable $exception) {
            Log::error('No se pudo guardar el log de la app móvil.', [
                'request_id' => $requestId,
                'exception_class' => $exception::class,
            ]);
        }
    }
}
