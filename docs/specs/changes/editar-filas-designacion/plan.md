# Plan: Editar filas de detalle de una designación (Jachasun)

## Fases

### Fase 1 — Base de datos (Jachasun)

- [x] Repro en BD real (rollback): UPDATE → `42601`, INSERT → `42701`.
- [x] Validar la corrección con función temporal en `public` (rollback):
  UPDATE, INSERT, no-op de detalle ajeno e id inexistente.
- [x] Aplicar `scripts-bd.sql` en la BD real (administrador, 2026-09-04).
- [x] Smoke SQL en la BD real tras aplicar (conexión de la app, rollback):
  UPDATE e INSERT devuelven la fila afectada sin errores.

Verificación: la definición corregida en `scripts-bd.sql` ejecuta en los tres
escenarios sin errores.

### Fase 2 — Servicio

- [x] Agregar `guardarDetalle()` en `JachasunDesignacionesService` (validación,
  pertenencia a la carrera, transacción `escribir()`).
- [x] Agregar tests unit (patrón `JachasunDesignacionesServiceTest`).

Verificación: `php artisan test --filter=JachasunDesignacionesServiceTest`.

### Fase 3 — Controlador y ruta

- [x] `show()` pasa `docentesDisponibles` / `materiasDisponibles` a la vista.
- [x] Agregar `actualizarDetalle()` en `DesignacionController`.
- [x] Registrar `POST /designaciones/{id}/detalle` con `whereNumber`.
- [x] Agregar tests feature con servicio mockeado.

Verificación: `php artisan test --filter=JachasunDesignaciones`.

### Fase 4 — Vista

- [x] Conectar el modal "Editar" de fila al nuevo endpoint; docente y materia
  como `<select>` con los valores de la designación.

Verificación: revisión manual del flujo actual en un entorno de testing autorizado.

### Fase 5 — Cierre y trazabilidad

- [x] Aplicar `scripts-bd.sql` en la BD real (administrador, 2026-09-04).
- [x] Actualizar `docs/INTEGRATION_JACHASUN.md`, `docs/testing/STATUS.md` y
  `docs/testing/TEST_MATRIX.md`.
- [ ] Ejecutar `vendor/bin/pint --test` y `php artisan test` (suite completa) —
  bloqueado: base de testing (`127.0.0.1:55432`) no disponible en este servidor.

## Archivos afectados

- BD (Jachasun, schema `designaciones`): `f_designacion_detalle`.
- `app/Services/Jachasun/JachasunDesignacionesService.php`
- `app/Http/Controllers/DesignacionController.php`
- `routes/web.php`
- `resources/views/designaciones/carrera.blade.php`
- `tests/Unit/JachasunDesignacionesServiceTest.php`
- `tests/Feature/JachasunDesignacionesEscrituraTest.php`
- `docs/INTEGRATION_JACHASUN.md`
- `docs/testing/STATUS.md`, `docs/testing/TEST_MATRIX.md`
- `docs/specs/changes/editar-filas-designacion/*`