<?php

namespace Tests\Feature;

use App\Models\Designacion;
use App\Models\Grupo;
use App\Models\MallaCurricular;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicNormalizationVerificationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_approves_a_consistent_academic_normalization(): void
    {
        $malla = MallaCurricular::factory()->create();
        $grupo = Grupo::factory()->create([
            'materia_id' => $malla->materia_id,
            'malla_curricular_id' => $malla->id,
            'codigo' => '1',
        ]);

        Designacion::factory()->create([
            'Id_materia' => $malla->materia_id,
            'Id_grupo' => $grupo->id,
            'malla_curricular_id' => $malla->id,
        ]);

        $this->artisan('academico:verificar-normalizacion')
            ->expectsOutputToContain('Verificacion aprobada')
            ->assertExitCode(0);
    }
}
