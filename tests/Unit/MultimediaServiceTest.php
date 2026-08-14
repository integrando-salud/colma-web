<?php

namespace Tests\Unit;

use App\Services\MultimediaService;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class MultimediaServiceTest extends TestCase
{
    private string $tratamientosDirname = 'tratamientos-multimedia-prueba';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('filesystems.multimedia.tratamientos_dirname', $this->tratamientosDirname);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path("app/private/{$this->tratamientosDirname}"));

        parent::tearDown();
    }

    public function test_descarga_un_archivo_del_directorio_de_tratamientos(): void
    {
        $path = "{$this->tratamientosDirname}/AR/DBT/guia.pdf";
        $archivo = storage_path("app/private/{$path}");
        File::ensureDirectoryExists(dirname($archivo));
        File::put($archivo, '%PDF-1.4');

        $response = app(MultimediaService::class)->descargarMultimedia($path);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertStringContainsString('filename=guia.pdf', $response->headers->get('content-disposition'));
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_rechaza_rutas_que_no_pertenecen_al_directorio_de_tratamientos(): void
    {
        $this->expectException(NotFoundHttpException::class);

        app(MultimediaService::class)->descargarMultimedia('otro-directorio/archivo.pdf');
    }

    public function test_rechaza_rutas_con_recorrido_de_directorios(): void
    {
        $this->expectException(NotFoundHttpException::class);

        app(MultimediaService::class)->descargarMultimedia("{$this->tratamientosDirname}/../secreto.pdf");
    }

    public function test_rechaza_archivos_inexistentes(): void
    {
        $this->expectException(NotFoundHttpException::class);

        app(MultimediaService::class)->descargarMultimedia("{$this->tratamientosDirname}/inexistente.pdf");
    }

    public function test_rechaza_directorios_y_enlaces_simbolicos_fuera_de_tratamientos(): void
    {
        $directorio = storage_path("app/private/{$this->tratamientosDirname}/AR");
        File::ensureDirectoryExists($directorio);
        $archivoExterno = storage_path('app/private/archivo-privado-prueba.txt');
        File::put($archivoExterno, 'privado');
        symlink($archivoExterno, "$directorio/enlace.txt");

        try {
            foreach (["{$this->tratamientosDirname}/AR", "{$this->tratamientosDirname}/AR/enlace.txt"] as $path) {
                try {
                    app(MultimediaService::class)->descargarMultimedia($path);
                    $this->fail("La ruta [$path] debería ser rechazada.");
                } catch (NotFoundHttpException $exception) {
                    $this->assertSame(404, $exception->getStatusCode());
                }
            }
        } finally {
            File::delete($archivoExterno);
        }
    }
}
