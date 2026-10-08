# Tasks: Persistir decisiones de Vicerrectorado en Jachasun

Orquestación del cambio. No iniciar tareas de escritura hasta resolver las
confirmaciones de `proposal.md` y `spec.md`.

## T-01 Confirmar entidad y transición

- Instrucciones: confirmar si estado/observación se almacenan en la cabecera de
  una designación o en cada asignación docente; confirmar los literales y
  transiciones válidas.
- Archivos: `proposal.md`, `spec.md`.
- Resultado esperado: decisión por asignación con `APROBADA`/`RECHAZADA`,
  observación opcional y persistencia parcial de lotes.
- Estado: COMPLETADA según confirmación del usuario (2026-10-01).

## T-02 Aplicar función Jachasun

- Instrucciones: revisar `scripts-bd.sql`, confirmar que la tabla y tipos coinciden
  con Jachasun, aplicar la función y conceder `EXECUTE` al usuario de la aplicación.
- Archivos: `scripts-bd.sql` y contrato externo Jachasun.
- Resultado esperado: función documentada con entradas, resultado y semántica de
  transacción.
- Estado: BLOQUEADA.

## T-03 Confirmar efecto de observación vacía

- Instrucciones: definir si una observación vacía al decidir debe limpiar la
  observación anterior o conservarla.
- Archivos: `proposal.md`, `spec.md`.
- Resultado esperado: regla documentada sin `NEEDS_BUSINESS_CONFIRMATION`.
- Estado: COMPLETADA; una observación vacía limpia el valor anterior.

## T-04 Implementar servicio, endpoint y regresiones

- Instrucciones: después de T-01/T-02, implementar validación, autorización,
  escritura mediante función, operación múltiple y pruebas con mocks.
- Archivos: servicio Jachasun, controlador, rutas, vista y pruebas.
- Resultado esperado: ninguna decisión se considera guardada hasta confirmar el
  éxito de Jachasun.
- Estado: IMPLEMENTADA Y VERIFICADA CON MOCKS; no habilitar en un ambiente hasta T-02.

## T-05 Aplicar DDL y probar con Jachasun autorizado

- Instrucciones: el DBA verifica/agrega columnas, funciones y permisos desde
  `scripts-bd.sql`; ejecutar un smoke con datos sintéticos y rollback en una BD
  autorizada.
- Archivos: `scripts-bd.sql` y contrato de integración.
- Resultado esperado: lectura/escritura individual confirmada y escritura parcial
  de lote reproducida en la BD de testing/QA autorizada.
- Estado: BLOQUEADA; el script no ha sido aplicado.
