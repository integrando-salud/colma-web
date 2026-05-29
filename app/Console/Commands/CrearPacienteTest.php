<?php

namespace App\Console\Commands;

use App\Services\PacienteService;
use Illuminate\Console\Command;

class CrearPacienteTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'colma:crear-paciente-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea un paciente de prueba';

    /**
     * Execute the console command.
     */
    public function handle(PacienteService $pacienteService)
    {
        $this->info('Creando paciente de prueba...');

        $paciente = $pacienteService->crearPacienteTest();

        if ($paciente) {
            $this->info('Paciente creado con éxito');

            return Command::SUCCESS;
        } else {
            $this->error('Error al crear el paciente');

            return Command::FAILURE;
        }
    }
}
