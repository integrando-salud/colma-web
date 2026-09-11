<?php

namespace App\Console\Commands;

use App\Services\TratamientoService;
use Illuminate\Console\Command;
use Throwable;

class SincronizarCatalogoTratamientos extends Command
{
    protected $signature = 'tratamientos:sincronizar-catalogo
                            {--pais=* : Código de país a importar; se pueden indicar varios}
                            {--tipo=* : Directorio de tratamiento a importar; se pueden indicar varios}';

    protected $description = 'Importa al catálogo los tratamientos, sesiones y archivos disponibles en storage.';

    public function handle(TratamientoService $tratamientoService): int
    {
        try {
            $paises = $this->option('pais') ?: null;
            $tipos = $this->option('tipo') ?: null;
            $catalogos = $tratamientoService->sincronizarCatalogos($paises, $tipos);

            if ($catalogos === []) {
                $this->warn('No se encontraron tratamientos para importar.');

                return self::SUCCESS;
            }

            foreach ($catalogos as $catalogo) {
                $estado = $catalogo['creada'] ? 'nueva versión creada' : 'versión existente';
                $this->line("{$catalogo['pais']}/{$catalogo['tipo']}: {$estado} (ID {$catalogo['version_id']})");
            }

            $this->info('Catálogo de tratamientos sincronizado.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('No se pudo sincronizar el catálogo: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
