# Matriz de pruebas

Estados: `CUBIERTA`, `PENDIENTE`, `NEEDS_BUSINESS_CONFIRMATION`.

| ID | Módulo | Escenario | Prioridad | Tipo | Evidencia | Estado |
| --- | --- | --- | --- | --- | --- | --- |
| ENV-001 | Entorno | Rechazar ejecución en producción | P0 | Seguridad | `TestingPhase0` | CUBIERTA |
| ENV-002 | Entorno | Recrear base desde cero | P0 | Integración | `TestingPhase0` | CUBIERTA |
| ENV-003 | Entorno | Rollback completo de migraciones | P0 | Integración | `TestingPhase0` | CUBIERTA |
| ENV-004 | Entorno | No enviar correo ni broadcast real | P0 | Integración | `TestingPhase0` | CUBIERTA |
| AUTH-001 | Autenticación | Usuario no autenticado accede a flujo protegido | P0 | Feature | Por crear | PENDIENTE |
| AUTH-002 | Permisos | Director no accede a revisión de otra carrera | P0 | Seguridad | Por crear | PENDIENTE |
| AUTH-003 | Permisos | Usuario sin rol no aprueba una versión | P0 | Seguridad | Por crear | PENDIENTE |
| DES-001 | Propuestas | Crear borrador válido | P0 | Feature | Por crear | PENDIENTE |
| DES-002 | Propuestas | Enviar propuesta incompleta | P0 | Regla | Por crear | PENDIENTE |
| DES-003 | Propuestas | Impedir modificar versión pendiente | P0 | Regla | Por crear | PENDIENTE |
| REV-001 | Revisión | Aprobar todas las filas | P0 | Feature | Por crear | PENDIENTE |
| REV-002 | Revisión | Observar fila con motivo obligatorio | P0 | Feature | Por crear | PENDIENTE |
| REV-003 | Revisión | Aprobar y observar parcialmente sin duplicar notificación | P0 | Regresión | `NotificacionPropuestaTest` | CUBIERTA |
| NOT-001 | Notificaciones | Reintento no duplica notificación | P0 | Idempotencia | `NotificacionPropuestaTest` | CUBIERTA |
| NOT-002 | Notificaciones | Marcar todas como leídas | P1 | Feature | `NotificacionPropuestaTest` | CUBIERTA |
| DB-001 | Integridad | Foreign keys impiden referencias inválidas | P0 | Base de datos | Por crear | PENDIENTE |
| DB-002 | Integridad | Dos envíos concurrentes no duplican datos | P0 | Concurrencia | Por crear | PENDIENTE |
| REP-001 | Reportes | Exportar con filtros válidos | P1 | Feature | Por crear | PENDIENTE |
| PERF-001 | Rendimiento | Consultas comunes bajo carga normal | P1 | Rendimiento | Por crear | PENDIENTE |

## Reglas pendientes de confirmación

- `NEEDS_BUSINESS_CONFIRMATION`: límites de carga horaria y excepciones.
- `NEEDS_BUSINESS_CONFIRMATION`: comportamiento requerido para periodos cerrados.
- `NEEDS_BUSINESS_CONFIRMATION`: política ante conflictos de dos pestañas.
