<?php

namespace Tests\Feature\Catalogos;

use App\Models\Carrera;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\User;
use Tests\TestCase;

class CatalogValidationTest extends TestCase
{
    public function test_carrera_requiere_sigla(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/carreras', ['nombre' => 'Test'])
            ->assertSessionHasErrors('sigla');
    }

    public function test_carrera_requiere_nombre(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/carreras', ['sigla' => 'TST'])
            ->assertSessionHasErrors('nombre');
    }

    public function test_carrera_sigla_unica(): void
    {
        Carrera::factory()->create(['sigla' => 'ABC']);

        $this->actingAs(User::factory()->create())
            ->post('/carreras', ['sigla' => 'ABC', 'nombre' => 'Otra'])
            ->assertSessionHasErrors('sigla');
    }

    public function test_docente_ci_unica(): void
    {
        Docente::factory()->create(['ci' => '12345']);

        $this->actingAs(User::factory()->create())
            ->post('/docentes', ['nombre' => 'Juan', 'ci' => '12345'])
            ->assertSessionHasErrors('ci');
    }

    public function test_grupo_codigo_unico_por_materia(): void
    {
        $materia = Materia::factory()->create();
        Grupo::factory()->create(['materia_id' => $materia->id, 'codigo' => 'A']);

        $this->actingAs(User::factory()->create())
            ->post('/grupos', ['materia_id' => $materia->id, 'codigo' => 'A', 'estado' => 'habilitado'])
            ->assertSessionHasErrors('codigo');
    }

    public function test_grupo_mismo_codigo_en_diferente_materia_permite(): void
    {
        $materia1 = Materia::factory()->create();
        $materia2 = Materia::factory()->create();
        Grupo::factory()->create(['materia_id' => $materia1->id, 'codigo' => 'A']);

        $this->actingAs(User::factory()->create())
            ->post('/grupos', ['materia_id' => $materia2->id, 'codigo' => 'A', 'estado' => 'habilitado'])
            ->assertRedirect('/grupos');
    }

    public function test_materia_requiere_carrera_existente(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/materias', ['sigla' => 'INF101', 'nombre' => 'Test', 'carrera_id' => 9999])
            ->assertSessionHasErrors('carrera_id');
    }

    public function test_gestion_nombre_unico(): void
    {
        Gestion::factory()->create(['nombre' => '2026']);

        $this->actingAs(User::factory()->create())
            ->post('/gestiones', ['nombre' => '2026'])
            ->assertSessionHasErrors('nombre');
    }

    public function test_periodo_nombre_unico(): void
    {
        Periodo::factory()->create(['nombre' => '1']);

        $this->actingAs(User::factory()->create())
            ->post('/periodos', ['nombre' => '1'])
            ->assertSessionHasErrors('nombre');
    }
}
