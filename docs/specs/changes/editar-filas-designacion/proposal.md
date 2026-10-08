# Editar filas de detalle de una designación (Jachasun)

Status: Accepted
Deciders: Dueño del sistema de designaciones
Date: 2026-09-03

## Contexto y problema

La feature "copiar y editar designaciones" permite crear, copiar y editar la
cabecera de una designación, pero las filas de detalle
(`designaciones.asignaciones_detalles`) quedaron de solo lectura en la
aplicación ("Desasignar" es solo UI; el modal "Editar" de la fila no envía nada).

En la BD de Jachasun existe `designaciones.f_designacion_detalle` para insertar
o actualizar una fila de detalle, pero está **rota en todos sus caminos**:

- UPDATE → error `42601` (columna `id_docente` asignada dos veces; `id_grupo`
  nunca se actualiza).
- INSERT → error `42701` (columna `id_materia` duplicada), usa columnas
  inexistentes (`hrs_teoria`/`hrs_practica`) y omite `id_docente`.
- El `RETURN QUERY` es inválido (JOIN con columna inexistente, 13 columnas vs 12
  del `RETURNS TABLE`, `WHERE id` ambiguo) y no valida que el detalle pertenezca
  a la designación.

La reproducción y la corrección están en `repro-bd.md` y `scripts-bd.sql`.

## Goals

- Corregir `f_designacion_detalle` para insertar (`_id_detalle = 0`) y actualizar
  (`_id_detalle > 0`) una fila de detalle de una designación, devolviendo las
  filas visibles de la designación.
- Editar desde la UI las filas de detalle de una designación de la propia
  carrera: docente, materia, grupo y horas.
- Los selectores de docente y materia usan únicamente los docentes/materias que
  ya figuran en la designación (sin catálogos externos de `academico`).
- Mantener la restricción actual: el director solo opera sobre la sigla de su
  propia carrera (`carrera.sigla`), nunca sobre entrada del navegador.

## Non-Goals

- No eliminar filas de detalle (desasignar sigue siendo solo UI).
- No exponer catálogos completos de `academico.pln_materias` / `academico.docentes`.
- No conciliar la fuente de horas entre el listado (`f_asignaciones_detalles`
  usa `pm.hrs_*`) y la escritura (horas manuales en `dad.hrs_*`); queda como
  riesgo conocido.
- No modificar el flujo local de `propuestas` / `propuesta_designaciones`.

## Reglas de negocio

- `_id_detalle = 0` crea una fila nueva; `_id_detalle > 0` actualiza la fila
  existente **solo si pertenece a la designación `_id`**.
- Docentes 0 y 898 quedan excluidos del resultado (regla confirmada, igual que
  la lectura `f_asignaciones_detalles`); no se asignan desde la UI.
- Las horas se guardan y se devuelven manuales (`dad.hrs_*`); `fecha = now()`
  al actualizar (heredado de la función original).
- `habilitado` y `estado` quedan NULL en filas nuevas (heredado; sin regla nueva).
- La escritura se ejecuta en transacción normal contra Jachasun (sin
  `SET TRANSACTION READ ONLY`), con parámetros enlazados.

## Alternativas consideradas

- **Usar la función existente tal cual**: descartada; no ejecuta en ningún camino.
- **Exponer catálogos de `academico` para selects**: descartado en esta
  iteración; los docentes/materias disponibles se toman de las filas de la
  propia designación, que la app ya lee.
- **Corregir `f_designacion_detalle` y exponerla por el servicio**: se elige;
  mantiene el contrato de integración (la app llama funciones del schema
  `designaciones`) y concentra la lógica transaccional en un solo lugar.

## Dependencias

- `f_asignaciones` / `f_asignaciones_detalles` y vista `academico.v_facultades_programas`.
- Permisos de escritura en Jachasun (ya concedidos a `usr_designaciones`).
- Aplicación de `scripts-bd.sql` por el administrador.