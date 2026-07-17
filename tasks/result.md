# Resultado de la Tarea: T12 — Tests del Flujo de Revisión

## Resumen de cambios
Se ha creado e implementado el archivo de pruebas `tests/Feature/RevisionTest.php` para cubrir exhaustivamente el flujo de revisión de designaciones académicas por carrera.

Las pruebas agregadas son:
1. `test_usuario_envia_revision_con_exito`: Verifica que un usuario normal puede solicitar exitosamente la revisión de una carrera para una gestión y período específicos, registrando el estado `'pendiente'`.
2. `test_no_permite_dos_revisiones_pendientes_misma_carrera`: Valida que no se permita iniciar una revisión si ya existe otra con estado `'pendiente'` activa para la misma carrera, gestión y período (retorna error `422`).
3. `test_admin_aprueba_designaciones_en_lote`: Verifica que un usuario administrador pueda procesar y aprobar en lote múltiples designaciones de la revisión (cambiando su estado a `'aprobada'` y guardando el ID del aprobador).
4. `test_admin_rechaza_designaciones_en_lote`: Verifica que un usuario administrador pueda procesar y rechazar en lote múltiples designaciones de la revisión (cambiando su estado a `'rechazada'`).
5. `test_admin_completa_revision`: Comprueba que el administrador pueda finalizar una revisión, marcándola con estado `'revisado'` y registrando su ID y el timestamp correspondiente.
6. `test_usuario_normal_no_ve_revisiones_pendientes`: Garantiza que los usuarios normales no tengan acceso a los endpoints administrativos de visualización y detalle de revisiones pendientes (retornando `403`).
7. `test_admin_puede_ver_pendientes`: Asegura que el administrador puede ver las revisiones pendientes en la interfaz de Inertia renderizando el componente `Revisiones/Pendientes`.

## Verificación de pruebas
Se ejecutó la suite de pruebas específica para `RevisionTest` y la suite global:
- `php artisan test --filter=RevisionTest` -> **7 tests passed** (29 assertions)
- `php artisan test` -> **65 tests passed** (175 assertions)

---

## Git Diff de los archivos modificados / creados

```diff
diff --git a/tests/Feature/RevisionTest.php b/tests/Feature/RevisionTest.php
new file mode 100644
index 0000000..8d25413
--- /dev/null
+++ b/tests/Feature/RevisionTest.php
@@ -0,0 +1,304 @@
+<?php
+
+namespace Tests\Feature;
+
+use App\Models\Carrera;
+use App\Models\Designacion;
+use App\Models\Docente;
+use App\Models\Gestion;
+use App\Models\Grupo;
+use App\Models\Materia;
+use App\Models\Periodo;
+use App\Models\Revision;
+use App\Models\User;
+use Illuminate\Foundation\Testing\DatabaseTransactions;
+use Tests\TestCase;
+
+class RevisionTest extends TestCase
+{
+    use DatabaseTransactions;
+
+    public function test_usuario_envia_revision_con_exito(): void
+    {
+        $usuario = User::factory()->create();
+        $carrera = Carrera::factory()->create();
+        $gestion = Gestion::factory()->create();
+        $periodo = Periodo::factory()->create();
+
+        $response = $this->actingAs($usuario)
+            ->postJson('/revisiones/solicitar', [
+                'carrera_id' => $carrera->id,
+                'Id_gestion' => $gestion->id,
+                'Id_periodo' => $periodo->id,
+            ]);
+
+        $response->assertStatus(200)
+            ->assertJson([
+                'success' => true,
+            ]);
+
+        $revisionId = $response->json('revision_id');
+        $this->assertNotNull($revisionId);
+
+        $this->assertDatabaseHas('revisiones', [
+            'id' => $revisionId,
+            'carrera_id' => $carrera->id,
+            'Id_gestion' => $gestion->id,
+            'Id_periodo' => $periodo->id,
+            'estado' => 'pendiente',
+            'solicitado_por' => $usuario->id,
+        ]);
+
+        $revision = Revision::find($revisionId);
+        $this->assertNotNull($revision->solicitado_en);
+    }
+
+    public function test_no_permite_dos_revisiones_pendientes_misma_carrera(): void
+    {
+        $carrera = Carrera::factory()->create();
+        $gestion = Gestion::factory()->create();
+        $periodo = Periodo::factory()->create();
+        $solicitante = User::factory()->create();
+
+        Revision::create([
+            'carrera_id' => $carrera->id,
+            'Id_gestion' => $gestion->id,
+            'Id_periodo' => $periodo->id,
+            'solicitado_por' => $solicitante->id,
+            'solicitado_en' => now(),
+            'estado' => 'pendiente',
+        ]);
+
+        $usuario = User::factory()->create();
+
+        $response = $this->actingAs($usuario)
+            ->postJson('/revisiones/solicitar', [
+                'carrera_id' => $carrera->id,
+                'Id_gestion' => $gestion->id,
+                'Id_periodo' => $periodo->id,
+            ]);
+
+        $response->assertStatus(422)
+            ->assertJson([
+                'error' => 'Ya hay una revisión pendiente para esta carrera.',
+            ]);
+    }
+
+    public function test_admin_aprueba_designaciones_en_lote(): void
+    {
+        $admin = User::factory()->create(['is_admin' => true]);
+        $carrera = Carrera::factory()->create();
+        $gestion = Gestion::factory()->create();
+        $periodo = Periodo::factory()->create();
+
+        $materia1 = Materia::factory()->create(['carrera_id' => $carrera->id]);
+        $grupo1 = Grupo::factory()->create(['materia_id' => $materia1->id]);
+        $designacion1 = Designacion::factory()->create([
+            'Id_materia' => $materia1->id,
+            'Id_grupo' => $grupo1->id,
+            'Id_gestion' => $gestion->id,
+            'Id_periodo' => $periodo->id,
+            'estado' => 'propuesta',
+        ]);
+
+        $materia2 = Materia::factory()->create(['carrera_id' => $carrera->id]);
+        $grupo2 = Grupo::factory()->create(['materia_id' => $materia2->id]);
+        $designacion2 = Designacion::factory()->create([
+            'Id_materia' => $materia2->id,
+            'Id_grupo' => $grupo2->id,
+            'Id_gestion' => $gestion->id,
+            'Id_periodo' => $periodo->id,
+            'estado' => 'propuesta',
+        ]);
+
+        $revision = Revision::create([
+            'carrera_id' => $carrera->id,
+            'Id_gestion' => $gestion->id,
+            'Id_periodo' => $periodo->id,
+            'solicitado_por' => User::factory()->create()->id,
+            'solicitado_en' => now(),
+            'estado' => 'pendiente',
+        ]);
+
+        $response = $this->actingAs($admin)
+            ->postJson("/revisiones/{$revision->id}/procesar", [
+                'acciones' => [
+                    ['id' => $designacion1->id, 'accion' => 'aprobar'],
+                    ['id' => $designacion2->id, 'accion' => 'aprobar'],
+                ],
+            ]);
+
+        $response->assertStatus(200)
+            ->assertJson([
+                'success' => true,
+                'procesadas' => 2,
+            ]);
+
+        $this->assertDatabaseHas('designaciones', [
+            'id' => $designacion1->id,
+            'estado' => 'aprobada',
+            'aprobado_por' => $admin->id,
+        ]);
+
+        $this->assertDatabaseHas('designaciones', [
+            'id' => $designacion2->id,
+            'estado' => 'aprobada',
+            'aprobado_por' => $admin->id,
+        ]);
+    }
+
+    public function test_admin_rechaza_designaciones_en_lote(): void
+    {
+        $admin = User::factory()->create(['is_admin' => true]);
+        $carrera = Carrera::factory()->create();
+        $gestion = Gestion::factory()->create();
+        $periodo = Periodo::factory()->create();
+
+        $materia1 = Materia::factory()->create(['carrera_id' => $carrera->id]);
+        $grupo1 = Grupo::factory()->create(['materia_id' => $materia1->id]);
+        $designacion1 = Designacion::factory()->create([
+            'Id_materia' => $materia1->id,
+            'Id_grupo' => $grupo1->id,
+            'Id_gestion' => $gestion->id,
+            'Id_periodo' => $periodo->id,
+            'estado' => 'propuesta',
+        ]);
+
+        $materia2 = Materia::factory()->create(['carrera_id' => $carrera->id]);
+        $grupo2 = Grupo::factory()->create(['materia_id' => $materia2->id]);
+        $designacion2 = Designacion::factory()->create([
+            'Id_materia' => $materia2->id,
+            'Id_grupo' => $grupo2->id,
+            'Id_gestion' => $gestion->id,
+            'Id_periodo' => $periodo->id,
+            'estado' => 'propuesta',
+        ]);
+
+        $revision = Revision::create([
+            'carrera_id' => $carrera->id,
+            'Id_gestion' => $gestion->id,
+            'Id_periodo' => $periodo->id,
+            'solicitado_por' => User::factory()->create()->id,
+            'solicitado_en' => now(),
+            'estado' => 'pendiente',
+        ]);
+
+        $response = $this->actingAs($admin)
+            ->postJson("/revisiones/{$revision->id}/procesar", [
+                'acciones' => [
+                    ['id' => $designacion1->id, 'accion' => 'rechazar'],
+                    ['id' => $designacion2->id, 'accion' => 'rechazar'],
+                ],
+            ]);
+
+        $response->assertStatus(200)
+            ->assertJson([
+                'success' => true,
+                'procesadas' => 2,
+            ]);
+
+        $this->assertDatabaseHas('designaciones', [
+            'id' => $designacion1->id,
+            'estado' => 'rechazada',
+        ]);
+
+        $this->assertDatabaseHas('designaciones', [
+            'id' => $designacion2->id,
+            'estado' => 'rechazada',
+        ]);
+    }
+
+    public function test_admin_completa_revision(): void
+    {
+        $admin = User::factory()->create(['is_admin' => true]);
+        $carrera = Carrera::factory()->create();
+        $gestion = Gestion::factory()->create();
+        $periodo = Periodo::factory()->create();
+
+        $revision = Revision::create([
+            'carrera_id' => $carrera->id,
+            'Id_gestion' => $gestion->id,
+            'Id_periodo' => $periodo->id,
+            'solicitado_por' => User::factory()->create()->id,
+            'solicitado_en' => now(),
+            'estado' => 'pendiente',
+        ]);
+
+        $response = $this->actingAs($admin)
+            ->postJson("/revisiones/{$revision->id}/completar");
+
+        $response->assertStatus(200)
+            ->assertJson([
+                'success' => true,
+            ]);
+
+        $this->assertDatabaseHas('revisiones', [
+            'id' => $revision->id,
+            'estado' => 'revisado',
+            'revisado_por' => $admin->id,
+        ]);
+
+        $revision->refresh();
+        $this->assertNotNull($revision->revisado_en);
+    }
+
+    public function test_usuario_normal_no_ve_revisiones_pendientes(): void
+    {
+        $usuario = User::factory()->create(['is_admin' => false]);
+        $carrera = Carrera::factory()->create();
+        $gestion = Gestion::factory()->create();
+        $periodo = Periodo::factory()->create();
+
+        $revision = Revision::create([
+            'carrera_id' => $carrera->id,
+            'Id_gestion' => $gestion->id,
+            'Id_periodo' => $periodo->id,
+            'solicitado_por' => $usuario->id,
+            'solicitado_en' => now(),
+            'estado' => 'pendiente',
+        ]);
+
+        $this->actingAs($usuario)
+            ->get('/revisiones/pendientes')
+            ->assertStatus(403);
+
+        $this->actingAs($usuario)
+            ->get("/revisiones/{$revision->id}/revisar")
+            ->assertStatus(403);
+    }
+
+    public function test_admin_puede_ver_pendientes(): void
+    {
+        $admin = User::factory()->create(['is_admin' => true]);
+
+        $response = $this->actingAs($admin)
+            ->get('/revisiones/pendientes');
+
+        $response->assertStatus(200)
+            ->assertInertia(fn ($page) => $page->component('Revisiones/Pendientes'));
+    }
+}
```
