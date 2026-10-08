# BUG-2026-09-03-4: Fecha automática al copiar y editar designaciones

## Estado

RESUELTO (2026-09-03)

## Síntoma

Al copiar una designación (crear con "Importar de una gestión anterior") o al
editar una designación, el usuario debía escribir la fecha manualmente en el
campo "Fecha". Además, al editar una designación cuya `fecha` es `NULL`
(designaciones importadas, p. ej. 2137/2129), el guardado fallaba con el mensaje
genérico "No fue posible actualizar la designacion." sin poder guardar siquiera
la observación.

## Reproducción segura

1. Editar la cabecera de una designación con `fecha = NULL` enviando solo la
   observación: `POST /designaciones/{id}` con `['obs' => 'OBS NUEVA']`.
   Resultado antes: `InvalidArgumentException` ("La fecha no es valida.") lanzado
   por `JachasunDesignacionesService::validarFecha` al recibir `''`, capturado y
   mostrado como error genérico.
2. Crear una designación vacía sin fecha: `POST /designaciones` sin `fecha`.
   Resultado antes: `InvalidArgumentException` ("La fecha es obligatoria.") en
   `DesignacionController@store`.

## Causa

- `JachasunDesignacionesService::validarFecha()` rechazaba la fecha vacía
  (`''`), impidiendo que la función `f_designacion` (`INS`/`UPD`) aplicara su
  fallback a `now()` ya implementado en la BD (`_fecha` vacío o `NULL` → `now()`).
- `DesignacionController@store` exigía `fecha` en el modo "crear vacía".
- Los formularios (crear/importar y editar) mostraban el campo "Fecha" como
  ingreso manual obligatorio, aunque al copiar la BD ya asigna `now()`
  automáticamente (`DEFAULT (now())` en `designaciones.asignaciones.fecha`).

## Corrección aplicada

1. `JachasunDesignacionesService::validarFecha()`: acepta fecha vacía y la
   devuelve `''` para que la BD aplique `now()`; mantiene la validación de
   formato para valores no vacíos.
2. `DesignacionController@store`: en "crear vacía" ya no exige fecha; envía
   `$data['fecha'] ?? ''` a `insertar()`.
3. `DesignacionController@update`: conserva la lógica existente (fecha vacía →
   fecha existente); al ser `NULL` envía `''` y la BD asigna `now()`.
4. Vistas: se oculta el campo "Fecha" en `lista.blade.php` (nueva/importar) y en
   `carrera.blade.php` (editar cabecera). La validación JS de crear solo exige
   gestión y periodo.

No se modificaron funciones de BD: `f_designacion` ya aplica `now()` con
`_fecha` vacío y la copia usa el `DEFAULT (now())` de la columna `fecha`.

## Pruebas de regresión

- `test_crear_sin_fecha_envia_fecha_vacia_y_la_bd_la_pone_automatica`
  (feature): POST crear sin `fecha` → `insertar` recibe `''`.
- `test_actualizar_sin_fecha_existente_envia_fecha_vacia_para_la_bd`
  (feature): POST editar con `fecha = NULL` existente → `actualizar` recibe `''`
  y el guardado tiene éxito.
- `test_insertar_con_fecha_vacia_delega_el_fallback_a_la_bd` y
  `test_actualizar_con_fecha_vacia_delega_el_fallback_a_la_bd` (unit):
  `insertar`/`actualizar` con `''` no lanzan y pasan `''` a `f_designacion`.
- `test_actualizar_rechaza_una_fecha_con_formato_invalido` (unit): la
  validación de formato se conserva para valores no vacíos.

## Riesgos / pendientes

- Suite automatizada bloqueada en este servidor por ausencia del PostgreSQL de
  testing (`127.0.0.1:55432` no disponible); se verificó con `php -l`, `pint`,
  `view:cache`, `route:list` y una ejecución del servicio real con el gestor de
  BD mockeado (`insertar`/`actualizar` con `''` y rechazo de formato inválido).
- La hora de `now()` la define el servidor PostgreSQL de Jachasun (fuente de
  verdad del timestamp).