# Plan: Copiar y editar designaciones (Jachasun)

## Fases

### Fase 1 — Base de datos (Jachasun)

- [x] Preparar correcciones en `scripts-bd.sql` y validarlas con funciones temporales en `public` (rollback).
- [x] Aplicar las funciones corregidas en la BD real (administrador, 2026-09-03). Nota: la versión aplicada usa `vl_id := _id` al inicio en `f_designacion` (en vez de `_id := vl_id` en `INS`) y `IFF(id_docente = 898, 0, id_docente)` en la copia (regla confirmada).
- [x] Ejecutar smoke SQL en la BD real: `INS`, `UPD` y select con `_tipo = ''` devuelven lo esperado.
- [x] Validar copia real (fila + detalles con el nuevo id; origen inexistente no crea filas).
- [x] Smoke en BD real completado (2026-09-03, con rollback).

Verificación: los `SELECT ... f_designacion(...)` de ejemplo devuelven las filas esperadas y la copia crea la fila + detalles.

### Fase 2 — Servicio

- [x] Agregar métodos `copiar()`, `insertar()`, `actualizar()`, `obtener()` en `JachasunDesignacionesService`.
- [x] Validar parámetros con `validarParametros()` y el programa de la carrera autenticada (nunca del navegador).
- [x] Envolver las llamadas en transacción normal (sin `SET TRANSACTION READ ONLY`).
- [x] Agregar tests unit (patrón `JachasunDesignacionesServiceTest`): SQL exacto + parámetros enlazados + transacción sin READ ONLY.

Verificación: `php artisan test --filter=JachasunDesignacionesServiceTest` (suite no ejecutable en este servidor: dev-deps ausentes).

### Fase 3 — Controlador y rutas

- [x] Agregar acciones `store` (crear o importar vía `importar_desde`) y `update` en `DesignacionController`.
- [x] Verificar que la designación origen (copiar/editar) pertenece a la carrera del director.
- [x] Agregar rutas POST en `routes/web.php` (`designaciones`, `designaciones/{id}`; la copia va por `importar_desde` en `store`, sin ruta `/copiar` separada).
- [x] Agregar tests feature con servicio mockeado: autorización por carrera, rechazo de otra carrera, error Jachasun con mensaje seguro.

Verificación: `php artisan test --filter=JachasunDesignaciones`.

### Fase 4 — Vistas

- [x] Agregar acciones por fila (Editar/Desasignar) y edición de cabecera en `resources/views/designaciones/carrera.blade.php`.
- [x] Agregar en `resources/views/designaciones/lista.blade.php` la creación con opción "Importar de una gestión anterior" (sin botón de copiado por fila).

Verificación: revisión manual del flujo actual en un entorno de testing autorizado.

### Fase 5 — Cierre y trazabilidad

- [ ] Ejecutar `vendor/bin/pint --test` y `php artisan test` (suite completa) — bloqueado: `phpunit/phpunit` y `pint` no instalados en vendor (dev-deps ausentes).
- [x] Actualizar `docs/INTEGRATION_JACHASUN.md` (escrituras contra Jachasun documentadas).
- [x] Actualizar `docs/testing/STATUS.md` y `docs/testing/TEST_MATRIX.md` (incluye validación de scripts BD).
- [x] Confirmar con el dueño la regla docente 898 (confirmada) y aplicar en BD.
- [x] Aplicar y validar `scripts-bd.sql` en la BD real (administrador, 2026-09-03).

Verificación: suite completa verde y estado/trazabilidad registrados. Pendiente:
ejecutar la suite automatizada (`phpunit`/`pint`) tras `composer install --dev`
(dev-deps ausentes en este servidor); confirmar el resto de `NEEDS_BUSINESS_CONFIRMATION`
de `proposal.md` (cambio de `id_programa` en `UPD`, origen de `_obs`).

## Archivos afectados

- BD (Jachasun, schema `designaciones`): `f_copiar_designacion`, `f_designacion`.
- `app/Services/Jachasun/JachasunDesignacionesService.php`
- `app/Http/Controllers/DesignacionController.php`
- `routes/web.php`
- `resources/views/designaciones/carrera.blade.php`
- `resources/views/designaciones/lista.blade.php`
- `tests/Unit/JachasunDesignacionesServiceTest.php`
- `tests/Feature/JachasunDesignacionesListTest.php` (o nuevo test feature)
- `docs/INTEGRATION_JACHASUN.md`
- `docs/testing/STATUS.md`, `docs/testing/TEST_MATRIX.md`