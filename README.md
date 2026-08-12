# Sistema de Designaciones UATF

Sistema web para gestionar propuestas, revisiones y versiones de designaciones docentes de la Universidad Autonoma Tomas Frias. La aplicacion centraliza la preparacion de propuestas por carrera, la revision por Vicerrectorado y el seguimiento del estado de cada version enviada.

## Caracteristicas

- Gestion de propuestas de designacion por carrera, gestion y periodo academico.
- Versionado inmutable de propuestas enviadas a revision.
- Revision de propuestas con estados de aprobacion u observacion.
- Roles diferenciados para Direccion de Carrera y Vicerrectorado.
- Notificaciones internas para cambios relevantes del flujo.
- Flujo de boceto para distribuir cada materia entre horas pagadas, no pagadas y horas adicionales no pagadas.
- Bandeja de Vicerrectorado con descripcion de propuesta y revision visual alineada al boceto.
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

Para levantar el servidor con PostgreSQL de testing y conservar los datos,
seguir [la guia de testing](docs/TESTING.md). No usar
`composer test:phase0` como inicio normal: ese comando valida Fase 0 y reinicia
la base de datos de testing.

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

## Boceto interactivo del flujo

El repositorio incluye un boceto independiente para revisar la experiencia de usuario sin modificar la interfaz operativa ni requerir inicio de sesion o backend.

Con el servidor local iniciado, abra:

```text
http://127.0.0.1:8000/boceto-designaciones/
```

El avatar superior permite alternar entre tres Direcciones de Carrera y Vicerrectorado. El boceto simula el recorrido completo: crear o copiar una propuesta, editar, importar, guardar, enviar a revision, observar una designacion, corregir, reenviar y aprobar. La aprobacion final bloquea la propuesta. Todos los datos y transiciones son simulados.

## Calidad

```bash
composer audit --locked
vendor/bin/pint --test
php artisan test
php artisan academico:verificar-normalizacion
```

## Documentacion vigente

- [Arquitectura y límites](docs/ARCHITECTURE.md)
- [Reglas de negocio verificadas](docs/BUSINESS_RULES.md)
- [Operación segura](docs/OPERATIONS.md)
- [Pruebas y trazabilidad](docs/TESTING.md)
- [Integración de Jachasun](docs/INTEGRATION_JACHASUN.md)
- [Higiene y organización del repositorio](docs/HIGIENE_REPOSITORIO.md)

Los documentos anteriores se conservan en [docs/archive](docs/archive/); no
son fuente normativa vigente.

## Licencia

Proyecto academico desarrollado para apoyar la gestion interna de designaciones docentes.
