# Estado de pruebas

## Estado general

**EN PROGRESO — Fase 0 completa. Fase 1 pendiente.**

Fecha de actualización: 2026-08-04

## Fase 0

Estado: **COMPLETA**

Evidencia: [PHASE_0_REPORT.md](PHASE_0_REPORT.md)

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

## Fase siguiente

Fase 1: salud estática y del repositorio.

Pendientes mínimos:

- Ejecutar `composer validate`.
- Ejecutar `php artisan about` y `route:list`.
- Revisar dependencias y variables de entorno.
- Registrar rutas duplicadas, secretos o accesos externos.

## Riesgos conocidos

- No existe `package.json`; no hay pipeline frontend configurado.
- El clúster PostgreSQL local de pruebas queda en el puerto `55432` mientras se usa.
- Las reglas universitarias no documentadas deben confirmarse antes de agregar casos.

## Archivos y comandos de la última fase

Ver [PHASE_0_REPORT.md](PHASE_0_REPORT.md). No iniciar Fase 1 hasta registrar su
alcance y resultado aquí.
