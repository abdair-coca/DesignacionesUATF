# Matriz de pruebas

Fase 2 solo cartografía casos. No se implementaron casos nuevos. `CUBIERTA` significa evidencia en suite existente; `PENDIENTE` significa candidato; `NEEDS_BUSINESS_CONFIRMATION` requiere decisión antes de fijar expectativa.

| ID | Módulo | Caso | Prioridad | Tipo | Evidencia actual | Estado |
| --- | --- | --- | --- | --- | --- | --- |
| ENV-001 | Entorno | Rechazar producción | P0 | Seguridad | `TestingPhase0` | CUBIERTA |
| ENV-002 | Entorno | Recrear base desde cero | P0 | Integración | `TestingPhase0` | CUBIERTA |
| ENV-003 | Entorno | Rollback completo | P0 | Integración | `TestingPhase0` | CUBIERTA |
| ENV-004 | Entorno | No enviar correo/broadcast real | P0 | Integración | `TestingPhase0` | CUBIERTA |
| STATIC-001 | Dependencias | Validar Composer y auditoría | P1 | Estática | Fase 1 | CUBIERTA |
| STATIC-002 | Rutas | Detectar duplicados | P1 | Estática | `route:list --json` | CUBIERTA |
| STATIC-003 | Suite | Ejecutar backend | P0 | Salud | `composer test` | CUBIERTA |
| STATIC-004 | Frontend | Detectar pipeline | P1 | Estática | Sin `package.json` | CUBIERTA |
| STATIC-005 | Análisis | Verificar PHPStan/Pest | P1 | Estática | No configurados | CUBIERTA |
| AUTH-001 | Autenticación | Usuario no autenticado accede a ruta protegida | P0 | Feature | `PageAccessTest` | CUBIERTA |
| AUTH-002 | Autorización | Director no accede a propuesta/carrera ajena | P0 | Seguridad | `PropuestaVersionadaTest`, `RoleAuthorizationTest` | CUBIERTA |
| AUTH-003 | Autorización | Usuario incorrecto no decide/retira | P0 | Seguridad | Policies + tests existentes | CUBIERTA |
| AUTH-004 | Login | Rate limit y redirección por rol | P0 | Seguridad | `AuthenticatedSessionTest` | CUBIERTA |
| DES-001 | Propuestas | Crear borrador válido | P0 | Feature | `PropuestaVersionadaTest` | CUBIERTA |
| DES-002 | Propuestas | Enviar incompleta | P0 | Regla | `PropuestaDistribucionTest`, `DesignacionesAceptacionTest` | CUBIERTA |
| DES-003 | Propuestas | Impedir modificar versión pendiente | P0 | Regla | `PropuestaVersionadaTest` | CUBIERTA |
| DES-004 | Propuestas | Completar todos grupos habilitados | P0 | Regla | `PropuestaDistribucionTest` | CUBIERTA |
| DES-005 | Propuestas | Dos propuestas mismo contexto | P0 | Integridad | `PropuestaVersionadaTest` | CUBIERTA |
| DES-006 | Propuestas | Concurrencia de envío/edición | P0 | Concurrencia | Candidato | PENDIENTE |
| REV-001 | Revisión | Aprobar todas filas | P0 | Feature | `PropuestaRevisionTest` | CUBIERTA |
| REV-002 | Revisión | Observar fila con motivo | P0 | Feature | `PropuestaRevisionTest` | CUBIERTA |
| REV-003 | Revisión | Mezcla aprobar/observar | P0 | Regresión | `DesignacionesAceptacionTest` | CUBIERTA |
| REV-004 | Revisión | Decisión duplicada o snapshot ajeno | P0 | Integridad | Candidato | PENDIENTE |
| REV-005 | Revisión | Decisión incompleta | P0 | Regla | `PropuestaRevisionTest` | CUBIERTA |
| REV-006 | Revisión | Vicerrectorado global versus alcance restringido | P0 | Seguridad | Regla no confirmada | NEEDS_BUSINESS_CONFIRMATION |
| NOT-001 | Notificaciones | Reintento no duplica notificación | P0 | Idempotencia | `NotificacionPropuestaTest` | CUBIERTA |
| NOT-002 | Notificaciones | Marcar todas leídas | P1 | Feature | `NotificacionPropuestaTest` | CUBIERTA |
| NOT-003 | Notificaciones | Aviso pertenece solo a usuario | P0 | Seguridad | `NotificacionPropuestaTest` | CUBIERTA |
| NOT-004 | Notificaciones | URL histórica/canónica | P1 | Integración | `NotificacionPropuestaTest` | CUBIERTA |
| NOT-005 | Notificaciones | Eventos distintos no generan duplicado inesperado | P0 | Regresión | Candidato | PENDIENTE |
| IMP-001 | Importación | Misma gestión/periodo rechazada | P0 | Regla | `ImportacionPropuestaTest` | CUBIERTA |
| IMP-002 | Importación | Previsualización no escribe | P1 | Integración | `ImportacionPropuestaTest` | CUBIERTA |
| IMP-003 | Importación | Precedencia oficial versus legado | P0 | Integridad | `ImportacionPropuestaTest` | CUBIERTA |
| IMP-004 | Importación | No modificar aprobada previamente | P0 | Regla | `DesignacionesAceptacionTest` | CUBIERTA |
| DB-001 | Integridad | Foreign keys y checks | P0 | Base de datos | Migraciones/tests existentes | CUBIERTA |
| DB-002 | Integridad | Snapshots/decisiones inmutables | P0 | Base de datos | `PropuestaVersionadaTest` | CUBIERTA |
| DB-003 | Integridad | Unicidad de versión pendiente | P0 | Concurrencia | Candidato | PENDIENTE |
| HRS-001 | Carga | Horas no negativas, pagadas <= oficial, suma suficiente | P0 | Regla | `PropuestaDistribucionTest` | CUBIERTA |
| HRS-002 | Carga | Mínimo/máximo global | P0 | Regla | Config contradice servicio | NEEDS_BUSINESS_CONFIRMATION |
| FILE-001 | Archivos | Link `public/storage` disponible antes de uso | P1 | Operación | `about --env=testing` | PENDIENTE |
| FILE-002 | Archivos | GET/PUT storage autorizado y no filtra privados | P0 | Seguridad | Ruta implícita sin auth | PENDIENTE |
| REP-001 | Reportes | Exportar con filtros | P1 | Feature | No existe endpoint | NEEDS_BUSINESS_CONFIRMATION |
| PERF-001 | Rendimiento | Consultas bajo carga | P1 | Rendimiento | Fuera de Fase 2 | PENDIENTE |
| FE-001 | Frontend | Render Blade/Alpine sin build | P1 | UI | `resources/views` | CUBIERTA |
| FE-002 | Frontend | CDN no disponible | P2 | Resiliencia | Candidato | PENDIENTE |
| DOC-001 | Documentación | Resolver páginas descritas pero ausentes | P3 | Exploratoria | `PLAN_FRONTEND` versus árbol | NEEDS_BUSINESS_CONFIRMATION |

## Verificaciones operativas adicionales

| ID | Modulo | Caso | Prioridad | Evidencia | Estado |
| --- | --- | --- | --- | --- | --- |
| ENV-005 | Entorno | Reinicio normal de PostgreSQL conserva el dataset | P0 | `pg_ctl stop/start`, 5 usuarios conservados | CUBIERTA |
| ENV-006 | Entorno | Fase 0 destructiva se distingue del inicio normal | P0 | `TestingPhase0` ejecuta `migrate:fresh` | DOCUMENTADA |

## Correcciones funcionales recientes

| ID | Modulo | Caso | Prioridad | Evidencia | Estado |
| --- | --- | --- | --- | --- | --- |
| FE-003 | Frontend | Docentes con materias asignadas primero y orden alfabetico | P1 | `DesignacionesInterfazTest` | CUBIERTA |
| REV-007 | Revision | Falta motivo muestra modal y conserva campos editados | P1 | `PropuestaRevisionTest`, BUG-003 | CUBIERTA |
| REV-008 | Revision | Modal inicia oculto, abre por falta de motivo y cierra sin perder edicion | P1 | `PropuestaRevisionTest`, BUG-004, prueba navegador | CUBIERTA |

| FE-004 | Frontend | Modal genérico de impresión en lista, editor y revisión | P1 | `DesignacionesInterfazTest`, `PropuestaRevisionTest`, prueba navegador | CUBIERTA |
| FE-005 | Frontend | Paginación del editor con primera, anterior, siguiente y última | P1 | `DesignacionesInterfazTest`, prueba navegador | CUBIERTA |

## Prioridades para fase posterior

- P0 primero: autorización, estados, envío/revisión, snapshots, notificaciones, importación, constraints y storage si se usa.
- P1 después: UI, CDN, auditoría, reportes y operación.
- P2/P3: resiliencia visual, documentación y exploración.
- Ningún caso pendiente se implementó en Fase 2.

## Casos preparados por Fase 3

| ID | Módulo | Caso | Prioridad | Evidencia | Estado |
| --- | --- | --- | --- | --- | --- |
| DATA-001 | Datos | Seeder pequeño desde base vacía | P0 | `TestingSmallSeeder`, 432 designaciones | CUBIERTA |
| DATA-002 | Datos | Seeder normal con inserción por bloques | P0 | `TestingNormalSeeder`, 14.400 designaciones | CUBIERTA |
| DATA-003 | Datos | Seeder edge Unicode/estados/nullable | P1 | `TestingEdgeCasesSeeder` | CUBIERTA |
| DATA-004 | Datos | Seeder grande con multiplicador | P1 | `TestingLargeSeeder`, `0.8`, 32.768 designaciones | CUBIERTA |
| DATA-005 | Datos | Selector de perfil explícito | P1 | `TestingDatabaseSeeder` | PENDIENTE |
| DATA-006 | Datos | Rechazar seeder en producción | P0 | `TestingSeederSafety`, ejecución bloqueada | CUBIERTA |
| DATA-007 | Datos | Rechazar host/base ambiguos | P0 | `TestingSeederSafety` | PENDIENTE |
| DATA-008 | Datos | Validar conteos, estados y relaciones | P0 | `TestingDatasetValidator`, todos perfiles OK | CUBIERTA |
| DATA-009 | Factories | Crear entidades versionadas coherentes | P0 | Smoke test de seis factories | CUBIERTA |
| DATA-010 | Reproducibilidad | Recrear small desde base vacía | P0 | Ejecutado; mismo conteo observado | CUBIERTA |
| DATA-011 | Seguridad | No insertar datos deliberadamente inválidos | P1 | Validator y edge documentan rechazo futuro | CUBIERTA |
| DATA-012 | Escala | Large multiplicador 1.0 | P1 | Mecanismo probado con 0.8 | PENDIENTE |

Fase 3 prepara datos; no implementa todavía pruebas funcionales nuevas, E2E, concurrencia ni rendimiento.
