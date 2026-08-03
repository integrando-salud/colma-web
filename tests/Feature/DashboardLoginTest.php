<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_bloquea_el_sexto_intento_fallido_de_inicio_de_sesion(): void
    {
        $credentials = [
            'documento' => '12345678',
            'password' => 'credencial-incorrecta',
        ];

        foreach (range(1, 5) as $attempt) {
            $this->post('/ingresar', $credentials)
                ->assertSessionHasErrors('login');
        }

        $response = $this->post('/ingresar', $credentials);

        $response->assertSessionHasErrors('login');
        $this->assertStringContainsString(
            'Demasiados intentos de inicio de sesión.',
            session('errors')->first('login'),
        );
    }
}
