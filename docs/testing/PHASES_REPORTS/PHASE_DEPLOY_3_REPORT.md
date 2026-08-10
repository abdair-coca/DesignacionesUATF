# Reporte Fase 3: despliegue reproducible

## Objetivo

Preparar la aplicacion para una instalacion controlada en local, staging y
produccion, sin conectar aun funciones institucionales.

## Cambios entregados

- `config/deployment.php` con guardia de integracion institucional.
- `App\\Support\\ApplicationHealth` para verificar requisitos operativos.
- `app:health` y `app:health --json`.
- `GET /health` sin autenticacion y sin datos sensibles.
- `.env.example` con integracion institucional desactivada por defecto.
- `docs/GUIA_DESPLIEGUE.md` con instalacion, HTTPS, permisos, workers, logs,
  backups, restauracion, health checks y rollback.
- Pruebas de health y de bloqueo de configuracion prematura.

## Seguridad aplicada

- La conexion institucional no es la conexion principal.
- `INSTITUTIONAL_ENABLED=true` devuelve health degradado hasta que exista el
  adaptador de la Fase 4.
- El health check no ejecuta funciones institucionales ni SQL institucional.
- El endpoint no requiere login, pero no muestra secretos, excepciones ni datos
  de negocio.
- No se agregaron credenciales reales.
- No se ejecutaron migraciones sobre una base institucional.

## Verificaciones

| Comando o accion | Resultado |
| --- | --- |
| Pruebas nuevas antes de implementar | FALLA esperada: ruta y comando inexistentes |
| `ApplicationHealthTest` despues de implementar | OK; 4 pruebas, 15 aserciones |
| `composer test -- --env=testing` | OK; 78 pruebas, 446 aserciones |
| `TestingSmallSeeder` despues de la suite | OK; 5 usuarios, 432 designaciones |
| `php artisan app:health --env=testing --json` | OK; estado `ok` |
| `php artisan academico:verificar-normalizacion --env=testing` | OK |
| `vendor/bin/pint --test` | OK |
| `git diff --check` | OK |

## Incidente de entorno

La primera ejecucion de la suite de esta fase encontro PostgreSQL testing
apagado. Se inicio unicamente el cluster aislado de `storage/app/testing` en
el puerto `55432`, sin `migrate:fresh` durante el arranque. La suite se repitio
con el servidor listo y paso.

## Riesgos pendientes

- El tipo de servidor universitario aun no esta definido: Nginx/PHP-FPM o IIS.
- El backup y restore estan documentados, pero deben probarse en staging.
- La politica de CDN y el servicio de workers dependen de la infraestructura
  universitaria.
- La Fase 4 debe implementar el adaptador institucional antes de activar la
  variable correspondiente.

## Estado

COMPLETA. La Fase 4 no se inicia dentro de este cierre.
