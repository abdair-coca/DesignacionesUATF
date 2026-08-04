# Informe Fase 0: proteccion y preparacion

## Fecha

2026-08-04

## Comando reproducible

```powershell
composer test:phase0
```

El comando inicia, si hace falta, un clúster PostgreSQL local aislado en
`storage/app/testing/postgres`, puerto `55432`, y ejecuta
`php artisan testing:phase0 --env=testing`.

## Ambiente verificado

| Elemento | Configuración |
| --- | --- |
| APP_ENV | `testing` |
| Base | `designaciones_uatf_testing` |
| Motor | PostgreSQL local, puerto `55432` |
| PHP | 8.5.4 |
| Laravel | 13.23.0 |
| Composer | 2.9.5 |
| Node | 24.14.1 |
| Correo | `array` |
| Broadcast | `null` |
| Cola | `sync` |
| Cache/sesión | `array` |
| Servicios externos | modo `fake` |
| Storage | `storage/app/testing/*` |

## Criterios de salida

- [x] No existe conexión con producción: host local y base con sufijo `_testing`.
- [x] La base puede eliminarse y recrearse con `migrate:fresh`.
- [x] No se envían correos reales.
- [x] No se escriben archivos fuera del sandbox de pruebas.
- [x] La aplicación arranca con `.env.testing`.
- [x] Las 34 migraciones funcionan desde una base vacía.
- [x] El rollback completo funciona.

## Hallazgo corregido

El rollback de `2026_07_15_000001_add_missing_indexes` fallaba porque intentaba
eliminar índices usando nombres de columna (`Id_docente`, `Id_grupo`) en vez de
los nombres derivados por Laravel. Se corrigió usando las columnas como arreglo,
lo que permite que Laravel resuelva el nombre real del índice.

## Validaciones posteriores

- `composer test:phase0`: OK.
- `composer test`: 62 pruebas, 62 aprobadas, 326 assertions.
- `vendor/bin/pint --test`: OK.
- `php artisan migrate:status --env=testing`: 34 migraciones aplicadas.
- `git diff --check`: OK.

## Riesgos pendientes

- El clúster PostgreSQL de pruebas queda ejecutándose localmente en el puerto
  `55432`; puede detenerse con `pg_ctl -D storage/app/testing/postgres stop`.
- No hay `package.json`; no existe pipeline frontend que ejecutar en esta fase.
- La simulación de APIs externas queda preparada por configuración; el proyecto
  no expone integraciones externas activas que requieran un fake adicional.
