# Reporte Fase 2 — análisis funcional y cartografía

Fecha: 2026-08-04
Alcance: análisis únicamente. Sin factories, seeders nuevos, correcciones funcionales, pruebas masivas, E2E o rendimiento.

## Resultado ejecutivo

Fase 2 documentada. Sistema es aplicación Laravel web monolítica con Blade + Alpine.js + Tailwind CDN, PostgreSQL y flujo de designaciones versionado. Director crea/edita/envía; Vicerrectorado revisa y decide; snapshots preservan versión enviada; notificaciones son internas en base de datos.

No se modificó lógica de producción ni se crearon pruebas nuevas. La suite existente se ejecutó al cierre.

## Método y CodeGraph

Se consultó CodeGraph CLI antes de documentar:

- Índice sano: 123 archivos, 1.018 nodos, 1.461 relaciones, 20 rutas, lenguaje PHP.
- Backend activo: fallback WASM porque `better-sqlite3` no está disponible.
- `pendingChanges`: 0 antes de documentar.
- Consultas estructurales usadas sobre controllers, services, policies, modelos, notificaciones y rutas.
- MCP `codegraph_context` devolvió `Transport closed`; se continuó con el índice CLI local, que sí respondió y conserva el mapa AST.

## Archivos inspeccionados

### Aplicación

- `routes/web.php`, `routes/auth.php`, `bootstrap/app.php`.
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`.
- `app/Http/Controllers/PropuestaController.php`.
- `app/Http/Controllers/RevisionPropuestaController.php`.
- `app/Http/Controllers/NotificacionController.php`.
- `app/Services/PropuestaService.php`.
- `app/Services/RevisionPropuestaService.php`.
- `app/Services/ImportacionPropuestaService.php`.
- `app/Http/Middleware/EnsureRole.php`.
- `app/Policies/CarreraPolicy.php`, `PropuestaPolicy.php`, `PropuestaVersionPolicy.php`.
- Modelos `Carrera`, `Materia`, `MallaCurricular`, `Grupo`, `Docente`, `Gestion`, `Periodo`, `Designacion`, `Propuesta`, `PropuestaDesignacion`, `PropuestaVersion`, `PropuestaVersionDesignacion`, `PropuestaVersionDecision`, `PropuestaEvento`, `User`.
- `config/designaciones.php`, `config/filesystems.php`.

### Persistencia

- Migraciones de usuarios, carreras, materias, malla, grupos, docentes, gestiones, periodos y legado `designaciones`.
- Migraciones de `propuestas`, `propuesta_designaciones`, `propuesta_versiones`, snapshots, decisiones, eventos y `notifications`.
- Migraciones de roles, normalización, índices, unicidades, remuneración y triggers de inmutabilidad.

### Presentación y operación

- `resources/views/layouts/app.blade.php` y vistas Blade de designaciones/revisiones/notificaciones/auth.
- `public/boceto-designaciones/`.
- `composer.json`, `README.md`, `docs/PLAN_FRONTEND.md`, `.env.testing` y `AGENTS.md`.
- Suite `tests/Feature/` relacionada con aceptación, autorización, versionado, revisión, importación, roster, distribución, notificaciones y acceso.

## Hallazgos

1. 24 rutas: 21 de aplicación, 2 implícitas de storage (`GET`/`PUT /storage/{path}`), 1 `/up`.
2. Director queda limitado por rol y carrera; Vicerrectorado queda limitado por rol, no por carrera.
3. Reglas fuertes viven en `PropuestaService` y `RevisionPropuestaService`, con Policies antes del servicio y constraints PostgreSQL como última barrera.
4. El envío crea versión secuencial y snapshot inmutable; la revisión crea decisión por snapshot.
5. Propuesta, versión, fila actual, snapshot, decisión y UI tienen estados diferentes; no existe state machine única.
6. La importación combina propuesta oficial y legado, con prioridad definida por implementación, no por documento de negocio.
7. La notificación actual es database-only. El caso de duplicación observado tiene regresión de idempotencia cubierta por `NotificacionPropuestaTest`, pero quedan escenarios de eventos distintos y URL heurística para validar después.
8. No existe upload, download, exportación ni reporte en código de aplicación. Importación no usa archivos.
9. `public/storage` no está enlazado en testing. No afecta flujo actual; sí bloquea o vuelve insegura cualquier futura función de archivos públicos.
10. No hay `package.json` porque frontend no usa npm/Vite: Blade, Alpine CDN y Tailwind CDN.

## Reglas confirmadas

- Roles permitidos y combinación rol/carrera.
- Gestión actual requerida para crear/editar/enviar.
- Aislamiento del Director por carrera.
- Propuesta editable solo en borrador sin versión pendiente.
- Cobertura de grupos habilitados y docente requerido al enviar.
- Una versión pendiente por propuesta; snapshots/decisiones inmutables.
- Revisión total o por filas; decisiones completas; observación requiere motivo.
- Transiciones `pendiente` a `retirada`, `observada` o `aprobada`.
- Restricciones de horas por fila.
- Importación no puede usar mismo origen; no pisa fila aprobada previamente.
- Notificación persistida, pertenencia validada al marcar, lectura individual/todas.

## Ambigüedades

- Mínimo/máximo de horas: config dice mínimo 6; código no lo aplica.
- Alcance real de Vicerrectorado.
- Cardinalidad de propuestas por carrera/gestión/periodo.
- Fuente canónica y precedencia legado/oficial.
- Rechazo legado versus observación/retiro actual.
- Excepciones a obligatoriedad de grupos habilitados.
- Gestión cerrada, concurrencia de pestañas, auditoría detallada y reportes.

Todas están marcadas `NEEDS_BUSINESS_CONFIRMATION` en `BUSINESS_RULES.md`, `TEST_MATRIX.md` o `RISK_REGISTER.md`.

## Riesgos principales

- P0: autorización/alcance, estados distribuidos, duplicación o mezcla de fuentes, límites horarios no definidos.
- P1: triggers PostgreSQL, URL de notificación, storage no enlazado, CDN, ausencia de pipeline frontend.
- P2/P3: auditoría de edición, documentación histórica y funcionalidad de reportes no implementada.

Detalle: [RISK_REGISTER.md](../RISK_REGISTER.md).

## Documentos creados o actualizados

- `docs/testing/SYSTEM_MAP.md`
- `docs/testing/ENDPOINT_CATALOG.md`
- `docs/testing/BUSINESS_RULES.md`
- `docs/testing/STATE_TRANSITIONS.md`
- `docs/testing/TEST_MATRIX.md`
- `docs/testing/RISK_REGISTER.md`
- `docs/testing/PHASES_REPORTS/PHASE_2_REPORT.md`
- `docs/testing/STATUS.md`

## Resultado de pruebas al cierre

Comando ejecutado después de documentar:

```text
composer test
```

Resultado: **OK — 62/62 pruebas, 326 assertions**, sin cambios funcionales.

## Recomendación Fase 3

**APTO CON RESERVAS para planificar Fase 3; no apto para asumir reglas de negocio ni despliegue.** Antes de implementar cobertura adicional, confirmar límites horarios, alcance Vicerrectorado, cardinalidad de propuestas, precedencia de importación y necesidad de archivos/reportes. Fase 3 debe comenzar con casos P0 documentados, sin iniciar E2E, rendimiento o cambios funcionales dentro de este reporte.

Fase 2 termina aquí. No se avanzó a Fase 3.
