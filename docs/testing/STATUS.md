# Estado de pruebas

## Estado general

**EN PROGRESO — Fase 1 completa. Fase 2 pendiente.**

Fecha de actualización: 2026-08-04

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
| `composer test` | 62/62 pruebas, 326 assertions |
| `vendor/bin/pint --test` | OK |
| `php artisan migrate:status --env=testing` | 34 migraciones OK |
| `git diff --check` | OK |

## Fase 1

Estado: **COMPLETA**

Evidencia: [PHASE_1_REPORT.md](PHASES_REPORTS/PHASE_1_REPORT.md)

- `composer.json` válido.
- 24 rutas inspeccionadas, 21 nombradas, sin nombres ni combinaciones método/URI duplicadas.
- 34 migraciones aplicadas en testing.
- Sin avisos de vulnerabilidades en `composer audit`.
- Suite: 62/62 pruebas, 326 assertions.
- Pint: OK.

## Fase siguiente

Fase 2: reglas unitarias de dominio.

Pendientes mínimos:

- Extraer reglas desde servicios, modelos, requests y migraciones.
- Separar reglas confirmadas de `NEEDS_BUSINESS_CONFIRMATION`.
- Crear casos unitarios válidos, inválidos y de límite.

## Riesgos conocidos

- No existe `package.json`; no hay pipeline frontend configurado.
- PHPStan y Pest no están configurados.
- `public/storage` no está enlazado en el ambiente local; revisar antes de pruebas de archivos públicas.
- El clúster PostgreSQL local de pruebas queda en el puerto `55432` mientras se usa.
- Las reglas universitarias no documentadas deben confirmarse antes de agregar casos.

## Archivos y comandos de la última fase

Ver [PHASE_1_REPORT.md](PHASES_REPORTS/PHASE_1_REPORT.md) y
[PHASE_0_REPORT.md](PHASES_REPORTS/PHASE_0_REPORT.md). No iniciar Fase 2 hasta
registrar su alcance y resultado aquí.
