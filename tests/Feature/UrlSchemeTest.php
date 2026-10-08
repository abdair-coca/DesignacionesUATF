<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;

/**
 * BUG-2026-09-03-3: `URL::forceScheme('https')` generaba URLs `https` aunque el
 * servidor solo sirve HTTP. El `<form>` de edición de cabecera apuntaba a
 * `https://...`, el navegador posteaba a HTTPS, el servidor respondía un 301 a
 * HTTP y el POST se convertía en GET: la observación nunca se guardaba.
 *
 * @see docs/testing/BUG_REPORTS/BUG-2026-09-03-3-esquema-https-forzado.md
 */
class UrlSchemeTest extends BaseTestCase
{
    public function test_las_urls_generadas_usan_el_esquema_http_del_request(): void
    {
        $request = Request::create('http://asignaciones.uatf.edu.bo/designaciones/508', 'GET');
        $this->app->instance('request', $request);
        app('url')->setRequest($request);

        $url = route('designaciones.update', ['id' => 508]);

        $this->assertStringStartsWith('http://', $url);
        $this->assertFalse(str_starts_with($url, 'https://'));
    }
}
