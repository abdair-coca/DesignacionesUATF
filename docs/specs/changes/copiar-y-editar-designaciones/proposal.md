# Copiar y editar designaciones (Jachasun)

Status: Accepted
Deciders: Dueño del sistema de designaciones
Date: 2026-09-02

## Contexto y problema

La aplicación consulta las designaciones docentes desde la base de datos externa de
Jachasun mediante funciones de solo lectura (`designaciones.f_asignaciones`,
`designaciones.f_asignaciones_detalles`) dentro de transacciones `SET TRANSACTION READ ONLY`
(ver `docs/INTEGRATION_JACHASUN.md`).

El flujo actual no permite:

- Copiar una designación de una gestión anterior a una gestión/periodo nuevo.
- Crear o editar una designación directamente desde la aplicación.

En la BD de Jachasun ya existen dos funciones que cubren estos casos, pero con
defectos que impiden su uso seguro:

- `designaciones.f_copiar_designacion(_id, _id_gestion, _id_periodo, _obs)` está rota:
  lista la columna `id_asignaciones` duplicada en el `INSERT` de detalles y filtra
  `WHERE id = _id` en lugar de `WHERE id_asignaciones = _id`.
- `designaciones.f_designacion(_id, _fecha date, _id_programa, _id_gestion, _id_periodo,
  _obs, _tipo)` declara `_fecha` como `date`, pero el sistema trabaja con timestamps
  (ej. `'2026-08-31 12:00'`), por lo que rechaza valores con hora.

Estas funciones operan sobre `designaciones.asignaciones` y
`designaciones.asignaciones_detalles` de la BD de Jachasun, no sobre las tablas del
dominio local de Laravel.

## Goals

- Poder copiar una designación (con sus detalles) de una gestión anterior a una
  gestión/periodo nuevo, conservando el programa.
- Poder insertar una designación nueva en Jachasun con `_tipo = 'INS'`.
- Poder actualizar una designación existente con `_tipo = 'UPD'`.
- Poder consultar una designación por id con `_tipo = ''`.
- Mantener la restricción actual: el director solo opera sobre la sigla de su propia
  carrera (`carrera.sigla`), nunca sobre entrada del navegador.

## Non-Goals

- No editar filas individuales de `asignaciones_detalles` (se copian en bloque al copiar).
- No eliminar designaciones ni detalles.
- No modificar el flujo local de `propuestas` / `propuesta_designaciones` de Laravel.
- No cambiar el listado ni el detalle de solo lectura ya existentes.

## Alternativas consideradas

- **Hacer las escrituras desde la app con INSERT/UPDATE directos**: se descarta; las
  reglas de negocio viven en las funciones del schema `designaciones` de Jachasun y la
  app no debe duplicarlas ni acoplarse a la estructura interna de las tablas.
- **Reutilizar las funciones existentes tal cual**: se descarta porque `f_copiar_designacion`
  no ejecuta (error de columna duplicada) y `f_designacion` no acepta timestamps.
- **Corregir/crear las funciones en Jachasun y exponerlas por el servicio**: se elige;
  mantiene el contrato de integración (la app llama funciones del schema `designaciones`)
  y concentra la lógica transaccional en un solo lugar.

## Reglas de negocio

- La operación de escritura se ejecuta en transacción contra la BD de Jachasun (sin
  `SET TRANSACTION READ ONLY`).
- Solo un Director de Carrera autenticado puede copiar/crear/editar, y únicamente para
  la sigla de su carrera.
- La fila nueva creada al insertar (`INS`) y al copiar (`f_copiar_designacion`) se crea
  con `estado = 'SOLICITADO'` (confirmado con el dueño; lo garantiza el `DEFAULT` de
  la columna `estado`).
- Al copiar (`f_copiar_designacion`), los detalles con `id_docente = 898` se guardan
  con `id_docente = 0` (regla de negocio confirmada por el administrador; aplicada en
  la función con `IFF(id_docente = 898, 0, id_docente)`).
- La edición en pantalla de detalle modifica la cabecera (fecha, observación) vía
  `f_designacion` con `UPD`; las filas de detalle quedan de solo lectura.
- Permisos: el administrador concedió a `usr_designaciones` `INSERT`/`UPDATE`/`SELECT`
  sobre `designaciones.asignaciones` y `designaciones.asignaciones_detalles`, y
  `USAGE` sobre las secuencias. Las funciones corregidas fueron aplicadas en la BD
  real (2026-09-03); ver `spec.md` y `scripts-bd.sql`.
- `NEEDS_BUSINESS_CONFIRMATION`: si `UPD` debe permitir cambiar `id_programa`. La app
  siempre envía la sigla de la carrera autenticada, así que en la práctica no cambia.
- `NEEDS_BUSINESS_CONFIRMATION`: origen del `_obs` al copiar: el parámetro recibido
  (firma actual) o el valor de la designación origen.