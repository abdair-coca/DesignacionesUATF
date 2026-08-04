<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\Propuesta;
use App\Models\PropuestaVersion;
use App\Models\User;
use App\Notifications\PropuestaActualizadaNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificacionPropuestaTest extends TestCase
{
    public function test_envio_notifica_a_vicerrectorado_y_al_abrir_se_marca_leida(): void
    {
        [$director, $vicerrectorado, $propuesta] = $this->propuestaLista();

        $this->actingAs($director)
            ->post("/designaciones/{$propuesta->id}/enviar")
            ->assertRedirect();

        $version = PropuestaVersion::where('propuesta_id', $propuesta->id)->firstOrFail();
        $notificacion = DatabaseNotification::where('notifiable_id', $vicerrectorado->id)->firstOrFail();

        $this->assertSame('enviada', $notificacion->data['evento']);
        $this->assertSame($version->id, $notificacion->data['version_id']);

        $this->actingAs($vicerrectorado)
            ->get('/notificaciones')
            ->assertOk()
            ->assertSee('enviada a revision')
            ->assertSee('Ver todo');

        $this->actingAs($director)
            ->post("/notificaciones/{$notificacion->id}/leer")
            ->assertForbidden();

        $this->actingAs($vicerrectorado)
            ->post("/notificaciones/{$notificacion->id}/leer")
            ->assertRedirect("/revisiones/{$version->id}/revisar");

        $this->assertNotNull($notificacion->fresh()->read_at);
    }

    public function test_revision_notifica_al_director_duegno(): void
    {
        [$director, $vicerrectorado, $propuesta] = $this->propuestaLista();
        $this->actingAs($director)->post("/designaciones/{$propuesta->id}/enviar");
        $version = PropuestaVersion::where('propuesta_id', $propuesta->id)->firstOrFail();

        $this->actingAs($vicerrectorado)
            ->post("/revisiones/{$version->id}/decidir", ['modo' => 'aprobar_todo'])
            ->assertRedirect('/revisiones/pendientes');

        $notificacion = DatabaseNotification::where('notifiable_id', $director->id)->firstOrFail();
        $this->assertSame('aprobada_final', $notificacion->data['evento']);
        $this->assertSame($propuesta->id, $notificacion->data['propuesta_id']);
    }

    public function test_revision_parcial_envia_una_notificacion_resumida_al_director(): void
    {
        [$director, $vicerrectorado, $propuesta] = $this->propuestaLista(2);
        $this->actingAs($director)->post("/designaciones/{$propuesta->id}/enviar");
        $version = PropuestaVersion::where('propuesta_id', $propuesta->id)->with('designaciones')->firstOrFail();

        $this->actingAs($vicerrectorado)
            ->post("/revisiones/{$version->id}/decidir", [
                'modo' => 'decidir_filas',
                'observacion_general' => 'Corregir una fila.',
                'decisiones' => [
                    ['snapshot_id' => $version->designaciones[0]->id, 'decision' => 'observada'],
                    ['snapshot_id' => $version->designaciones[1]->id, 'decision' => 'aprobada'],
                ],
            ])
            ->assertRedirect('/revisiones/pendientes');

        $notificaciones = DatabaseNotification::where('notifiable_id', $director->id)->get();

        $this->assertCount(1, $notificaciones);
        $this->assertSame('observada', $notificaciones->first()->data['evento']);
        $this->assertSame(1, $notificaciones->first()->data['resumen']['filas_observadas']);
        $this->assertSame(1, $notificaciones->first()->data['resumen']['filas_aprobadas']);
        $this->assertStringContainsString('y filas aprobadas', $notificaciones->first()->data['titulo']);
    }

    public function test_misma_notificacion_no_se_duplica_para_un_destinatario(): void
    {
        [$director, $vicerrectorado, $propuesta] = $this->propuestaLista();
        $this->actingAs($director)->post("/designaciones/{$propuesta->id}/enviar");
        $version = PropuestaVersion::where('propuesta_id', $propuesta->id)->firstOrFail();

        $vicerrectorado->notify(new PropuestaActualizadaNotification($version, 'enviada'));
        $vicerrectorado->notify(new PropuestaActualizadaNotification($version, 'enviada'));

        $notificaciones = DatabaseNotification::where('notifiable_id', $vicerrectorado->id)
            ->get()
            ->filter(fn (DatabaseNotification $notificacion): bool => $notificacion->data['version_id'] === $version->id
                && $notificacion->data['evento'] === 'enviada');

        $this->assertCount(1, $notificaciones);
    }

    public function test_puede_marcar_todas_las_notificaciones_como_leidas(): void
    {
        [$director, $vicerrectorado, $propuesta] = $this->propuestaLista();
        $this->actingAs($director)->post("/designaciones/{$propuesta->id}/enviar");

        $this->assertSame(1, $vicerrectorado->unreadNotifications()->count());

        $this->actingAs($vicerrectorado)
            ->post('/notificaciones/leer-todas')
            ->assertRedirect();

        $this->assertSame(0, $vicerrectorado->fresh()->unreadNotifications()->count());
    }

    public function test_retiro_notifica_a_vicerrectorado(): void
    {
        [$director, $vicerrectorado, $propuesta] = $this->propuestaLista();
        $this->actingAs($director)->post("/designaciones/{$propuesta->id}/enviar");
        $version = PropuestaVersion::where('propuesta_id', $propuesta->id)->firstOrFail();

        $this->actingAs($director)
            ->post("/designacion-versiones/{$version->id}/retirar")
            ->assertRedirect();

        $eventos = DatabaseNotification::where('notifiable_id', $vicerrectorado->id)->get()->pluck('data.evento');
        $this->assertTrue($eventos->contains('retirada'));

        $this->actingAs($director)
            ->post("/designaciones/{$propuesta->id}/enviar")
            ->assertRedirect();

        $eventos = DatabaseNotification::where('notifiable_id', $vicerrectorado->id)->get()->pluck('data.evento');
        $this->assertTrue($eventos->contains('reenviada'));
    }

    public function test_notificacion_historica_redirige_a_la_ruta_canonica(): void
    {
        $vicerrectorado = User::factory()->vicerrectorado()->create();
        $notificacion = DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\PropuestaActualizadaNotification',
            'notifiable_type' => $vicerrectorado::class,
            'notifiable_id' => $vicerrectorado->id,
            'data' => ['url' => '/versiones/42/revisar'],
        ]);

        $this->actingAs($vicerrectorado)
            ->post("/notificaciones/{$notificacion->id}/leer")
            ->assertRedirect('/revisiones/42/revisar');

        $this->assertNotNull($notificacion->fresh()->read_at);
    }

    private function propuestaLista(int $filas = 1): array
    {
        Gestion::query()->update(['es_actual' => false]);
        $gestion = Gestion::factory()->create(['es_actual' => true]);
        $periodo = Periodo::factory()->create();
        $carrera = Carrera::factory()->create();
        $director = User::factory()->director($carrera)->create();
        $vicerrectorado = User::factory()->vicerrectorado()->create();
        $propuesta = Propuesta::create([
            'carrera_id' => $carrera->id,
            'gestion_id' => $gestion->id,
            'periodo_id' => $periodo->id,
            'creado_por' => $director->id,
            'estado' => 'borrador',
        ]);

        for ($indice = 0; $indice < $filas; $indice++) {
            $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);
            $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);

            $this->actingAs($director)
                ->put("/designaciones/{$propuesta->id}/asignaciones", [
                    'cambios' => [[
                        'grupo_id' => $grupo->id,
                        'materia_id' => $materia->id,
                        'docente_id' => Docente::factory()->create()->id,
                    ]],
                ])
                ->assertRedirect();
        }

        return [$director, $vicerrectorado, $propuesta];
    }
}
