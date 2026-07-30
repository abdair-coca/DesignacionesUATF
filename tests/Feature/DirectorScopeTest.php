<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\User;
use Tests\TestCase;

class DirectorScopeTest extends TestCase
{
    public function test_director_es_redirigido_a_su_carrera_al_entrar_a_designaciones(): void
    {
        $carreraInf = Carrera::where('sigla', 'INF')->first() ?? Carrera::factory()->create(['sigla' => 'INF_TEST', 'nombre' => 'Ingeniería Informática']);
        $directorInf = User::factory()->create([
            'name' => 'Director Informática',
            'email' => 'test.director.inf@uatf.edu.bo',
            'carrera_id' => $carreraInf->id,
        ]);

        $this->actingAs($directorInf)
            ->get('/designaciones')
            ->assertRedirect(route('designaciones.lista'));
    }

    public function test_director_no_puede_acceder_a_otra_carrera(): void
    {
        $carreraInf = Carrera::where('sigla', 'INF')->first() ?? Carrera::factory()->create(['sigla' => 'INF_TEST2', 'nombre' => 'Ingeniería Informática']);
        $carreraCiv = Carrera::where('sigla', 'CIV')->first() ?? Carrera::factory()->create(['sigla' => 'CIV_TEST2', 'nombre' => 'Ingeniería Civil']);

        $directorInf = User::factory()->create([
            'name' => 'Director Informática',
            'email' => 'test.director.inf2@uatf.edu.bo',
            'carrera_id' => $carreraInf->id,
        ]);

        $this->actingAs($directorInf)
            ->get(route('designaciones.carrera', $carreraCiv->id))
            ->assertForbidden();
    }

    public function test_vicerrectorado_no_puede_acceder_a_designaciones(): void
    {
        $carreraCiv = Carrera::where('sigla', 'CIV')->first() ?? Carrera::factory()->create(['sigla' => 'CIV_TEST3', 'nombre' => 'Ingeniería Civil']);

        $admin = User::factory()->vicerrectorado()->create([
            'name' => 'Vicerrectorado Test',
            'email' => 'admin.test@uatf.edu.bo',
        ]);

        $this->actingAs($admin)
            ->get('/designaciones/lista')
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('designaciones.carrera', $carreraCiv->id))
            ->assertForbidden();
    }
}
