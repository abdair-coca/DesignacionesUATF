<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FrontendAssetsTest extends TestCase
{
    public function test_archivos_de_presentacion_y_fabricas_alpine_residen_en_resources_assets(): void
    {
        $raiz = dirname(__DIR__, 2);
        $rutaPublicaAssets = $raiz.'/public/assets';

        $this->assertFalse(
            is_link($rutaPublicaAssets) || file_exists($rutaPublicaAssets),
            'Los assets no deben publicarse desde public/assets.'
        );

        $contratos = [
            'css/tailwind.generated.css' => [
                '.flex{display:flex}',
                '.min-h-screen{min-height:100vh}',
                '#2d353c',
                'Instrument Sans',
            ],
            'css/layouts/app.css' => ['[x-cloak]', '.login-banner', '.sidebar-profile-banner'],
            'css/layouts/header.css' => ['.odiseo-top-header', '.top-menu-mark-read'],
            'css/shared/designaciones-detalle.css' => ['.designaciones-detail', '@media print'],
            'css/shared/designaciones-lista.css' => ['.designaciones-screen'],
            'css/shared/modales.css' => ['.app-modal', '.designacion-confirmacion-modal'],
            'css/designaciones/pdf.css' => ['@page', 'body.designaciones-pdf', '.pdf-cell-padded'],
            'js/designaciones/carrera.js' => ['window.designacionesCarrera', 'grupoSiguienteMateria', 'this.docenteSeleccionado = docente'],
            'js/designaciones/lista.js' => ['window.designacionesLista', 'this.contextoActual.gestion'],
            'js/vicerrectorado/designaciones/detalle.js' => [
                'window.vicerrectoradoRevision',
                'Las decisiones confirmadas se guardaron correctamente.',
            ],
        ];

        foreach ($contratos as $asset => $fragmentos) {
            $ruta = $raiz.'/resources/assets/'.$asset;
            $this->assertFileExists($ruta, "No existe el asset {$asset}.");

            $contenido = file_get_contents($ruta);
            $this->assertIsString($contenido);

            foreach ($fragmentos as $fragmento) {
                $this->assertStringContainsString($fragmento, $contenido, "Falta {$fragmento} en {$asset}.");
            }
        }

        $referencias = [
            'resources/views/layouts/app.blade.php' => [
                'resources/assets/css/layouts/app.css',
                'resources/assets/css/layouts/header.css',
                'resources/assets/css/shared/modales.css',
                'resources/assets/css/tailwind.generated.css',
            ],
            'resources/views/designaciones/lista.blade.php' => [
                'resources/assets/css/shared/designaciones-lista.css',
                'resources/assets/js/designaciones/lista.js',
            ],
            'resources/views/designaciones/carrera.blade.php' => [
                'resources/assets/css/shared/designaciones-detalle.css',
                'resources/assets/js/designaciones/carrera.js',
            ],
            'resources/views/vicerrectorado/designaciones/index.blade.php' => [
                'resources/assets/css/shared/designaciones-lista.css',
            ],
            'resources/views/vicerrectorado/designaciones/detalle.blade.php' => [
                'resources/assets/css/shared/designaciones-detalle.css',
                'resources/assets/js/vicerrectorado/designaciones/detalle.js',
            ],
            'resources/views/designaciones/pdf.blade.php' => [
                "resource_path('assets/css/designaciones/pdf.css')",
            ],
        ];

        foreach ($referencias as $vista => $assets) {
            $contenido = file_get_contents(dirname(__DIR__, 2).'/'.$vista);
            $this->assertIsString($contenido);

            foreach ($assets as $asset) {
                $this->assertStringContainsString($asset, $contenido, "La vista {$vista} no referencia {$asset}.");
            }
        }
    }

    public function test_tailwind_es_local_y_alpine_se_carga_despues_de_los_scripts_de_vista(): void
    {
        $layout = file_get_contents(dirname(__DIR__, 2).'/resources/views/layouts/app.blade.php');
        $this->assertIsString($layout);
        $this->assertStringNotContainsString('cdn.tailwindcss.com', $layout);

        $stackScripts = strpos($layout, "@stack('scripts')");
        $alpine = strpos($layout, 'cdn.jsdelivr.net/npm/alpinejs');

        $this->assertIsInt($stackScripts);
        $this->assertIsInt($alpine);
        $this->assertLessThan($alpine, $stackScripts);
    }
}
