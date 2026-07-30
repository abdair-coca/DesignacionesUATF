<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\Revision;
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

    public function test_director_no_puede_actualizar_designacion_de_otra_carrera_al_alterar_la_url(): void
    {
        $carreraPropia = Carrera::factory()->create();
        $carreraAjena = Carrera::factory()->create();
        $director = User::factory()->director($carreraPropia)->create();
        [$materia, $grupo] = $this->materiaYGrupo($carreraAjena);
        $designacion = Designacion::factory()->create([
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'malla_curricular_id' => $grupo->malla_curricular_id,
            'estado' => 'propuesta',
        ]);

        $this->actingAs($director)
            ->put("/designaciones/{$designacion->id}", $this->payload($materia, $grupo))
            ->assertForbidden();
    }

    public function test_vicerrectorado_no_puede_ver_borrador_no_enviado(): void
    {
        $carrera = Carrera::factory()->create();
        $director = User::factory()->director($carrera)->create();
        $borrador = Revision::create([
            'carrera_id' => $carrera->id,
            'Id_gestion' => Gestion::factory()->create()->id,
            'Id_periodo' => Periodo::factory()->create()->id,
            'solicitado_por' => $director->id,
            'estado' => 'propuesta',
        ]);

        $this->actingAs(User::factory()->vicerrectorado()->create())
            ->get("/revisiones/{$borrador->id}/revisar")
            ->assertForbidden();
    }

    public function test_vicerrectorado_no_puede_manipular_designaciones_ni_importaciones(): void
    {
        $vicerrectorado = User::factory()->vicerrectorado()->create();

        $this->actingAs($vicerrectorado)
            ->postJson('/designaciones/previsualizar-pegado', [
                'Id_gestion' => Gestion::factory()->create()->id,
                'Id_periodo' => Periodo::factory()->create()->id,
                'filas' => [],
            ])
            ->assertForbidden();
    }

    private function materiaYGrupo(Carrera $carrera): array
    {
        $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);

        return [$materia, Grupo::factory()->create(['materia_id' => $materia->id])];
    }

    private function payload(Materia $materia, Grupo $grupo): array
    {
        return [
            'Id_docente' => Docente::factory()->create()->id,
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'Id_gestion' => Gestion::factory()->create()->id,
            'Id_periodo' => Periodo::factory()->create()->id,
            'estado' => 'propuesta',
        ];
    }
}
