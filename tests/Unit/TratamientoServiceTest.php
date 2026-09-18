<?php

namespace Tests\Unit;

use App\Models\TratamientoElemento;
use App\Models\TratamientoVersion;
use App\Services\TratamientoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class TratamientoServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $tratamientosDirname = 'tratamientos-prueba';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('filesystems.multimedia.tratamientos_dirname', $this->tratamientosDirname);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path("app/private/$this->tratamientosDirname"));
        parent::tearDown();
    }

    public function test_importa_elementos_logicos_y_asocia_solo_los_recursos_declarados(): void
    {
        $this->crearCatalogo([
            ['type' => 'DESCRIPTION', 'title' => 'Sin recurso'],
            ['type' => 'DESCRIPTION', 'title' => 'Con audio e imagen', 'audio' => 'AR/DBT/audio.m4a', 'image' => 'AR/DBT/imagen.png'],
            ['type' => 'VIDEO', 'title' => 'Video', 'video' => 'AR/DBT/video.mp4'],
            ['type' => 'QUIZ', 'title' => 'Quiz', 'questions' => []],
            ['type' => 'METAS', 'title' => 'Metas', 'metas' => []],
        ]);
        $this->crearRecurso('AR/DBT/audio.m4a');
        $this->crearRecurso('AR/DBT/imagen.png');
        $this->crearRecurso('AR/DBT/video.mp4');

        $resultado = app(TratamientoService::class)->sincronizarCatalogos();

        $this->assertCount(1, $resultado);
        $version = TratamientoVersion::with('sesiones.elementos.multimedia')->sole();
        $this->assertSame('DIABETES', $version->tipo);
        $this->assertSame('DIABETES', $version->contenido['id']);
        $this->assertSame(1, $version->sesiones->count());
        $this->assertSame(5, TratamientoElemento::count());
        $this->assertSame([0, 1, 2, 3, 4], $version->sesiones->sole()->elementos->sortBy('orden')->pluck('orden')->all());
        $this->assertSame(3, $version->sesiones->sole()->elementos->flatMap->multimedia->count());
        $this->assertSame(['AUDIO', 'IMAGE', 'VIDEO'], $version->sesiones->sole()->elementos->flatMap->multimedia->sortBy('tipo')->pluck('tipo')->values()->all());
        $this->assertSame(0, $version->sesiones->sole()->elementos->firstWhere('tipo', 'QUIZ')->multimedia->count());
        $this->assertSame(0, $version->sesiones->sole()->elementos->firstWhere('tipo', 'METAS')->multimedia->count());
        $this->assertSame('AR/DBT/video.mp4', $version->sesiones->sole()->elementos->firstWhere('tipo', 'VIDEO')->multimedia->sole()->ruta);
    }

    public function test_reutiliza_la_version_si_el_contenido_y_los_recursos_no_cambian_y_crea_otra_si_cambian_los_bytes(): void
    {
        $this->crearCatalogo([['type' => 'VIDEO', 'video' => 'AR/DBT/video.mp4']]);
        $this->crearRecurso('AR/DBT/video.mp4', 'primera version');

        $service = app(TratamientoService::class);
        $service->sincronizarCatalogos();
        $service->sincronizarCatalogos();
        $this->assertSame(1, TratamientoVersion::count());

        $this->crearRecurso('AR/DBT/video.mp4', 'segunda version');
        $service->sincronizarCatalogos();
        $this->assertSame(2, TratamientoVersion::count());
    }

    #[DataProvider('elementosInvalidos')]
    public function test_rechaza_contrato_multimedia_invalido(array $elemento, string $mensaje): void
    {
        $this->crearCatalogo([$elemento]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($mensaje);
        app(TratamientoService::class)->sincronizarCatalogos();
    }

    public static function elementosInvalidos(): array
    {
        return [
            'video sin video' => [['type' => 'VIDEO'], 'VIDEO debe tener un video no vacío'],
            'video con audio' => [['type' => 'VIDEO', 'video' => 'AR/DBT/video.mp4', 'audio' => 'AR/DBT/audio.m4a'], 'VIDEO no puede tener recurso audio'],
            'description con video' => [['type' => 'DESCRIPTION', 'video' => 'AR/DBT/video.mp4'], 'DESCRIPTION no puede tener recurso video'],
            'quiz con imagen' => [['type' => 'QUIZ', 'image' => 'AR/DBT/imagen.png'], 'QUIZ no puede tener recurso image'],
            'metas con audio' => [['type' => 'METAS', 'audio' => 'AR/DBT/audio.m4a'], 'METAS no puede tener recurso audio'],
            'tipo desconocido' => [['type' => 'PDF'], "tipo de contenido desconocido 'PDF'"],
            'recurso vacio' => [['type' => 'DESCRIPTION', 'audio' => ''], 'path audio debe ser un string no vacío'],
            'recurso inexistente' => [['type' => 'DESCRIPTION', 'audio' => 'AR/DBT/no-existe.m4a'], "no existe el recurso físico 'AR/DBT/no-existe.m4a'"],
        ];
    }

    public function test_rechaza_un_path_repetido_entre_elementos(): void
    {
        $this->crearCatalogo([
            ['type' => 'DESCRIPTION', 'audio' => 'AR/DBT/audio.m4a'],
            ['type' => 'DESCRIPTION', 'audio' => 'AR/DBT/audio.m4a'],
        ]);
        $this->crearRecurso('AR/DBT/audio.m4a');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("path multimedia 'AR/DBT/audio.m4a' está repetido");
        app(TratamientoService::class)->sincronizarCatalogos();
    }

    /** @param array<int, array<string, mixed>> $elementos */
    private function crearCatalogo(array $elementos): void
    {
        $contenido = ['countries' => ['AR' => [[
            'id' => 'DIABETES', 'name' => 'Diabetes', 'sessions' => [[
                'id' => 99, 'title' => 'Semana declarada', 'subSessions' => [[
                    'id' => 7, 'title' => 'Sesión declarada', 'multimedia' => $elementos,
                ]],
            ]],
        ]]]];
        File::ensureDirectoryExists(storage_path("app/private/$this->tratamientosDirname"));
        File::put(storage_path("app/private/$this->tratamientosDirname/content.json"), json_encode($contenido, JSON_THROW_ON_ERROR));
    }

    private function crearRecurso(string $ruta, string $contenido = 'recurso'): void
    {
        $path = storage_path("app/private/$this->tratamientosDirname/$ruta");
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contenido);
    }
}
