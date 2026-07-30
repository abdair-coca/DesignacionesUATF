<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\Revision;
use App\Models\User;
use Tests\TestCase;

class RevisionPendientesViewTest extends TestCase
{
    public function test_admin_ve_pendientes_con_datos(): void
    {
        $admin = User::factory()->vicerrectorado()->create();
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        Revision::create([
            'carrera_id' => $carrera->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'solicitado_por' => User::factory()->create()->id,
            'solicitado_en' => now(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($admin)
            ->get('/revisiones/pendientes')
            ->assertOk();
    }

    public function test_admin_ve_revisar_con_designaciones(): void
    {
        $admin = User::factory()->vicerrectorado()->create();
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $revision = Revision::create([
            'carrera_id' => $carrera->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'solicitado_por' => User::factory()->create()->id,
            'solicitado_en' => now(),
            'estado' => 'pendiente',
        ]);

        $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);
        Designacion::factory()->create([
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $this->actingAs($admin)
            ->get("/revisiones/{$revision->id}/revisar")
            ->assertOk();
    }

    public function test_usuario_normal_no_ve_revisar(): void
    {
        $user = User::factory()->create();
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $revision = Revision::create([
            'carrera_id' => $carrera->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'solicitado_por' => $user->id,
            'solicitado_en' => now(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($user)
            ->get("/revisiones/{$revision->id}/revisar")
            ->assertStatus(403);
    }

    public function test_procesar_requiere_acciones_array(): void
    {
        $admin = User::factory()->vicerrectorado()->create();
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $revision = Revision::create([
            'carrera_id' => $carrera->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'solicitado_por' => $admin->id,
            'solicitado_en' => now(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($admin)
            ->post("/revisiones/{$revision->id}/procesar", [])
            ->assertSessionHasErrors('acciones');
    }
}
