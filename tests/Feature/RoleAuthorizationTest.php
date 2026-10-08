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

    public function test_vicerrectorado_no_puede_acceder_a_designaciones_de_carrera(): void
    {
        $this->actingAs(User::factory()->vicerrectorado()->create())->get('/designaciones')->assertForbidden();
    }
}
