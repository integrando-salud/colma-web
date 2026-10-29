<?php

namespace Tests\Unit;

use App\Services\MobileApiLogService;
use Tests\TestCase;

class MobileApiLogBodyTest extends TestCase
{
    public function test_zero_empty_and_undefined_limits_preserve_the_full_body(): void
    {
        $data = ['value' => str_repeat('x', 300000), 'token' => 'secret'];
        $service = app(MobileApiLogService::class);

        foreach ([0, '', null] as $limit) {
            config(['mobile_api_logs.max_body_bytes' => $limit]);
            $result = $service->body(json_encode($data));
            $this->assertFalse($result['truncated']);
            $this->assertSame($data['value'], $result['data']['value']);
            $this->assertSame('[REDACTED]', $result['data']['token']);
        }
    }

    public function test_positive_limit_still_truncates(): void
    {
        config(['mobile_api_logs.max_body_bytes' => 1024]);
        $result = app(MobileApiLogService::class)->body(json_encode(['value' => str_repeat('x', 2000)]));
        $this->assertTrue($result['truncated']);
        $this->assertSame(1024, strlen($result['preview']));
    }
}
