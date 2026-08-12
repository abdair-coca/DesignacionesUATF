# Reporte Fase 3 — factories, seeders y datasets

Fecha: 2026-08-04
Alcance: solo infraestructura de datos de testing. Fase 4 no iniciada.

## 1. Resumen ejecutivo

Fase 3 implementada. Se completaron factories para entidades versionadas, se agregaron seeders aislados bajo `Database\\Seeders\\Testing`, protección contra destinos inseguros, validación reutilizable y documentación de datasets.

No se modificaron controladores, servicios, Policies, middleware ni migraciones. No se crearon pruebas funcionales masivas, E2E, carga ni rendimiento.

## 2. Archivos inspeccionados

- `AGENTS.md` y documentos de Fase 2 en `docs/testing/`.
- Factories existentes en `database/factories/`.
- Seeders existentes en `database/seeders/`.
- Modelos en `app/Models/`, relaciones, casts y fillables.
- Migraciones académicas, legado, roles, propuestas versionadas, remuneración, notificaciones y triggers.
- `composer.json`, `.env.testing`, `phpunit.xml`, `tests/TestCase.php` y suite `tests/Feature/`.
- CodeGraph CLI: índice PHP inicializado; fallback WASM activo por ausencia de `better-sqlite3`.

## 3. Inventario inicial

Factories existentes: `Carrera`, `Designacion`, `Docente`, `Gestion`, `Grupo`, `MallaCurricular`, `Materia`, `Periodo`, `User`.

Factories faltantes: `Propuesta`, `PropuestaDesignacion`, `PropuestaVersion`, `PropuestaVersionDesignacion`, `PropuestaVersionDecision`, `PropuestaEvento`.

Seeders existentes: catálogo académico, usuarios demo y designaciones demo en `database/seeders/`.

Limitaciones detectadas: seeders existentes usan correos institucionales/demo, azar no determinista, no protegen destino y no cubren entidades versionadas. No se modificaron; perfiles testing quedan separados.

## 4. Factories creadas/mejoradas

Nuevas:

- `database/factories/PropuestaFactory.php`
- `database/factories/PropuestaDesignacionFactory.php`
- `database/factories/PropuestaVersionFactory.php`
- `database/factories/PropuestaVersionDesignacionFactory.php`
- `database/factories/PropuestaVersionDecisionFactory.php`
- `database/factories/PropuestaEventoFactory.php`

Mejoradas:

- `GestionFactory`: `es_actual`, estado `actual`.
- `MallaCurricularFactory`: estados `forCarrera`/`forMateria`.
- `GrupoFactory`: estado `disabled`.
- `DocenteFactory`: `forCarrera`/`sinCarrera`.
- `DesignacionFactory`: `aprobada`/`rechazada` con estados existentes.
- Modelos versionados recibieron `HasFactory`, sin alterar comportamiento de negocio.

No se crearon estados inexistentes como “propuesta rechazada” para `Propuesta`, “docente inactivo” o “designación anulada”.

## 5. Seeders y validación

- `TestingSeederSafety.php`: bloquea producción, destinos ambiguos y staging no autorizado.
- `TestingUsersSeeder.php`: usuarios sintéticos por rol real.
- `TestingDatasetSupport.php`: catálogo, docentes, legado, escenarios versionados, eventos y notificaciones.
- `TestingDatasetValidator.php`: checks de roles, emails, estados, relaciones, unicidades y conteos.
- `TestingSmallSeeder.php`, `TestingNormalSeeder.php`, `TestingLargeSeeder.php`, `TestingEdgeCasesSeeder.php`.
- `TestingDatabaseSeeder.php`: selector explícito por `TESTING_DATASET_PROFILE`; no se conecta automáticamente al `DatabaseSeeder` estándar.

## 6. Volúmenes observados

| Perfil | Resultado principal | Conteos adicionales | Tiempo | Memoria pico |
| --- | --- | --- | ---: | ---: |
| Small | 432 designaciones | 24 docentes, 48 grupos, 5 propuestas, 4 versiones, 4 notificaciones | 1,12 s | 32 MB |
| Normal | 14.400 designaciones | 1.200 docentes, 288 grupos, 5 propuestas, 4 versiones, 4 notificaciones | 3,01 s | 36 MB |
| Edge | 288 designaciones | Unicode/Ñ/apóstrofe, grupo deshabilitado, docente sin carrera, fechas antiguas | 1,58 s | 30 MB |
| Large `0.8` | 32.768 designaciones | 6.400 docentes, 512 grupos, 4 propuestas, 3 versiones, 3 notificaciones | 4,85 s | 46 MB |

Normal y large superan 1.000 filas por bloque; se comprobó inserción por bloques.

## 7. Reproducibilidad

CI, siglas, emails, códigos y fechas se generan con índices estables. Estados se distribuyen por índice fijo. Perfil grande acepta `TESTING_LARGE_MULTIPLIER`; configuración `1` representa aproximadamente 100.000 designaciones legado. Requisito operativo: usar base vacía mediante `migrate:fresh`.

## 8. Seguridad

Guardia validada con `--env=production`: rechazó ejecución con `Testing seeders blocked: production indicator detected. No data changed.`

También rechazó `--env=local` apuntando a `designaciones_uatf:5432` sin autorización explícita por nombre ambiguo: `Testing seeders blocked: unsafe database target ... No data changed.`

También exige host seguro y nombre de base con marcador local/testing/staging, salvo autorización explícita local/staging mediante variable específica. No se usaron datos reales ni correos institucionales.

## 9. Comandos ejecutados

- `php -l` sobre modelos, factories y seeders: OK.
- `vendor/bin/pint --test`: inicialmente detectó formato; `vendor/bin/pint` corrigió solo formato de archivos nuevos; verificación final OK.
- `php artisan migrate:fresh --env=testing --force` antes de cada perfil.
- Seeders small, normal, edge y large `0.8`: OK.
- Prueba explícita de guardia producción: bloqueada correctamente.
- Factory smoke test con las seis entidades versionadas: OK.
- `composer test`: resultado final **62/62 pruebas, 326 assertions**, después de código y formato.

## 10. Validaciones de integridad

Cada seeder terminó con `TestingDatasetValidator` en estado `ok=true`:

- Foreign keys respetadas por PostgreSQL.
- Roles válidos, directores con carrera y Vicerrectorado sin carrera.
- Emails únicamente `example.test`, únicos.
- Estados permitidos.
- Grupo/malla/materia coherentes.
- Versiones pendientes únicas.
- Snapshots y decisiones únicos.
- No se insertaron filas deliberadamente inválidas.

## 11. Errores encontrados y resolución

- Primer intento small: error de tipo `Eloquent\Collection` versus `Support\Collection` en helper de gestiones/periodos. Corregido en seeder, se repitió desde base limpia.
- Factory smoke test: snapshot se intentaba actualizar después de insertar y PostgreSQL bloqueó por trigger inmutable. Corregido calculando relaciones antes del `INSERT`; smoke test posterior OK.
- Pint detectó formato en cuatro archivos; corregido con Pint.

## 12. Riesgos nuevos

- Los seeders asumen base vacía; reejecución sobre datos existentes puede producir conflictos de unicidad.
- El perfil grande completo (multiplicador 1) no fue necesario para validar mecanismo; se ejecutó 0.8 con 32.768 filas. Duración/memoria completa queda pendiente de fase de rendimiento.
- La credencial fixture está documentada solo para testing; no debe copiarse a entornos institucionales.
- No se crean escenarios inválidos en base; pruebas de rechazo deberán construirlos dentro de transacciones en fases posteriores.

## 13. Reglas pendientes

Se mantienen `NEEDS_BUSINESS_CONFIRMATION`: límites horarios, alcance de Vicerrectorado, cardinalidad de propuestas, precedencia legado/oficial, excepciones de grupos, gestión cerrada, concurrencia y reportes.

## 14. Archivos modificados/creados

Modelos con `HasFactory`:

- `app/Models/PropuestaDesignacion.php`
- `app/Models/PropuestaEvento.php`
- `app/Models/PropuestaVersion.php`
- `app/Models/PropuestaVersionDecision.php`
- `app/Models/PropuestaVersionDesignacion.php`

Factories:

- Modificados: `DesignacionFactory.php`, `DocenteFactory.php`, `GestionFactory.php`, `GrupoFactory.php`, `MallaCurricularFactory.php`.
- Creados: `PropuestaFactory.php`, `PropuestaDesignacionFactory.php`, `PropuestaVersionFactory.php`, `PropuestaVersionDesignacionFactory.php`, `PropuestaVersionDecisionFactory.php`, `PropuestaEventoFactory.php`.

Seeders/helpers creados en `database/seeders/Testing/`: `TestingSeederSafety.php`, `TestingUsersSeeder.php`, `TestingDatasetSupport.php`, `TestingDatasetValidator.php`, `TestingSmallSeeder.php`, `TestingNormalSeeder.php`, `TestingLargeSeeder.php`, `TestingEdgeCasesSeeder.php`, `TestingDatabaseSeeder.php`.

Documentación creada/actualizada: `docs/testing/DATASETS.md`, `PHASES_REPORTS/PHASE_3_REPORT.md`, `STATUS.md`, `TEST_MATRIX.md`, `RISK_REGISTER.md`.

No se modificaron migraciones, controladores, servicios, Policies, middleware ni `DatabaseSeeder`.

## 15. Addendum operativo

- La suite actualizada contiene 63 pruebas y 331 assertions.
- Se agrego regresion de persistencia de sesion en `PageAccessTest`.
- Se confirmo que reiniciar PostgreSQL con `pg_ctl stop/start` conserva 5 usuarios.
- Se documento BUG-002: `composer test:phase0` ejecuta `migrate:fresh` y deja el dataset vacio por diseño.
- La guia de levantamiento ahora separa inicio normal y validacion destructiva de Fase 0.

## 16. Recomendacion Fase 4

**Apto con reservas para planificar Fase 4.** Factories, perfiles pequeño/normal/edge, generación grande escalada y guardias están verificadas. Antes de Fase 4, conservar ejecución exclusivamente en testing/local, revisar reglas `NEEDS_BUSINESS_CONFIRMATION` y no usar `DatabaseSeeder` estándar para pruebas aisladas.

Fase 3 termina aquí. Detenerse antes de Fase 4.
