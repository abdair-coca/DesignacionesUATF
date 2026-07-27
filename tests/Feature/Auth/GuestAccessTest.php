<?php

namespace Tests\Feature\Auth;

use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GuestAccessTest extends TestCase
{
    public static function rutasGetProtegidas(): array
    {
        return [
            'raiz' => ['/'],
            'dashboard' => ['/dashboard'],
            'carreras.index' => ['/carreras'],
            'carreras.create' => ['/carreras/create'],
            'materias.index' => ['/materias'],
            'materias.create' => ['/materias/create'],
            'grupos.index' => ['/grupos'],
            'grupos.create' => ['/grupos/create'],
            'docentes.index' => ['/docentes'],
            'docentes.create' => ['/docentes/create'],
            'gestiones.index' => ['/gestiones'],
            'gestiones.create' => ['/gestiones/create'],
            'periodos.index' => ['/periodos'],
            'periodos.create' => ['/periodos/create'],
            'designaciones.index' => ['/designaciones'],
            'designaciones.lista' => ['/designaciones/lista'],
            'designaciones.create' => ['/designaciones/create'],
            'revisiones.pendientes' => ['/revisiones/pendientes'],
        ];
    }

    #[DataProvider('rutasGetProtegidas')]
    public function test_invitado_redirigido_login_en_get(string $uri): void
    {
        $this->get($uri)->assertRedirect('/login');
    }

    public function test_invitado_no_puede_crear_carrera(): void
    {
        $this->post('/carreras', ['sigla' => 'X', 'nombre' => 'X'])
            ->assertRedirect('/login');
    }

    public function test_invitado_no_puede_crear_materia(): void
    {
        $carrera = Carrera::factory()->create();
        $this->post('/materias', ['sigla' => 'X', 'nombre' => 'X', 'carrera_id' => $carrera->id])
            ->assertRedirect('/login');
    }

    public function test_invitado_no_puede_crear_grupo(): void
    {
        $materia = Materia::factory()->create();
        $this->post('/grupos', ['materia_id' => $materia->id, 'codigo' => 'A'])
            ->assertRedirect('/login');
    }

    public function test_invitado_no_puede_crear_docente(): void
    {
        $this->post('/docentes', ['nombre' => 'X', 'ci' => '123'])
            ->assertRedirect('/login');
    }

    public function test_invitado_no_puede_crear_gestion(): void
    {
        $this->post('/gestiones', ['nombre' => '2027'])
            ->assertRedirect('/login');
    }

    public function test_invitado_no_puede_crear_periodo(): void
    {
        $this->post('/periodos', ['nombre' => '3'])
            ->assertRedirect('/login');
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
