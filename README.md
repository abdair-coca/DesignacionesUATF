# Designaciones UATF

Aplicación Laravel para la gestión de designaciones docentes. Dirección de
Carrera administra las designaciones de su carrera; Vicerrectorado dispone de
consulta institucional de solo lectura. Jachasun proporciona los datos
académicos y de designaciones.

## Stack

PHP 8.3+, Laravel 13, PostgreSQL, Blade, Alpine.js y DOMPDF.

## Inicio rápido

Consulte [`docs/README.md`](docs/README.md) para entender el flujo, la estructura,
las reglas de seguridad y las verificaciones. Revise `AGENTS.md` antes de
cualquier cambio. No configure pruebas contra producción ni contra la base
universitaria.

Comandos principales para una base PostgreSQL de testing autorizada:

```bash
php artisan test --env=testing
vendor/bin/pint --test
composer audit --locked
```

## Documentos de referencia

- [`docs/README.md`](docs/README.md): contexto principal del proyecto.
- [`docs/INTEGRATION_JACHASUN.md`](docs/INTEGRATION_JACHASUN.md): contrato de
	integración.
- [`docs/testing/`](docs/testing/): estado, matriz e informes de pruebas.
- [`docs/specs/`](docs/specs/): specs de cambios.
- [`docs/archive/`](docs/archive/): historial no normativo.
