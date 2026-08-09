# Reporte Fase 2: guia de arquitectura y capacitacion

## Objetivo

Crear una guia basada en el codigo real para que una persona pueda entender el
sistema, seguir una peticion, ejecutar pruebas y diagnosticar problemas sin
depender de IA.

## Archivos modificados

- `docs/GUIA_APRENDIZAJE.md`
- `README.md`
- `docs/testing/STATUS.md`
- `docs/testing/TEST_MATRIX.md`

Los cambios preexistentes en `app/Http/Controllers/PropuestaController.php` y
dos vistas no fueron incluidos en esta fase.

## Contenido entregado

- mapa de capas y responsabilidades;
- rutas actuales separadas por rol;
- recorrido Director-Vicerrectorado completo;
- explicacion de propuestas, snapshots, decisiones y eventos;
- modelo de datos y transiciones de estado;
- procedimiento de diagnostico;
- comandos de instalacion, testing y verificacion;
- separacion de ambientes;
- seis ejercicios autonomos con resultado esperado;
- checklist de dominio personal;
- referencias a la documentacion existente.

## Verificaciones

| Comando o accion | Resultado |
| --- | --- |
| `php artisan route:list --json --env=testing` | Inicialmente no pudo conectar porque PostgreSQL estaba apagado |
| Inicio PostgreSQL testing en `55432` | OK; sin `migrate:fresh` durante el arranque |
| `select 1` sobre `designaciones_uatf_testing` | OK |
| `php artisan migrate:status --env=testing` | OK; migraciones aplicadas |
| `composer test -- --env=testing` primer intento | FALLA de entorno: `database system is starting up` en los 74 casos |
| `composer test -- --env=testing` reintento | OK; 74 pruebas, 431 aserciones |
| Restauracion dataset `TestingSmallSeeder` | OK; 5 usuarios, 432 designaciones, validacion OK |
| `php artisan academico:verificar-normalizacion --env=testing` | OK |
| `vendor/bin/pint --test` | OK |
| `git diff --check` | OK |

## Riesgos y pendientes

- Los ejercicios de capacitacion deben ser realizados manualmente por el
  usuario y marcados en el checklist.
- CodeGraph sigue bloqueado por SQLite y esta documentado en BUG-005.
- La Fase 3 aun debe preparar instalacion, HTTPS, permisos, respaldos y health
  checks.
- No se configuro ninguna funcion ni credencial institucional.

## Estado

COMPLETA. La Fase 3 no se inicia dentro de este cierre.
