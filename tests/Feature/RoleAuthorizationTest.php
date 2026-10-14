<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\User;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    public function test_la_base_rechaza_roles_fuera_del_catalogo_permitido(): void
    {
        $this->expectException(QueryException::class);
        User::factory()->create(['rol' => 'administrador']);
    }

    public function test_la_base_exige_carrera_para_director_y_la_prohibe_para_vicerrectorado(): void
    {
        $this->expectException(QueryException::class);
        User::factory()->create(['rol' => User::ROL_VICERRECTORADO, 'carrera_id' => Carrera::factory()]);
    }

    public function test_la_base_rechaza_director_sin_carrera(): void
    {
        $this->expectException(QueryException::class);
        User::factory()->create(['rol' => User::ROL_DIRECTOR_CARRERA, 'carrera_id' => null]);
    }

    public function test_la_base_acepta_los_roles_con_su_alcance_exclusivo(): void
    {
        $carrera = Carrera::factory()->create();

        $director = User::factory()->director($carrera)->create();
        $decanatura = User::factory()->decanatura(73001)->create();
        $vicerrectorado = User::factory()->vicerrectorado()->create();

        $this->assertSame(User::ROL_DIRECTOR_CARRERA, $director->rol);
        $this->assertNull($director->facultad_id);
        $this->assertSame(User::ROL_DECANATURA, $decanatura->rol);
        $this->assertNull($decanatura->carrera_id);
        $this->assertSame(73001, $decanatura->facultad_id);
        $this->assertSame(User::ROL_VICERRECTORADO, $vicerrectorado->rol);
        $this->assertNull($vicerrectorado->carrera_id);
        $this->assertNull($vicerrectorado->facultad_id);
    }

    public function test_la_base_rechaza_decanatura_sin_facultad(): void
    {
        $this->expectException(QueryException::class);
        User::factory()->decanatura(73001)->create(['facultad_id' => null]);
    }

    public function test_la_base_rechaza_decanatura_con_carrera(): void
    {
        $this->expectException(QueryException::class);
        User::factory()->decanatura(73001)->create(['carrera_id' => Carrera::factory()]);
    }

    public function test_la_base_rechaza_director_con_facultad(): void
    {
        $this->expectException(QueryException::class);
        User::factory()->director()->create(['facultad_id' => 73001]);
    }

    public function test_la_base_rechaza_vicerrectorado_con_facultad(): void
    {
        $this->expectException(QueryException::class);
        User::factory()->vicerrectorado()->create(['facultad_id' => 73001]);
    }

    public function test_la_base_acepta_personal_sin_alcance(): void
    {
        $personal = User::factory()->personal()->create();

        $this->assertSame(User::ROL_PERSONAL, $personal->rol);
        $this->assertNull($personal->carrera_id);
        $this->assertNull($personal->facultad_id);
        $this->assertTrue($personal->esPersonal());
        $this->assertFalse($personal->esVicerrectorado());
    }

    public function test_la_base_rechaza_personal_con_carrera(): void
    {
        $this->expectException(QueryException::class);
        User::factory()->personal()->create(['carrera_id' => Carrera::factory()]);
    }

    public function test_la_base_rechaza_personal_con_facultad(): void
    {
        $this->expectException(QueryException::class);
        User::factory()->personal()->create(['facultad_id' => 73001]);
    }

    public function test_vicerrectorado_no_puede_acceder_a_designaciones_de_carrera(): void
    {
        $this->actingAs(User::factory()->vicerrectorado()->create())->get('/designaciones')->assertForbidden();
    }

    public function test_personal_no_accede_a_las_designaciones_de_carrera(): void
    {
        $personal = User::factory()->personal()->create();

        $this->actingAs($personal)->get('/designaciones')->assertForbidden();
        $this->actingAs($personal)->get('/designaciones/1')->assertForbidden();
        $this->actingAs($personal)->get('/designaciones/1/pdf')->assertForbidden();
        $this->actingAs($personal)->get('/designaciones/docentes/buscar?q=ana')->assertForbidden();
        $this->actingAs($personal)->post('/designaciones', [])->assertForbidden();
        $this->actingAs($personal)->post('/designaciones/1', [])->assertForbidden();
        $this->actingAs($personal)->post('/designaciones/1/detalle', [])->assertForbidden();
        $this->actingAs($personal)->post('/designaciones/1/carta-pdf', [])->assertForbidden();
    }

    public function test_personal_no_accede_a_la_bandeja_de_vicerrectorado(): void
    {
        $personal = User::factory()->personal()->create();

        $this->actingAs($personal)->get('/vicerrectorado/designaciones')->assertForbidden();
        $this->actingAs($personal)->get('/vicerrectorado/designaciones/1')->assertForbidden();
        $this->actingAs($personal)->get('/vicerrectorado/designaciones/1/pdf')->assertForbidden();
        $this->actingAs($personal)->post('/vicerrectorado/designaciones/1/decisiones', [])->assertForbidden();
        $this->actingAs($personal)->post('/vicerrectorado/designaciones/1/estado', [])->assertForbidden();
    }

    public function test_otros_roles_no_acceden_a_las_designaciones_de_personal(): void
    {
        $carrera = Carrera::factory()->create();
        $usuarios = [
            User::factory()->director($carrera)->create(),
            User::factory()->decanatura(73001)->create(),
            User::factory()->vicerrectorado()->create(),
        ];

        foreach ($usuarios as $usuario) {
            $this->actingAs($usuario)->get('/personal/designaciones')->assertForbidden();
            $this->actingAs($usuario)->get('/personal/designaciones/1')->assertForbidden();
            $this->actingAs($usuario)->get('/personal/designaciones/1/pdf')->assertForbidden();
        }
    }
}
