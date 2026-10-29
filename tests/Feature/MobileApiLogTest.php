<?php

namespace Tests\Feature;

use App\Models\MobileApiLog;
use App\Models\Paciente;
use App\Models\User;
use App\Services\AlertaService;
use App\Services\MobileApiLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileApiLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mobile_api_logs.enabled' => true]);
    }

    public function test_success_and_redaction(): void
    {
        [$user] = $this->crearPaciente();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/sync-data-paciente', [
            'userId' => $user->id, 'extra' => ['token' => 'hidden', 'value' => 'original'],
        ])->assertOk()->assertHeader('X-Request-ID');
        $log = MobileApiLog::sole();
        $this->assertSame($response->headers->get('X-Request-ID'), $log->request_id);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('[REDACTED]', $log->request_body['data']['extra']['token']);
        $this->assertSame('original', $log->request_body['data']['extra']['value']);
        $this->assertSame($response->json(), $log->response_body['data']);
    }

    public function test_rejected_requests_are_logged(): void
    {
        $this->postJson('/api/pedido-ayuda')->assertUnauthorized();
        $this->assertSame(401, MobileApiLog::sole()->response_status);
        [$user] = $this->crearPaciente();
        $this->actingAs($user, 'sanctum')->postJson('/api/sync-data-paciente', [
            'userId' => $user->id, 'glucosa' => 'invalid',
        ])->assertUnprocessable();
        $log = MobileApiLog::latest('id')->first();
        $this->assertSame(422, $log->response_status);
        $this->assertSame('invalid', $log->request_body['data']['glucosa']);
        $this->actingAs($user, 'sanctum')->postJson('/api/sync-data-paciente', ['userId' => -1])->assertForbidden();
        $this->assertSame(403, MobileApiLog::latest('id')->first()->response_status);
    }

    public function test_operation_error_is_correlated(): void
    {
        [$user] = $this->crearPaciente();
        $this->mock(AlertaService::class)->shouldReceive('savePedidoAyuda')->once()->andThrow(new \RuntimeException('failure'));
        $this->actingAs($user, 'sanctum')->postJson('/api/pedido-ayuda', ['reason' => 'test'])->assertStatus(500);
        $log = MobileApiLog::sole();
        $this->assertSame(\RuntimeException::class, $log->exception_class);
        $this->assertStringContainsString($log->request_id, $log->exception_message);
        $this->assertSame('test', $log->request_body['data']['reason']);
    }

    public function test_disabled_and_other_endpoints_are_not_logged(): void
    {
        config(['mobile_api_logs.enabled' => false]);
        $this->postJson('/api/pedido-ayuda')->assertUnauthorized()->assertHeaderMissing('X-Request-ID');
        config(['mobile_api_logs.enabled' => true]);
        $this->getJson('/api/user')->assertUnauthorized();
        $this->assertDatabaseCount('mobile_api_logs', 0);
    }

    public function test_missing_log_table_does_not_break_operation(): void
    {
        [$user] = $this->crearPaciente();
        Schema::drop('mobile_api_logs');
        $this->actingAs($user, 'sanctum')->postJson('/api/sync-data-paciente', ['userId' => $user->id])->assertOk();
    }

    public function test_pruning_respects_retention_even_when_logging_disabled(): void
    {
        config(['mobile_api_logs.enabled' => false, 'mobile_api_logs.retention_days' => 7]);
        foreach ([8, 1] as $days) {
            MobileApiLog::create(['request_id' => (string) \Illuminate\Support\Str::uuid(),
                'received_at' => now('UTC')->subDays($days), 'endpoint' => 'api/pedido-ayuda', 'method' => 'POST']);
        }
        $this->artisan('mobile-api-logs:prune')->assertSuccessful();
        $this->assertDatabaseCount('mobile_api_logs', 1);
    }

    public function test_body_limits_and_invalid_json(): void
    {
        config(['mobile_api_logs.max_body_bytes' => 20]);
        $service = app(MobileApiLogService::class);
        $this->assertTrue($service->body(json_encode(['value' => str_repeat('x', 100)]))['truncated']);
        $body = $service->body('{broken');
        $this->assertTrue($body['invalid_json']);
        $this->assertArrayNotHasKey('data', $body);
    }

    public function test_cleanup_schedule_respects_toggle_and_interval(): void
    {
        $event = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command ?? '', 'mobile-api-logs:prune'));
        $this->assertNotNull($event);
        $this->travelTo(\Illuminate\Support\Carbon::parse('1970-01-03 03:00:00', 'UTC'));
        config(['mobile_api_logs.auto_cleanup' => false, 'mobile_api_logs.cleanup_every_days' => 2]);
        $this->assertFalse($event->filtersPass($this->app));
        config(['mobile_api_logs.auto_cleanup' => true]);
        $this->assertTrue($event->filtersPass($this->app));
        $this->travelTo(\Illuminate\Support\Carbon::parse('1970-01-04 03:00:00', 'UTC'));
        $this->assertFalse($event->filtersPass($this->app));
        $this->travelBack();
    }

    private function crearPaciente(): array
    {
        $user = User::create([
            'nombre' => 'Ana Paz',
            'sexo' => 'F',
            'documento' => '12345678',
            'email' => 'ana@example.test',
            'password' => 'secreto',
        ]);
        $user->assign(User::ROL_PACIENTE);

        $paciente = Paciente::create([
            'user_id' => $user->id,
            'nombre' => 'Ana',
            'apellido' => 'Paz',
            'fecha_nacimiento' => '1980-01-01',
            'sexo' => 'F',
            'documento' => $user->documento,
            'telefono' => '123456789',
            'email' => $user->email,
            'direccion' => 'Calle 1',
            'fecha_enrolamiento' => '2026-01-01',
        ]);

        return [$user, $paciente];
    }

}
