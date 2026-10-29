<?php

namespace App\Http\Middleware;

use App\Services\MobileApiLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogMobileApi
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('mobile_api_logs.enabled') || ! $request->isMethod('POST') ||
            ! $request->is('api/sync-data-paciente', 'api/pedido-ayuda')) {
            return $next($request);
        }

        $service = app(MobileApiLogService::class);
        $id = (string) Str::uuid();
        $start = hrtime(true);
        $request->attributes->set('mobile_api_request_id', $id);
        Log::withContext(['request_id' => $id]);

        try {
            $service->persist($id, [
                'received_at' => now('UTC'),
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'request_body' => $service->body($request->getContent()),
                'client_metadata' => collect([
                    'app_version' => $request->header('X-App-Version'),
                    'platform' => $request->header('X-Platform'),
                    'os_version' => $request->header('X-OS-Version'),
                ])->map(fn ($value) => $value === null ? null : mb_substr($value, 0, 255))->all(),
            ]);

            $response = $next($request);
            try {
                $exception = $request->attributes->get('mobile_api_exception') ?? ($response->exception ?? null);
                $service->persist($id, [
                    'user_id' => $request->user()?->id,
                    'paciente_id' => $request->user()?->paciente?->id,
                    'response_status' => $response->getStatusCode(),
                    'response_body' => $service->body((string) $response->getContent()),
                    'duration_ms' => (int) round((hrtime(true) - $start) / 1e6),
                    'exception_class' => $exception ? $exception::class : null,
                    // El mensaje completo puede incluir SQL y datos clínicos: queda en el log técnico.
                    'exception_message' => $exception ? 'Consultar el log técnico con request_id='.$id : null,
                ]);
            } catch (\Throwable $exception) {
                Log::error('No se pudo completar el log móvil.', ['exception_class' => $exception::class]);
            }
            $response->headers->set('X-Request-ID', $id);

            return $response;
        } finally {
            Log::withoutContext(['request_id']);
        }
    }
}
