<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GuestAccessTest extends TestCase
{
    public static function rutasGetProtegidas(): array
    {
        return [
            'raiz' => ['/'],
            'designaciones.index' => ['/designaciones'],
            'designaciones.lista' => ['/designaciones/lista'],
            'revisiones.pendientes' => ['/revisiones/pendientes'],
        ];
    }

    #[DataProvider('rutasGetProtegidas')]
    public function test_invitado_redirigido_login_en_get(string $uri): void
    {
        $this->get($uri)->assertRedirect('/login');
    }

    public function test_invitado_no_puede_acceder_login_si_ya_autenticado(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect();
    }

    public function test_login_muestra_formulario(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Email')
            ->assertSee('Ingresar');
    }

    public function test_login_valido_redirige(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret')]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret',
        ])->assertRedirect('/designaciones');

        $this->assertAuthenticated();
    }

    public function test_login_invalido_rechaza(): void
    {
        User::factory()->create(['email' => 'test@test.com', 'password' => bcrypt('correct')]);

        $this->post('/login', [
            'email' => 'test@test.com',
            'password' => 'wrong',
        ])->assertSessionHasErrors();

        $this->assertGuest();
    }

    public function test_logout_cierra_sesion(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
