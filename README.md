# Sistema de Designaciones UATF

Aplicacion interna para preparar, revisar y aprobar designaciones de materias a docentes de la Universidad Autonoma Tomas Frias.

## Arquitectura

- Laravel monolitico con vistas Blade y Alpine.js.
- PostgreSQL en desarrollo, pruebas y produccion.
- Tailwind CSS por CDN; no requiere Node, Vite ni una SPA independiente.
- El acceso requiere sesion. Hay dos roles: `director_carrera` y `vicerrectorado`.

## Flujo operativo

1. El Director entra a `/designaciones`, abre las designaciones de su carrera y asigna docentes por grupo.
2. Al enviar, se crea una version inmutable con una copia de cada fila.
3. Vicerrectorado entra a `/revisiones/pendientes`, revisa la version y aprueba u observa filas o toda la revision.
4. El Director corrige el mismo borrador y reenvia una nueva version cuando corresponda.
5. Las notificaciones internas enlazan a la pantalla vigente y las URLs antiguas almacenadas se traducen al abrirse.

La tabla historica `designaciones` se conserva solo como fuente de importacion. El trabajo diario no usa su CRUD ni la tabla `revisiones` historica.

## Estado del flujo

La arquitectura, los permisos, los borradores, las versiones inmutables, la revision, la importacion y las notificaciones ya estan implementados. Aun falta completar la validacion integral de usuario del flujo de Designaciones con datos representativos antes de considerarlo aceptado para operacion. Consulte [Estado del flujo de Designaciones](docs/ESTADO_FLUJO_DESIGNACIONES.md).

## Instalacion

```bash
composer install
cp .env.example .env
php artisan key:generate
# Configurar PostgreSQL local en .env
php artisan migrate --seed
php artisan serve
```

## Pruebas

Las pruebas usan PostgreSQL y una base o esquema aislado. Las credenciales van exclusivamente en `.env.testing`.

```bash
cp .env.testing.example .env.testing
php artisan migrate --env=testing --force
php artisan test
```

Si se crea una base desde cero, puede usarse `php artisan migrate:fresh --seed --env=testing`.

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
- [Grafo de conocimiento](docs/GRAFO_CONOCIMIENTO.md)
- [Contexto de trabajo](CLAUDE.md)
- [Bitacora](docs/bitacora/)
