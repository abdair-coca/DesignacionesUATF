<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase;

class FrontendAssetsServingTest extends TestCase
{
    public function test_css_and_javascript_assets_are_served_from_the_resources_url(): void
    {
        $this->get('/resources/assets/css/tailwind.generated.css')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8')
            ->assertSee('.flex{display:flex}', false);

        $this->get('/resources/assets/css/layouts/app.css')
            ->assertOk()
            ->assertSee('[x-cloak]', false);

        $this->get('/resources/assets/js/designaciones/lista.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/javascript; charset=UTF-8')
            ->assertSee('window.designacionesLista', false);
    }

    public function test_assets_route_cannot_read_files_outside_resources_assets(): void
    {
        $this->get('/resources/assets/../../.env')->assertNotFound();
        $this->get('/resources/assets/.env')->assertNotFound();
    }
}
