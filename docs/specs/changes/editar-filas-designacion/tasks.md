# Tasks: Editar filas de detalle de una designación (Jachasun)

Orquestación para worker. Ver protocolo en `docs/tasks/README.md`.
Referencias de firma y comportamiento en `spec.md`.

## T-01 Corregir función f_designacion_detalle en BD

- Instrucciones: En la BD de Jachasun (schema `designaciones`), reemplazar
  `f_designacion_detalle` con la definición corregida de `scripts-bd.sql`
  (validada con función temporal en `public` + rollback).
- Archivos: BD (no versionado); definición en `scripts-bd.sql`.
- Resultado esperado: UPDATE e INSERT ejecutan y devuelven las filas visibles
  de la designación; no-op para detalle ajeno e id inexistente.
- Estado: **APLICADA en BD real** por el administrador (2026-09-04). La versión
  aplicada usa `RETURNS TABLE` de 14 columnas y devuelve solo la fila afectada;
  la vista quedó calificada (`academico.v_facultades_programas`), lo que corrige
  el `42P01` que la app veía (search_path de `usr_designaciones` = `public`).
  Verificada con smoke en BD real (rollback): UPDATE e INSERT devuelven la fila.
- [x] Aplicada en BD real

## T-02 Smoke test en BD

- Instrucciones: Ejecutar UPDATE, INSERT, no-op de detalle ajeno e id
  inexistente en transacciones con rollback (ids reales 2138/2131).
- Archivos: BD (no versionado).
- Resultado esperado: todos los escenarios devuelven lo esperado sin efectos.
- [x] Ejecutado en BD real (2026-09-04, conexión de la app, rollback): UPDATE e
  INSERT devuelven la fila afectada y no dejan cambios; el detalle 80360 queda
  intacto (doc=591, horas 0) y 2138 conserva 119 filas.

## T-03 Método del servicio

- Instrucciones: Agregar `guardarDetalle()` en `JachasunDesignacionesService`
  ejecutando `f_designacion_detalle` con parámetros enlazados en transacción
  `escribir()`, validando pertenencia a la carrera.
- Archivos: `app/Services/Jachasun/JachasunDesignacionesService.php`
- Resultado esperado: método disponible con validación y transacción de escritura.
- [x] Implementado

## T-04 Tests unit del servicio

- Instrucciones: Extender `tests/Unit/JachasunDesignacionesServiceTest.php` con
  casos para `guardarDetalle` (patrón de los tests existentes).
- Archivos: `tests/Unit/JachasunDesignacionesServiceTest.php`
- Resultado esperado: SQL exacto, bindings y transacción sin `READ ONLY`.
- [x] Implementado

## T-05 Controlador, ruta y catálogos de la vista

- Instrucciones: Agregar `actualizarDetalle()` en `DesignacionController`;
  registrar `POST /designaciones/{id}/detalle`; en `show()` pasar
  `docentesDisponibles` / `materiasDisponibles` a la vista.
- Archivos: `app/Http/Controllers/DesignacionController.php`, `routes/web.php`
- Resultado esperado: acción implementada y segura.
- [x] Implementado

## T-06 Vista de edición de fila

- Instrucciones: Conectar el modal "Editar" de `carrera.blade.php` al nuevo
  endpoint; docente y materia como `<select>` con los valores de la designación.
- Archivos: `resources/views/designaciones/carrera.blade.php`
- Resultado esperado: flujo operable desde la UI.
- [x] Implementado

## T-07 Tests feature de escritura de detalle

- Instrucciones: Extender los tests feature (patrón
  `JachasunDesignacionesEscrituraTest`): editar fila con servicio mockeado,
  rechazo de otra carrera y error Jachasun con mensaje seguro.
- Archivos: `tests/Feature/JachasunDesignacionesEscrituraTest.php`
- Resultado esperado: `php artisan test --filter=JachasunDesignaciones` verde.
- [x] Implementado

## T-08 Cierre y trazabilidad

- Instrucciones: Aplicar `scripts-bd.sql` en la BD real (administrador);
  actualizar `docs/INTEGRATION_JACHASUN.md`, `docs/testing/STATUS.md` y
  `docs/testing/TEST_MATRIX.md`.
- Archivos: docs de integración y testing.
- [x] Aplicado en la BD real (2026-09-04) y documentado. Pendiente solo ejecutar
  la suite automatizada, bloqueada por la base de testing (`127.0.0.1:55432`)
  no disponible; requiere `composer install --dev` / base de testing.