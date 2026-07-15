<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GuestRedirectTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return array<string, array{0: string}>
     */
    public static function rutasProtegidasGet(): array
    {
        return [
            'raiz' => ['/'],
            'dashboard' => ['/dashboard'],
            'carreras.index' => ['/carreras'],
            'materias.index' => ['/materias'],
            'grupos.index' => ['/grupos'],
            'docentes.index' => ['/docentes'],
            'gestiones.index' => ['/gestiones'],
            'periodos.index' => ['/periodos'],
            'designaciones.index' => ['/designaciones'],
            'designaciones.lista' => ['/designaciones/lista'],
            'designaciones.copiar' => ['/designaciones/copiar'],
        ];
    }

    #[DataProvider('rutasProtegidasGet')]
    public function test_invitado_es_redirigido_a_login(string $uri): void
    {
        $this->get($uri)->assertRedirect('/login');
    }

    public function test_invitado_no_puede_crear_una_carrera(): void
    {
        $this->post('/carreras', ['sigla' => 'X', 'nombre' => 'X'])
            ->assertRedirect('/login');
    }
}
