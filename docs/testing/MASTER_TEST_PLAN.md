# Plan maestro de pruebas

## Objetivo

Validar de forma reproducible la seguridad, integridad, reglas de negocio,
flujos críticos y comportamiento operativo del Sistema de Designación Docente.

## Reglas de ejecución

- El alcance es local, testing o staging autorizado. Producción está prohibida.
- La configuración base es `.env.testing`.
- El entorno destructivo se prepara con `composer test:phase0`.
- Para iniciar y conservar datos existentes, usar `pg_ctl` y no `composer test:phase0`.
- `composer test:phase0` ejecuta `migrate:fresh`, rollback y migracion; requiere volver a sembrar antes de probar manualmente.
- Los efectos externos deben permanecer simulados o desactivados.
- Todo fallo debe reproducirse antes de corregirse.
- Las reglas no documentadas se registran como `NEEDS_BUSINESS_CONFIRMATION`.
- Se detiene el trabajo al terminar cada fase.

## Fases

| Fase | Alcance | Salida | Estado |
| --- | --- | --- | --- |
| 0 | Protección, base, storage y dependencias externas | Entorno reproducible | COMPLETA |
| 1 | Salud estática del repositorio | Instalación, rutas y suite base | COMPLETA |
| 2 | Reglas unitarias de dominio | Casos válidos, inválidos y límites | PENDIENTE |
| 3 | Feature backend | Flujos, permisos y persistencia | PENDIENTE |
| 4 | Base de datos e integridad | Constraints, rollback y concurrencia | PENDIENTE |
| 5 | Frontend | Estados, formularios y errores | PENDIENTE |
| 6 | E2E | Flujos críticos en navegador | PENDIENTE |
| 7 | Concurrencia | Duplicaciones y conflictos | PENDIENTE |
| 8 | Rendimiento | Línea base, carga y degradación | PENDIENTE |
| 9 | Seguridad | Autenticación, autorización y entradas | PENDIENTE |
| 10 | Resiliencia | Fallos, rollback y reintentos | PENDIENTE |
| 11 | Exploratoria | Misiones con usuarios reales | PENDIENTE |

## Gate de cada fase

Antes de cerrar una fase deben existir:

1. Casos agregados a `TEST_MATRIX.md`.
2. Pruebas ejecutadas y resultado registrado.
3. Bugs reproducibles documentados.
4. Archivos y comandos listados en `STATUS.md`.
5. Riesgos y reglas ambiguas identificados.

## Comandos base

```powershell
composer test:phase0
composer test
vendor/bin/pint --test
php artisan migrate:status --env=testing
```

## Criterio final

No recomendar despliegue hasta que los flujos P0 pasen, no existan bugs P0
abiertos, las migraciones y rollback estén verificados, y los riesgos restantes
estén aceptados explícitamente.
