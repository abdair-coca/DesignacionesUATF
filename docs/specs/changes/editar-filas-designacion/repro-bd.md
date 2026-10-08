# Reproducción de errores en `designaciones.f_designacion_detalle`

Fecha de detección: 2026-09-03
Estado: **RESUELTO y APLICADO en la BD real** (administrador, 2026-09-04);
verificado con smoke por la conexión de la app (rollback).

## Resumen

`designaciones.f_designacion_detalle` existe en la BD de Jachasun
(`10.10.166.120:5432/jachasun`) pero **no ejecuta en ningún camino**: los modos
UPDATE e INSERT fallan por defectos de sintaxis/estructura antes de escribir.
Esto bloquea su integración para editar las filas de detalle (docente, materia,
grupo, horas) de una designación desde la aplicación.

La lógica corregida se validó con una función temporal en `public`
(transacción con rollback) y quedó registrada en `scripts-bd.sql` para que el
administrador la aplique en el schema `designaciones`.

## Problema 1 — UPDATE falla por columna asignada dos veces

### Cuándo ocurría
Al intentar actualizar una fila de detalle existente (`_id_detalle > 0`).

### Reproducción (detalle real 80360 de la designación 2138)
```sql
SELECT * FROM designaciones.f_designacion_detalle(2138, 80360, 672, 3868, 5, 10, 2, 1);
```

### Resultado
```
ERROR: 42601 multiple assignments to same column "id_docente"
```

### Causa
El `UPDATE` asignaba `id_docente` dos veces:
```sql
UPDATE ... SET id_materia = _id_materia,
              id_docente  = _id_docente,
              id_docente  = _id_grupo,      -- repite la columna
              ...                          -- y nunca asigna id_grupo
```

## Problema 2 — INSERT falla por columna duplicada

### Cuándo ocurría
Al intentar insertar una fila nueva (`_id_detalle = 0`).

### Reproducción
```sql
SELECT * FROM designaciones.f_designacion_detalle(2138, 0, 602, 9595, 1, 0, 0, 0);
```

### Resultado
```
ERROR: 42701 column "id_materia" specified more than once
```

### Causa
La lista de columnas del `INSERT` repetía `id_materia`, usaba columnas
inexistentes (`hrs_teoria`, `hrs_practica`; las reales son
`hrs_teoricas`, `hrs_practicas`) y omitía `id_docente`.

## Defectos adicionales detectados (no alcanzables por los errores anteriores)

- `RETURN QUERY` unía `ON da.id_asignaciones = da.id` (columna inexistente en
  `asignaciones`; debe ser `dad.id_asignaciones = da.id`).
- El `SELECT` del `RETURN QUERY` tenía 13 columnas contra 12 del `RETURNS TABLE`.
- `WHERE id = vl_id_detalle` con `id` ambiguo (`da` y `dad` lo tienen); además
  `vl_id_detalle` guardaba el FK `id_asignaciones` en UPDATE y el id nuevo en INSERT.
- Sin validación: un `_id_detalle` de otra designación se actualizaba igual; un
  `_id_detalle > 0` inexistente no hacía nada; `_id` inexistente rompía el FK del INSERT.
- No aplicaba el filtro `NOT IN (0, 898)` que sí aplica la lectura
  `f_asignaciones_detalles` (hay 12.274 detalles con docente 898).

## Verificación de la corrección (función temporal `public.f_designacion_detalle_tmp`, rollback)

- UPDATE de 80360: actualiza `id_docente` 591→672, `id_grupo` 5, horas 10/2/1 y
  `fecha=now()`; devuelve 70 filas visibles de 2138 (69 antes del update).
- INSERT para 2138: crea la fila nueva; devuelve 71 filas visibles.
- No-op de detalle ajeno: `f_designacion_detalle_tmp(2131, 80360, ...)` no toca
  80360 y devuelve la fila de 2131.
- Id inexistente: `(999999, 0, ...)` → 0 filas, sin efectos.
- Tras el rollback: detalle 80360 intacto (doc 591, horas 0), 2138 con 119
  detalles, función temporal eliminada.

## Estado

Resuelto. La definición corregida fue aplicada por el administrador en la BD
real (2026-09-04) y verificada con smoke por la conexión de la app
(`usr_designaciones`): UPDATE e INSERT devuelven la fila afectada y no dejan
cambios tras el rollback (detalle 80360 intacto; 2138 conserva 119 filas).

## Hallazgo adicional: `search_path` y la vista `v_facultades_programas`

La primera versión aplicada por el administrador usaba la vista sin calificar
(`JOIN v_facultades_programas vfp`). La función fallaba en la app con
`42P01 relation "v_facultades_programas" does not exist` porque el usuario de la
app (`usr_designaciones`) tiene `search_path = public`, mientras que la sesión
del administrador resuelve la vista (tiene `academico` en su search_path). Por
eso la función "funcionaba" al probarla como admin pero no en la app. Se corrigió
calificando la vista: `academico.v_facultades_programas`. Además, llamar a la
función mientras quedaba rota dejaba transacciones colgadas con locks sobre
`asignaciones_detalles` (procesos terminados y datos restaurados).