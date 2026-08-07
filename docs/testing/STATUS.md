# Estado de pruebas

## Estado general

### Estado operativo actualizado

- Base testing: `designaciones_uatf_testing` en `127.0.0.1:55432`.
- Inicio normal: `pg_ctl stop/start`; conserva los registros.
- `composer test:phase0` es destructivo para los datos: ejecuta `migrate:fresh`, rollback y migracion.
- Dataset actual: perfil `large` con multiplicador `0.8`; 80 usuarios, 6.400 docentes y 32.768 designaciones.
- Login probado con cuentas sinteticas; sesion configurada con driver `file`.
- Incidente documentado: [BUG-002](BUG_REPORTS/BUG-002-testing-phase0-clears-dataset.md).
- BUG-003 mitigado: revision sin motivo muestra modal y conserva edicion.
- BUG-004 resuelto: modal de revision inicia oculto, abre ante falta de motivo y se cierra sin perder edicion.
- Docentes asignados aparecen primero y ordenados alfabeticamente por nombre.

**EN PROGRESO — Fase 3 completa. Fase 4 pendiente.**

Fecha de actualización: 2026-08-06

## Fase 0

Estado: **COMPLETA**

Evidencia: [PHASE_0_REPORT.md](PHASES_REPORTS/PHASE_0_REPORT.md)

- Base aislada: `designaciones_uatf_testing`, PostgreSQL local en `127.0.0.1:55432`.
- 34 migraciones aplicadas desde cero.
- Rollback completo verificado.
- Mail `array`, broadcast `null`, cola `sync`, cache y sesión `array`.
- Storage temporal aislado y limpiado.
- Servicios externos en modo `fake`.
- Protección contra producción implementada.

## Validación más reciente

| Comando | Resultado |
| --- | --- |
| `composer test:phase0` | OK |
| `composer test -- --env=testing` | 69/69 pruebas, 378 assertions |
| `vendor/bin/pint --test` | OK |
| `php artisan migrate:status --env=testing` | 34 migraciones OK |
| `git diff --check` | OK |

## Verificacion de lista historica y paginacion (2026-08-07)

| Comando | Resultado |
| --- | --- |
| `composer test -- --env=testing` | OK; 73/73 pruebas, 412 assertions |
| `vendor/bin/pint --test` | OK |
| `php artisan academico:verificar-normalizacion --env=testing` | OK |
| `git diff --check` | OK |
| `composer audit --locked` | BLOQUEADO por 6 avisos actuales en `league/commonmark`; pendiente actualizar dependencia |

## Verificacion focalizada de esta ejecucion

Alcance: correccion del modal de validacion de revision. No se inicia una fase
nueva; se cierra este cambio con evidencia especifica y se detiene el trabajo.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test tests/Feature/PropuestaRevisionTest.php --filter=test_revision_muestra_modal_y_conserva_edicion_si_falta_motivo` antes del cambio | FALLA esperada: faltaba la clase visual `hidden` |
| Mismo test despues del cambio | OK; 1 prueba, 15 aserciones |
| Navegador: carga inicial | OK; modal no visible |
| Navegador: fila observada sin motivo + `Confirmar Revision` | OK; modal visible |
| Navegador: `Entendido` | OK; modal oculto, decision y campo editado conservados |

| `codegraph status --json` | 123 archivos, 1.018 nodos, 1.461 relaciones; índice inicializado |
| `php artisan route:list --json` | 24 rutas; 21 nombradas; sin duplicados método/URI |
| `vendor/bin/pint --test` | OK |
| `php artisan migrate:fresh --env=testing --force` + small | OK; 432 designaciones; 1,12 s |
| `migrate:fresh` + normal | OK; 14.400 designaciones; 3,01 s |
| `migrate:fresh` + edge | OK; 288 designaciones; 1,58 s |
| `migrate:fresh` + large `TESTING_LARGE_MULTIPLIER=0.8` | OK; 32.768 designaciones; 4,85 s |
| Seeder con `--env=production` | Bloqueado; 0 cambios |

## Fase 1

Estado: **COMPLETA**

Evidencia: [PHASE_1_REPORT.md](PHASES_REPORTS/PHASE_1_REPORT.md)

- `composer.json` válido.
- 24 rutas inspeccionadas, 21 nombradas, sin nombres ni combinaciones método/URI duplicadas.
- 34 migraciones aplicadas en testing.
- Sin avisos de vulnerabilidades en `composer audit`.
- Suite: 63/63 pruebas, 331 assertions.
- Pint: OK.

## Fase 2

Estado: **COMPLETA — análisis funcional y cartografía documentados**

Evidencia: [PHASE_2_REPORT.md](PHASES_REPORTS/PHASE_2_REPORT.md)

Documentos: [SYSTEM_MAP.md](SYSTEM_MAP.md), [ENDPOINT_CATALOG.md](ENDPOINT_CATALOG.md), [BUSINESS_RULES.md](BUSINESS_RULES.md), [STATE_TRANSITIONS.md](STATE_TRANSITIONS.md), [TEST_MATRIX.md](TEST_MATRIX.md), [RISK_REGISTER.md](RISK_REGISTER.md).

- Se inspeccionaron rutas, controladores, servicios, middleware, Policies, modelos, relaciones, migraciones, vistas, configuración y pruebas.
- No se modificó lógica de producción.
- No se crearon factories/seeders, pruebas masivas, E2E ni pruebas de rendimiento.
- Frontend confirmado: Blade + Alpine CDN + Tailwind CDN; no existe `package.json` porque no hay pipeline npm/Vite.
- `public/storage` no está enlazado en testing; no afecta flujo actual porque no hay uploads/downloads/reportes implementados.
- Ambigüedades marcadas `NEEDS_BUSINESS_CONFIRMATION`.

Resultado suite al cierre de Fase 2: **OK — 62/62 pruebas, 326 assertions** (`composer test`, registro histórico).

## Fase 3

Estado: **COMPLETA — factories, seeders y datasets de testing verificados**

Evidencia: [PHASE_3_REPORT.md](PHASES_REPORTS/PHASE_3_REPORT.md)

Documentación: [DATASETS.md](DATASETS.md).

- Factories versionadas creadas; factories existentes ampliadas con estados respaldados por código.
- Seeders aislados en `database/seeders/Testing/`; `DatabaseSeeder` estándar no modificado.
- Guardia valida entorno, host, puerto, nombre de base e indicadores de producción.
- Small, normal, edge y large escalado ejecutados desde base limpia.
- Validator comprobó roles, estados, unicidades, claves y relaciones.
- Sin datos reales; emails usan `example.test`.
- No se modificaron controladores, servicios, Policies, middleware ni migraciones.
- No se crearon pruebas funcionales masivas, E2E, carga ni rendimiento.

Resultado suite al cierre de Fase 3: **OK — 62/62 pruebas, 326 assertions** (`composer test`, registro histórico).

## Fase siguiente

Fase 4: integridad de base de datos, rollback y concurrencia.

Pendientes mínimos:

- No iniciar dentro de esta ejecución.
- Priorizar casos P0 de `TEST_MATRIX.md` usando datasets `small`/`normal`.

## Verificación de impresión y navegación (2026-08-06)

Alcance: añadir el botón de impresión en lista, editor y revisión como modal
genérico, y verificar la navegación paginada del editor. La impresión real se
deja pendiente; el modal informa que la generación estará disponible después.

| Verificación | Resultado |
| --- | --- |
| Regresión backend para lista/editor | OK; `DesignacionesInterfazTest` |
| Regresión backend para revisión | OK; `PropuestaRevisionTest` |
| Navegador: modal de impresión en lista | OK; abre, muestra mensaje y cierra |
| Navegador: modal de impresión en editor | OK; abre, muestra mensaje y cierra |
| Navegador: modal de impresión en revisión | OK; abre, muestra mensaje y cierra |
| Navegador: primera/anterior al inicio | OK; ambas deshabilitadas |
| Navegador: siguiente y anterior | OK; cambia al siguiente bloque y regresa |
| Navegador: última y primera | OK; llega al último bloque y vuelve al primero |
| Lista: propuesta no oficial | OK; muestra `Abrir` |
| Lista: propuesta oficial | OK; muestra `Ver detalle` junto a `Imprimir` |
| Lista: todas las gestiones | OK; elimina el filtro y conserva propuestas de años anteriores |
| Lista: paginación | OK; 10 filas por página con navegación completa |
| Detalle oficial | OK; muestra aviso de aprobación y no el aviso de borrador bloqueado |

## Justificacion de remuneracion en revision (2026-08-07)

La revision de Vicerrectorado muestra la justificacion registrada por el
Director en una columna propia y de solo lectura, tanto para la version
pendiente como para las versiones observadas o aprobadas. El texto conserva
los saltos de linea y se muestra completo; cuando el campo es nulo o vacio se
renderiza `—`. La justificacion no se mezcla con las observaciones de
Vicerrectorado y los snapshots anteriores permanecen intactos al reenviar.

| Comando | Resultado |
| --- | --- |
| Regresion focalizada de distribucion y revision | OK; 4 pruebas, 37 aserciones |
| `composer test -- --env=testing` | OK; 73 pruebas, 412 aserciones |
| `vendor/bin/pint --test` | OK |
| `php artisan academico:verificar-normalizacion --env=testing` | OK |
| `git diff --check` | OK |
| `composer audit --locked` | BLOQUEADO por 6 avisos actuales en `league/commonmark`; pendiente actualizar dependencia |

| Verificacion | Resultado |
| --- | --- |
| Revision pendiente con justificacion | Cubierta por `PropuestaDistribucionTest` |
| Revision aprobada conserva justificacion | Cubierta por `PropuestaRevisionTest` |
| Reenvio muestra justificacion corregida y conserva la anterior | Cubierta por `PropuestaRevisionTest` |
| Justificacion vacia muestra `—` | Cubierta por `PropuestaDistribucionTest` |
| Vicerrectorado no recibe control editable | Cubierta por `PropuestaDistribucionTest` y `PropuestaRevisionTest` |

La verificación usó únicamente el dataset sintético `small` en la base de
testing local; no se conectó a producción.

## Riesgos conocidos

- No existe `package.json`; no hay pipeline frontend configurado.
- PHPStan y Pest no están configurados.
- `public/storage` no está enlazado en el ambiente local; revisar antes de pruebas de archivos públicas.
- El clúster PostgreSQL local de pruebas queda en el puerto `55432` mientras se usa.
- Las reglas universitarias no documentadas deben confirmarse antes de agregar casos.

## Archivos y comandos de la última fase

Ver [PHASE_3_REPORT.md](PHASES_REPORTS/PHASE_3_REPORT.md),
[PHASE_2_REPORT.md](PHASES_REPORTS/PHASE_2_REPORT.md),
[PHASE_1_REPORT.md](PHASES_REPORTS/PHASE_1_REPORT.md) y
[PHASE_0_REPORT.md](PHASES_REPORTS/PHASE_0_REPORT.md). Fase 3 detenida; no iniciar
Fase 4 dentro de esta ejecución.
