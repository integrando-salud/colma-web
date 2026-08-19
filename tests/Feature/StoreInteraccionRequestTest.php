<?php

namespace Tests\Feature;

use App\Http\Requests\Dashboard\Interacciones\StoreInteraccionRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreInteraccionRequestTest extends TestCase
{
    public function test_no_permite_una_fecha_de_contacto_futura(): void
    {
        Carbon::setTestNow('2026-08-19');

        $request = new StoreInteraccionRequest();
        $request->merge([
            'fecha' => '20/08/2026',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('fecha'));

        Carbon::setTestNow();
    }
}
