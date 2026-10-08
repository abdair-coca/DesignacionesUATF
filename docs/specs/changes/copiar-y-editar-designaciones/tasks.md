# Tasks: Copiar y editar designaciones (Jachasun)

Orquestación para worker. Ver protocolo en `docs/tasks/README.md`.
Referencias de firma y comportamiento en `spec.md`.

## T-01 Corregir función f_copiar_designacion en BD

- Instrucciones: En la BD de Jachasun (schema `designaciones`), reemplazar
  `f_copiar_designacion` (son de `utijavier`) con la definición corregida de `spec.md`:
  eliminar la columna duplicada `id_asignaciones` del INSERT de detalles, cambiar el
  filtro a `WHERE id_asignaciones = _id` y crear la fila nueva con `estado = 'SOLICITADO'`.
- Archivos: BD (no versionado).
- Resultado esperado: la función ejecuta sin error y crea fila + detalles.
- Estado: **APLICADA en BD real** por el administrador (2026-09-03): detalles con
  `WHERE id_asignaciones = _id` y regla docente 898 → 0 confirmada. Verificada con
  smoke en BD real (copia correcta, `estado = 'SOLICITADO'` por default de columna).
- [x] Completada (registrada en `scripts-bd.sql`)

## T-02 Ajustar función f_designacion en BD (INS)

- Instrucciones: En `designaciones.f_designacion` (ya aplicada con `_fecha character
  varying` y fallback a `now()`), faltan dos ajustes en el modo `'INS'`: agregar
  `_id := vl_id;` tras el `RETURNING` para que el `RETURN QUERY` devuelva la fila
  insertada, y añadir `estado = 'SOLICITADO'` al INSERT.
- Archivos: BD (no versionado).
- Resultado esperado: `'INS'` inserta con `estado = 'SOLICITADO'` y devuelve la fila;
  `'UPD'` y `''` (select) siguen funcionando.
- Estado: **APLICADA en BD real** por el administrador (2026-09-03): `vl_id := _id`
  al inicio hace que `INS`/`UPD`/select devuelvan la fila; `estado = 'SOLICITADO'`
  lo garantiza el default de columna. Verificada con smoke en BD real.
- [x] Completada (registrada en `scripts-bd.sql`)

## T-03 Smoke test de las funciones en BD

- Instrucciones: Ejecutar los tres modos de `f_designacion` (`INS`, `UPD`, select con `''`)
  y una copia real (`f_copiar_designacion`); verificar que el origen inexistente no crea
  filas y que la copia crea fila + detalles con el nuevo id.
- Archivos: BD (no versionado).
- Resultado esperado: todas las invocaciones devuelven lo esperado.
- [x] Completada en BD real (2026-09-03): `INS` y `''` devuelven fila, `UPD`
  actualiza y devuelve, copia crea fila + detalles con el nuevo id y origen
  inexistente no crea filas.

## T-04 Agregar métodos de escritura al servicio

- Instrucciones: En `JachasunDesignacionesService`, agregar `copiar()`, `insertar()`,
  `actualizar()`, `obtener()` ejecutando `f_copiar_designacion` / `f_designacion` con
  parámetros enlazados, validando con `validarParametros()` y usando el programa de la
  carrera autenticada (nunca del navegador). Las llamadas van en transacción normal, sin
  `SET TRANSACTION READ ONLY`.
- Archivos: `app/Services/Jachasun/JachasunDesignacionesService.php`
- Resultado esperado: métodos disponibles con validación y transacción de escritura.
- [x] Implementado

## T-05 Tests unit del servicio

- Instrucciones: Extender `tests/Unit/JachasunDesignacionesServiceTest.php` con casos para
  `copiar`/`insertar`/`actualizar`/`obtener`: SQL exacto, parámetros enlazados y transacción
  sin `READ ONLY` (patrón de los tests existentes).
- Archivos: `tests/Unit/JachasunDesignacionesServiceTest.php`
- Resultado esperado: `php artisan test --filter=JachasunDesignacionesServiceTest` verde.
- [x] Tests escritos (suite no ejecutable en este servidor)

## T-06 Acciones del controlador

- Instrucciones: Agregar `store` (crear vacía o importar desde otra gestión vía
  `importar_desde`, que ejecuta `copiar()`) y `update` en `DesignacionController` con
  validación de request, autorización por `carrera.sigla` y manejo seguro de errores de
  Jachasun (mensaje genérico + log de la clase de excepción).
- Archivos: `app/Http/Controllers/DesignacionController.php`
- Resultado esperado: acciones implementadas y seguras.
- [x] Implementado

## T-07 Rutas POST

- Instrucciones: Registrar en `routes/web.php`: `POST /designaciones` (crear o importar
  con `importar_desde`) y `POST /designaciones/{id}` (editar cabecera), con `whereNumber`
  en `{id}`. No se registra ruta `/copiar` separada: la copia se resuelve con
  `importar_desde` en `store` (ver `spec.md`).
- Archivos: `routes/web.php`
- Resultado esperado: rutas registradas y resueltas.
- [x] Implementado

## T-08 Tests feature de escritura

- Instrucciones: Agregar tests feature (patrón `JachasunDesignacionesListTest`): copiar/crear/
  editar con servicio mockeado; rechazo si la designación es de otra carrera; error de
  Jachasun con mensaje seguro sin exponer el detalle.
- Archivos: `tests/Feature/` (nuevo o extendiendo los existentes)
- Resultado esperado: `php artisan test --filter=JachasunDesignaciones` verde.
- [x] Tests escritos (suite no ejecutable en este servidor)

## T-09 Vistas de copiar/crear/editar

- Instrucciones: En `resources/views/designaciones/carrera.blade.php` conservar la edición
  de cabecera y restaurar las acciones por fila (Editar abre el modal de la fila;
  Desasignar, solo UI). En `lista.blade.php` el acceso a crear con opción "Importar de una
  gestión anterior" (sin botón de copiado por fila). Mantener los contratos y
  la estructura de las vistas actuales.
- Archivos: `resources/views/designaciones/carrera.blade.php`,
  `resources/views/designaciones/lista.blade.php`
- Resultado esperado: flujo operable desde la UI.
- [x] Implementado (usa `partials/modal-confirmacion` y `partials/modal-notificacion`)

## T-10 Cierre y trazabilidad

- Instrucciones: Ejecutar `vendor/bin/pint --test` y `php artisan test` (suite completa);
  actualizar `docs/INTEGRATION_JACHASUN.md` (ya hay escrituras), `docs/testing/STATUS.md` y
  `docs/testing/TEST_MATRIX.md`; dejar `docs/specs/changes/copiar-y-editar-designaciones/*`
  con `Status: Done` en `spec.md` al cerrar.
- Archivos: docs de integración y testing.
- Resultado esperado: suite verde y trazabilidad actualizada.
- [ ] Parcial: trazabilidad actualizada (tasks, spec, STATUS, TEST_MATRIX,
  INTEGRATION_JACHASUN, repro-bd); funciones BD corregidas, aplicadas y verificadas
  en la BD real (2026-09-03); regla docente 898 confirmada. Suite y Pint siguen
  bloqueados por dev-deps ausentes (sin `vendor/bin/phpunit` ni `vendor/bin/pint`);
  requiere `composer install --dev` para cerrar.