# BUG-002: Fase 0 deja vacia la base de datos de testing

## Severidad

P1 - bloquea la prueba manual si se espera conservar usuarios sembrados.

## Modulo

Infraestructura de testing y ciclo de vida de la base de datos.

## Ambiente

PostgreSQL local, `127.0.0.1:55432`, base `designaciones_uatf_testing`.

## Pasos para reproducir

1. Ejecutar el seeder small.
2. Ejecutar `composer test:phase0`.
3. Consultar `App\Models\User::count()`.

## Resultado observado

La base queda con el esquema migrado, pero sin usuarios ni designaciones.

## Causa raiz

`scripts/testing/phase0.ps1` ejecuta `php artisan testing:phase0 --env=testing`.
`app/Console/Commands/TestingPhase0.php` ejecuta `migrate:fresh`, rollback y
migracion como parte de la validacion de Fase 0. No vuelve a ejecutar seeders.

## Comportamiento esperado

El reinicio normal de PostgreSQL con `pg_ctl stop/start` conserva los datos.
La validacion destructiva de Fase 0 debe distinguirse del inicio normal.

## Evidencia

- `composer test:phase0`: deja `users=0`.
- Reinicio directo con `pg_ctl stop/start`: conserva `users=5`.
- `TestingSmallSeeder`: restaura `users=5` y `designaciones=432`.

## Mitigacion aplicada

La guia `LEVANTAR_SERVIDOR_TESTING.md` indica `pg_ctl` para el inicio normal y
reserva `composer test:phase0` para validacion destructiva.

## Estado

**DOCUMENTADO** - no requiere cambio de logica de produccion.
