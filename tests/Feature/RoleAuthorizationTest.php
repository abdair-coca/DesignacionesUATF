<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Gestion;
use App\Models\Periodo;
use App\Models\Propuesta;
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

        User::factory()->create([
            'rol' => User::ROL_VICERRECTORADO,
            'carrera_id' => Carrera::factory(),
        ]);
    }

    public function test_la_base_rechaza_director_sin_carrera(): void
    {
        $this->expectException(QueryException::class);

        User::factory()->create([
            'rol' => User::ROL_DIRECTOR_CARRERA,
            'carrera_id' => null,
        ]);
    }

    public function test_vicerrectorado_no_puede_acceder_a_designaciones_de_director(): void
    {
        Gestion::query()->update(['es_actual' => false]);
        $gestion = Gestion::factory()->create(['es_actual' => true]);
        $periodo = Periodo::factory()->create();
        $director = User::factory()->director(Carrera::factory()->create())->create();
        $propuesta = Propuesta::create([
            'carrera_id' => $director->carrera_id,
            'gestion_id' => $gestion->id,
            'periodo_id' => $periodo->id,
            'creado_por' => $director->id,
            'estado' => 'borrador',
        ]);

        $this->actingAs(User::factory()->vicerrectorado()->create())
            ->get('/designaciones')
            ->assertForbidden();

        $this->actingAs(User::factory()->vicerrectorado()->create())
            ->get("/designaciones/{$propuesta->id}/importar")
            ->assertForbidden();
    }
}
