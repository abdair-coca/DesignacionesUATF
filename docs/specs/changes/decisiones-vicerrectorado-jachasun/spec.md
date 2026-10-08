# Spec: Decisiones de Vicerrectorado

Status: Implementación Laravel completa; habilitación externa pendiente
Deciders: Dueño del sistema de designaciones y administrador de Jachasun
Date: 2026-10-02

## Solución

Vicerrectorado decide por asignación docente (`APROBADA` o `RECHAZADA`) y puede
decidir también el estado general de la designación (`APROBADO` u `OBSERVADA`).
La decisión general no modifica las decisiones de sus filas. Observar requiere
un motivo de hasta 1000 caracteres; se conserva separado de la observación de
origen y de las observaciones por fila. Aprobar limpia el motivo general previo.

El estado general comienza como `SOLICITADO`. Después de guardar una decisión
por fila se recalcula en la misma transacción: si no hay filas o queda alguna
sin decidir, permanece `SOLICITADO`; si todas están aprobadas, pasa a
`APROBADO`; y si todas están decididas con al menos una rechazada, pasa a
`OBSERVADA`. Una acción general puede establecer `APROBADO` u `OBSERVADA` aunque
haya filas pendientes. La siguiente modificación de una fila vuelve a aplicar
la regla automática.

El contrato de escritura se define en `scripts-bd.sql`. No se habilita la
persistencia en un ambiente hasta que el administrador institucional revise y
aplique el contrato autorizado.

## Invariantes

- Los estados generales permitidos son exactamente `SOLICITADO`, `APROBADO` y
  `OBSERVADA`; las filas conservan `APROBADA` y `RECHAZADA`.
- La aprobación/observación general no escribe estados ni observaciones en las
  filas.
- Solo Vicerrectorado puede escribir decisiones generales o por fila, y el
  servidor verifica que la designación pertenezca al contexto universitario.
- Una designación con estado general `APROBADO` queda en solo lectura para
  Dirección de Carrera; el bloqueo aplica a cabecera y filas en la interfaz y
  en los endpoints.
- La observación general es obligatoria al marcar `OBSERVADA`; al marcar
  `APROBADO` se limpia. Las decisiones por fila mantienen su comportamiento
  existente.
- Cada guardado de fila persiste su decisión y recalcula el estado general
  atómicamente. Una falla en una fila no revierte otras filas válidas.
- Las escrituras usan únicamente el contrato autorizado con parámetros
  enlazados y transacciones normales.
- Los mensajes visibles son generales y no exponen detalles técnicos.

## Interfaces y pruebas

- `POST /vicerrectorado/designaciones/{id}/decisiones` conserva las decisiones
  por fila y responde también con el estado general calculado.
- `POST /vicerrectorado/designaciones/{id}/estado` acepta `APROBADO` o
  `OBSERVADA`; exige observación para `OBSERVADA` y la limpia para `APROBADO`.
- El listado y el detalle muestran el estado general. La lectura de estados por
  fila y la lectura del estado general son independientes: un fallo al leer el
  estado general no oculta las decisiones por fila, y un fallo al leer estados
  por fila los muestra como `Sin estado` sin bloquear las acciones. Las
  operaciones de escritura usan las rutas autorizadas y responden con un error
  general si no pueden completarse.
- Las pruebas de Laravel cubren acciones generales aun con filas pendientes,
  separación y limpieza del motivo, validación y autorización. El recálculo
  automático para filas pendientes, todas aprobadas, rechazo mixto y cero filas
  requiere smoke autorizado del contrato; no se ejecutó en esta fase.

## Dependencias

- Decisiones de negocio confirmadas por el dueño del sistema.
- Revisión y aplicación de `scripts-bd.sql` por el administrador institucional
  antes de habilitar la escritura general y el recálculo automático.
