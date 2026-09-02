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

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('fecha'));
        $this->assertSame('La fecha no puede ser posterior a hoy.', $validator->errors()->first('fecha'));

        Carbon::setTestNow();
    }

    public function test_no_permite_una_fecha_de_cita_futura(): void
    {
        Carbon::setTestNow('2026-08-19');

        foreach (['Cita de entrenamiento', 'Cita final', 'Re-entrenamiento'] as $motivo) {
            $request = new StoreInteraccionRequest();
            $request->merge([
                'contactado' => true,
                'motivo' => $motivo,
                'fecha_cita_final' => '20/08/2026',
            ]);

            $validator = Validator::make($request->all(), $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), $motivo);
            $this->assertTrue($validator->errors()->has('fecha_cita_final'), $motivo);
            $this->assertSame('La fecha no puede ser posterior a hoy.', $validator->errors()->first('fecha_cita_final'), $motivo);
        }

        Carbon::setTestNow();
    }

    public function test_no_permite_una_nueva_fecha_de_contacto_pasada(): void
    {
        Carbon::setTestNow('2026-08-19');

        $request = new StoreInteraccionRequest();
        $request->merge([
            'contactado' => false,
            'motivo' => 'Alerta clínica',
            'nueva_fecha_contacto' => '18/08/2026',
        ]);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('nueva_fecha_contacto'));
        $this->assertSame('La fecha no puede ser anterior a hoy.', $validator->errors()->first('nueva_fecha_contacto'));

        Carbon::setTestNow();
    }
}
