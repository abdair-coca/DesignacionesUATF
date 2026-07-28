<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Gestion;
use App\Models\Periodo;
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
            'is_admin' => false,
            'carrera_id' => $carreraInf->id,
        ]);

        $this->actingAs($directorInf)
            ->get('/designaciones')
            ->assertRedirect(route('designaciones.carrera', $carreraInf->id));
    }

    public function test_director_no_puede_acceder_a_otra_carrera(): void
    {
        $carreraInf = Carrera::where('sigla', 'INF')->first() ?? Carrera::factory()->create(['sigla' => 'INF_TEST2', 'nombre' => 'Ingeniería Informática']);
        $carreraCiv = Carrera::where('sigla', 'CIV')->first() ?? Carrera::factory()->create(['sigla' => 'CIV_TEST2', 'nombre' => 'Ingeniería Civil']);

        $directorInf = User::factory()->create([
            'name' => 'Director Informática',
            'email' => 'test.director.inf2@uatf.edu.bo',
            'is_admin' => false,
            'carrera_id' => $carreraInf->id,
        ]);

        $this->actingAs($directorInf)
            ->get(route('designaciones.carrera', $carreraCiv->id))
            ->assertRedirect(route('designaciones.carrera', $carreraInf->id));
    }

    public function test_admin_puede_ver_resumen_y_cualquier_carrera(): void
    {
        $carreraCiv = Carrera::where('sigla', 'CIV')->first() ?? Carrera::factory()->create(['sigla' => 'CIV_TEST3', 'nombre' => 'Ingeniería Civil']);
        $gestion = Gestion::first() ?? Gestion::factory()->create();
        $periodo = Periodo::where('nombre', '2')->first() ?? Periodo::factory()->create(['nombre' => '2']);

        $admin = User::factory()->create([
            'name' => 'Admin Vicedecano Test',
            'email' => 'admin.test@uatf.edu.bo',
            'is_admin' => true,
            'carrera_id' => null,
        ]);

        $this->actingAs($admin)
            ->get('/designaciones')
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('designaciones.carrera', $carreraCiv->id))
            ->assertOk();
    }
}
