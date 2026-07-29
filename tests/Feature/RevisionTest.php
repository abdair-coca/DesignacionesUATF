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
use Tests\TestCase;

class RevisionTest extends TestCase
{
    public function test_usuario_envia_revision_con_exito(): void
    {
        $usuario = User::factory()->create();
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::firstOrCreate(['nombre' => (string) date('Y')]);
        $periodo = Periodo::factory()->create();

        $response = $this->actingAs($usuario)
            ->postJson('/revisiones/solicitar', [
                'carrera_id' => $carrera->id,
                'Id_gestion' => $gestion->id,
                'Id_periodo' => $periodo->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $revisionId = $response->json('revision_id');
        $this->assertNotNull($revisionId);

        $this->assertDatabaseHas('revisiones', [
            'id' => $revisionId,
            'carrera_id' => $carrera->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'pendiente',
            'solicitado_por' => $usuario->id,
        ]);

        $revision = Revision::find($revisionId);
        $this->assertNotNull($revision->solicitado_en);
    }

    public function test_no_permite_solicitar_revision_de_gestion_pasada(): void
    {
        $usuario = User::factory()->create();
        $carrera = Carrera::factory()->create();
        $gestionPasada = Gestion::firstOrCreate(['nombre' => '2024']);
        $periodo = Periodo::factory()->create();

        $response = $this->actingAs($usuario)
            ->postJson('/revisiones/solicitar', [
                'carrera_id' => $carrera->id,
                'Id_gestion' => $gestionPasada->id,
                'Id_periodo' => $periodo->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Únicamente se pueden enviar a revisión las designaciones correspondientes a la gestión actual (2026).',
            ]);
    }

    public function test_no_permite_dos_revisiones_pendientes_misma_carrera(): void
    {
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::firstOrCreate(['nombre' => (string) date('Y')]);
        $periodo = Periodo::factory()->create();
        $solicitante = User::factory()->create();

        Revision::create([
            'carrera_id' => $carrera->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'solicitado_por' => $solicitante->id,
            'solicitado_en' => now(),
            'estado' => 'pendiente',
        ]);

        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)
            ->postJson('/revisiones/solicitar', [
                'carrera_id' => $carrera->id,
                'Id_gestion' => $gestion->id,
                'Id_periodo' => $periodo->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Ya hay una revisión pendiente para esta carrera.',
            ]);
    }

    public function test_admin_aprueba_designaciones_en_lote(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $materia1 = Materia::factory()->create(['carrera_id' => $carrera->id]);
        $grupo1 = Grupo::factory()->create(['materia_id' => $materia1->id]);
        $designacion1 = Designacion::factory()->create([
            'Id_materia' => $materia1->id,
            'Id_grupo' => $grupo1->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $materia2 = Materia::factory()->create(['carrera_id' => $carrera->id]);
        $grupo2 = Grupo::factory()->create(['materia_id' => $materia2->id]);
        $designacion2 = Designacion::factory()->create([
            'Id_materia' => $materia2->id,
            'Id_grupo' => $grupo2->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $revision = Revision::create([
            'carrera_id' => $carrera->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'solicitado_por' => User::factory()->create()->id,
            'solicitado_en' => now(),
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/revisiones/{$revision->id}/procesar", [
                'acciones' => [
                    ['id' => $designacion1->id, 'accion' => 'aprobar'],
                    ['id' => $designacion2->id, 'accion' => 'aprobar'],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'procesadas' => 2,
            ]);

        $this->assertDatabaseHas('designaciones', [
            'id' => $designacion1->id,
            'estado' => 'aprobada',
            'aprobado_por' => $admin->id,
        ]);

        $this->assertDatabaseHas('designaciones', [
            'id' => $designacion2->id,
            'estado' => 'aprobada',
            'aprobado_por' => $admin->id,
        ]);
    }

    public function test_admin_rechaza_designaciones_en_lote(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $materia1 = Materia::factory()->create(['carrera_id' => $carrera->id]);
        $grupo1 = Grupo::factory()->create(['materia_id' => $materia1->id]);
        $designacion1 = Designacion::factory()->create([
            'Id_materia' => $materia1->id,
            'Id_grupo' => $grupo1->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $materia2 = Materia::factory()->create(['carrera_id' => $carrera->id]);
        $grupo2 = Grupo::factory()->create(['materia_id' => $materia2->id]);
        $designacion2 = Designacion::factory()->create([
            'Id_materia' => $materia2->id,
            'Id_grupo' => $grupo2->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $revision = Revision::create([
            'carrera_id' => $carrera->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'solicitado_por' => User::factory()->create()->id,
            'solicitado_en' => now(),
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/revisiones/{$revision->id}/procesar", [
                'acciones' => [
                    ['id' => $designacion1->id, 'accion' => 'rechazar', 'motivo_rechazo' => 'Excede horas del docente'],
                    ['id' => $designacion2->id, 'accion' => 'rechazar', 'motivo_rechazo' => 'Conflicto de materia'],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'procesadas' => 2,
            ]);

        $this->assertDatabaseHas('designaciones', [
            'id' => $designacion1->id,
            'estado' => 'rechazada',
            'motivo_rechazo' => 'Excede horas del docente',
        ]);

        $this->assertDatabaseHas('designaciones', [
            'id' => $designacion2->id,
            'estado' => 'rechazada',
            'motivo_rechazo' => 'Conflicto de materia',
        ]);
    }

    public function test_admin_completa_revision(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
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

        $response = $this->actingAs($admin)
            ->postJson("/revisiones/{$revision->id}/completar");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('revisiones', [
            'id' => $revision->id,
            'estado' => 'revisado',
            'revisado_por' => $admin->id,
        ]);

        $revision->refresh();
        $this->assertNotNull($revision->revisado_en);
    }

    public function test_usuario_normal_no_ve_revisiones_pendientes(): void
    {
        $usuario = User::factory()->create(['is_admin' => false]);
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $revision = Revision::create([
            'carrera_id' => $carrera->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'solicitado_por' => $usuario->id,
            'solicitado_en' => now(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($usuario)
            ->get('/revisiones/pendientes')
            ->assertStatus(403);

        $this->actingAs($usuario)
            ->get("/revisiones/{$revision->id}/revisar")
            ->assertStatus(403);
    }

    public function test_admin_puede_ver_pendientes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->get('/revisiones/pendientes');

        $response->assertStatus(200);
    }

    public function test_admin_completa_revision_y_procesa_acciones_pendientes_en_un_solo_paso(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $materia1 = Materia::factory()->create(['carrera_id' => $carrera->id]);
        $grupo1 = Grupo::factory()->create(['materia_id' => $materia1->id]);
        $designacion1 = Designacion::factory()->create([
            'Id_materia' => $materia1->id,
            'Id_grupo' => $grupo1->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $materia2 = Materia::factory()->create(['carrera_id' => $carrera->id]);
        $grupo2 = Grupo::factory()->create(['materia_id' => $materia2->id]);
        $designacion2 = Designacion::factory()->create([
            'Id_materia' => $materia2->id,
            'Id_grupo' => $grupo2->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $revision = Revision::create([
            'carrera_id' => $carrera->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'solicitado_por' => User::factory()->create()->id,
            'solicitado_en' => now(),
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/revisiones/{$revision->id}/completar", [
                'acciones' => [
                    ['id' => $designacion1->id, 'accion' => 'aprobar'],
                    ['id' => $designacion2->id, 'accion' => 'rechazar'],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('designaciones', [
            'id' => $designacion1->id,
            'estado' => 'aprobada',
            'aprobado_por' => $admin->id,
        ]);

        $this->assertDatabaseHas('designaciones', [
            'id' => $designacion2->id,
            'estado' => 'rechazada',
        ]);

        $this->assertDatabaseHas('revisiones', [
            'id' => $revision->id,
            'estado' => 'observado',
            'revisado_por' => $admin->id,
        ]);
    }

    public function test_usuario_retira_revision_y_vuelve_a_estado_propuesta(): void
    {
        $usuario = User::factory()->create(['is_admin' => false]);
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $revision = Revision::create([
            'carrera_id' => $carrera->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'solicitado_por' => $usuario->id,
            'solicitado_en' => now(),
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($usuario)
            ->postJson("/revisiones/{$revision->id}/retirar");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('revisiones', [
            'id' => $revision->id,
            'estado' => 'propuesta',
            'solicitado_en' => null,
        ]);
    }

    public function test_crear_propuesta_crea_registro_nuevo_independiente(): void
    {
        $carrera = Carrera::factory()->create();
        $usuario = User::factory()->create(['carrera_id' => $carrera->id]);
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $borradorInicial = Revision::create([
            'carrera_id' => $carrera->id,
            'descripcion' => 'Propuesta inicial',
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'solicitado_por' => $usuario->id,
            'estado' => 'propuesta',
        ]);

        $response = $this->actingAs($usuario)
            ->postJson('/revisiones/crear-propuesta', [
                'descripcion' => 'Propuesta segunda',
                'gestion_id' => $gestion->id,
                'periodo_id' => $periodo->id,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('revisiones', [
            'id' => $borradorInicial->id,
            'descripcion' => 'Propuesta inicial',
        ]);

        $this->assertDatabaseHas('revisiones', [
            'descripcion' => 'Propuesta segunda',
        ]);

        $this->assertEquals(2, Revision::where('carrera_id', $carrera->id)->count());
    }

    public function test_copiar_anterior_guarda_descripcion_personalizada(): void
    {
        $carrera = Carrera::factory()->create();
        $usuario = User::factory()->create(['carrera_id' => $carrera->id]);
        $gestionOrigen = Gestion::factory()->create();
        $gestionDestino = Gestion::factory()->create();
        $periodoOrigen = Periodo::factory()->create();
        $periodoDestino = Periodo::factory()->create();

        $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);
        $docente = Docente::factory()->create();

        Designacion::factory()->create([
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'Id_docente' => $docente->id,
            'Id_gestion' => $gestionOrigen->id,
            'Id_periodo' => $periodoOrigen->id,
        ]);

        $response = $this->actingAs($usuario)
            ->postJson("/designaciones/carrera/{$carrera->id}/copiar-anterior", [
                'origen_gestion_id' => $gestionOrigen->id,
                'origen_periodo_id' => $periodoOrigen->id,
                'destino_gestion_id' => $gestionDestino->id,
                'destino_periodo_id' => $periodoDestino->id,
                'descripcion' => 'Propuesta Copiada Mi Personalizada',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('revisiones', [
            'carrera_id' => $carrera->id,
            'Id_gestion' => $gestionDestino->id,
            'Id_periodo' => $periodoDestino->id,
            'descripcion' => 'Propuesta Copiada Mi Personalizada',
            'estado' => 'propuesta',
        ]);
    }

    public function test_crear_multiples_propuestas_genera_registros_independientes(): void
    {
        $carrera = Carrera::factory()->create();
        $usuario = User::factory()->create(['carrera_id' => $carrera->id]);
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $this->actingAs($usuario)->postJson('/revisiones/crear-propuesta', [
            'descripcion' => 'Propuesta Nro 1',
            'gestion_id' => $gestion->id,
            'periodo_id' => $periodo->id,
        ])->assertStatus(200);

        $this->actingAs($usuario)->postJson('/revisiones/crear-propuesta', [
            'descripcion' => 'Propuesta Nro 2',
            'gestion_id' => $gestion->id,
            'periodo_id' => $periodo->id,
        ])->assertStatus(200);

        $this->assertEquals(2, Revision::where('carrera_id', $carrera->id)->count());
    }

    public function test_eliminar_propuesta_no_oficial_exitoso(): void
    {
        $carrera = Carrera::factory()->create();
        $usuario = User::factory()->create(['carrera_id' => $carrera->id]);
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $revision = Revision::create([
            'carrera_id' => $carrera->id,
            'descripcion' => 'Propuesta a eliminar',
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'solicitado_por' => $usuario->id,
            'estado' => 'propuesta',
        ]);

        $response = $this->actingAs($usuario)->deleteJson("/revisiones/{$revision->id}");

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseMissing('revisiones', ['id' => $revision->id]);
    }

    public function test_eliminar_propuesta_oficial_falla(): void
    {
        $carrera = Carrera::factory()->create();
        $usuario = User::factory()->create(['carrera_id' => $carrera->id]);
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $revision = Revision::create([
            'carrera_id' => $carrera->id,
            'descripcion' => 'Propuesta oficial',
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'solicitado_por' => $usuario->id,
            'estado' => 'revisado',
        ]);

        $response = $this->actingAs($usuario)->deleteJson("/revisiones/{$revision->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('revisiones', ['id' => $revision->id]);
    }

    public function test_solicitar_por_revision_id_especifico(): void
    {
        $carrera = Carrera::factory()->create();
        $usuario = User::factory()->create(['carrera_id' => $carrera->id]);
        $gestion = Gestion::firstOrCreate(['nombre' => (string) date('Y')]);
        $periodo = Periodo::factory()->create();

        $rev1 = Revision::create([
            'carrera_id' => $carrera->id,
            'descripcion' => 'Propuesta Nro 1',
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'solicitado_por' => $usuario->id,
            'estado' => 'propuesta',
        ]);

        $rev2 = Revision::create([
            'carrera_id' => $carrera->id,
            'descripcion' => 'Propuesta Nro 2',
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'solicitado_por' => $usuario->id,
            'estado' => 'propuesta',
        ]);

        $response = $this->actingAs($usuario)->postJson('/revisiones/solicitar', [
            'carrera_id' => $carrera->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'revision_id' => $rev2->id,
            'descripcion' => 'Propuesta Nro 2 Enviada',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('revisiones', [
            'id' => $rev2->id,
            'estado' => 'pendiente',
            'descripcion' => 'Propuesta Nro 2 Enviada',
        ]);

        $this->assertDatabaseHas('revisiones', [
            'id' => $rev1->id,
            'estado' => 'propuesta',
        ]);
    }

    public function test_completar_requiere_decision_explicita_para_aprobar_o_devolver(): void
    {
        $carrera = Carrera::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $revision = Revision::create([
            'carrera_id' => $carrera->id,
            'descripcion' => 'Propuesta a Evaluar',
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'solicitado_por' => $admin->id,
            'estado' => 'pendiente',
        ]);

        $responseAprobar = $this->actingAs($admin)->postJson("/revisiones/{$revision->id}/completar", [
            'decision' => 'aprobar_todo',
        ]);

        $responseAprobar->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('revisiones', [
            'id' => $revision->id,
            'estado' => 'revisado',
        ]);
    }
}
