# Persistir decisiones de Vicerrectorado en Jachasun

Status: Proposed
Deciders: Dueño del sistema de designaciones y administrador de Jachasun
Date: 2026-10-01

## Contexto y problema

La pantalla de detalle de Vicerrectorado permite aceptar o rechazar filas y
capturar una observación opcional. Por confirmación del usuario, las decisiones
actuales solo viven en la página y se pierden al recargar. Se solicita que el
estado y la observación se guarden también en Jachasun.

El contrato conocido de Jachasun devuelve `r_estado` y `r_obs` desde
`f_asignaciones`, mientras que `f_asignaciones_detalles` devuelve las filas de
docentes sin campos de estado u observación. Las funciones de escritura
documentadas (`f_designacion` y `f_designacion_detalle`) no ofrecen actualmente
una operación confirmada para esta decisión de Vicerrectorado.

## Goals

- Persistir decisiones de aceptación/rechazo y observaciones en Jachasun usando
  una función autorizada.
- Conservar decisiones por fila y selección múltiple según la interfaz vigente,
  si el dueño de datos confirma que la entidad de Jachasun lo permite.

## Non-Goals

- Ejecutar `UPDATE`/`INSERT` directos desde la aplicación.
- Inventar estados válidos, transiciones o una relación entre filas docentes y
  el estado de una designación completa.
- Aplicar cambios a Jachasun real sin el contrato y autorización del
  administrador correspondiente.

## Alternativas consideradas

- **Reutilizar `f_designacion`**: no cubre el requisito conocido; su contrato
  actual actualiza la cabecera de la designación y no recibe un estado.
- **Reutilizar `f_designacion_detalle`**: su contrato modifica docente, materia,
  grupo y horas; no cambia estado ni observación.
- **Escribir directamente en tablas**: descartado por el contrato de integración
  del proyecto, que concentra escrituras académicas en funciones de Jachasun.
- **Usar una función autorizada existente o solicitar una nueva al administrador**:
  pendiente de confirmación del dueño de datos.

## Reglas de negocio

- Confirmado por el usuario (2026-10-01): cada asignación docente tendrá su propia
  decisión; aceptar guarda `APROBADA`, rechazar guarda `RECHAZADA`.
- Confirmado por el usuario (2026-10-01): la observación es opcional.
- Confirmado por el usuario (2026-10-01): en una acción grupal se guardan las
  asignaciones válidas aunque otras fallen.
- Confirmado por el usuario (2026-10-01): la función nueva debe actualizar la
  decisión en Jachasun por asignación docente.
- Confirmado por el usuario (2026-10-01): una observación vacía limpia cualquier
  observación previa.
- El contrato SQL propuesto queda en `scripts-bd.sql`; el DBA debe verificarlo,
  aplicarlo y conceder permisos antes de habilitar el endpoint.
