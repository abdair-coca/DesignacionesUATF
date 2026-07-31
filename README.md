# Sistema de Designaciones UATF

Sistema web para gestionar propuestas, revisiones y versiones de designaciones docentes de la Universidad Autonoma Tomas Frias. La aplicacion centraliza la preparacion de propuestas por carrera, la revision por Vicerrectorado y el seguimiento del estado de cada version enviada.

## Caracteristicas

- Gestion de propuestas de designacion por carrera, gestion y periodo academico.
- Versionado inmutable de propuestas enviadas a revision.
- Revision de propuestas con estados de aprobacion u observacion.
- Roles diferenciados para Direccion de Carrera y Vicerrectorado.
- Notificaciones internas para cambios relevantes del flujo.
- Normalizacion academica de carreras, materias, mallas curriculares, grupos y docentes.

## Stack

- PHP 8.4
- Laravel 13
- PostgreSQL
- Blade
- Alpine.js
- Tailwind CSS por CDN
- PHPUnit

## Instalacion

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Configure la conexion PostgreSQL en `.env` antes de ejecutar las migraciones.

## Pruebas

Las pruebas usan una configuracion aislada definida en `.env.testing`.

```bash
cp .env.testing.example .env.testing
php artisan migrate --env=testing --force
php artisan test
```

Para reiniciar completamente la base de pruebas:

```bash
php artisan migrate:fresh --seed --env=testing
```

## Calidad

```bash
composer audit --locked
vendor/bin/pint --test
php artisan test
php artisan academico:verificar-normalizacion
```

## Documentacion

- [Modelo de datos](docs/ERD.md)
- [Estado del flujo de Designaciones](docs/ESTADO_FLUJO_DESIGNACIONES.md)
- [Plan frontend](docs/PLAN_FRONTEND.md)
- [Higiene del repositorio](docs/HIGIENE_REPOSITORIO.md)

## Licencia

Proyecto academico desarrollado para apoyar la gestion interna de designaciones docentes.
