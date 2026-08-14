<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTratamientoControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica que un token de un usuario del panel no pueda consultar tratamientos.
     */
    public function test_rechaza_usuarios_que_no_son_pacientes(): void
    {
        $user = User::create([
            'nombre' => 'Usuario del panel',
            'sexo' => 'F',
            'documento' => '87654321',
            'email' => 'panel-tratamiento@example.test',
            'password' => 'secreto',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tratamiento')
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->get('/api/multimedia/tratamientos/AR/DBT/semana-1/sesion-1/video.mp4')
            ->assertForbidden();
    }
}
