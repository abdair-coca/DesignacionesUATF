# Designación de Materias a Docentes — UATF

Sistema web para gestionar la designación de materias a docentes en la Universidad Autónoma
Tomás Frías (UATF). Reemplaza el sistema antiguo, que no tenía control de roles.

## Stack técnico

- Laravel (última LTS estable) — monolito, sin API REST separada.
- **Blade + Alpine.js** para las vistas (diseño inspirado en Color Admin v2).
- PostgreSQL (desarrollo y producción usan el mismo motor).
- Tailwind CSS v4 (CDN).

> El diseño de interfaz replica el estilo **Color Admin v2 (SeanTheme)** implementado
> completamente con Tailwind. No se requieren npm, Vite ni node_modules.

## Instalación

Requiere PostgreSQL corriendo localmente y una base de datos creada para el proyecto
(por convención, `designaciones_uatf`).

```bash
composer install
cp .env.example .env
php artisan key:generate
# editar .env con las credenciales reales de tu Postgres local
php artisan migrate --seed
```

## Pruebas

Las pruebas deben ejecutarse en PostgreSQL, en una base aislada de desarrollo y produccion.
Por convencion se usa `designaciones_uatf_testing`.

```bash
cp .env.testing.example .env.testing
# editar .env.testing con usuario y contrasena locales de PostgreSQL
# crear la base con un usuario PostgreSQL que tenga permiso CREATEDB
createdb -h 127.0.0.1 -p 5432 -U postgres designaciones_uatf_testing
php artisan migrate:fresh --seed --env=testing
php artisan test
```

Cuando el usuario local no tiene permiso `CREATEDB`, se puede aislar testing en un esquema PostgreSQL dentro de una base local de datos ficticios. Configure `DB_DATABASE` con esa base, `DB_SCHEMA=designaciones_uatf_testing` y cree el esquema con su propietario antes de ejecutar las mismas migraciones.

`phpunit.xml` no debe contener usuarios ni contraseñas reales. Las credenciales locales viven
solo en `.env` o `.env.testing`, que no se versionan.

## Verificacion de normalizacion academica

Antes de retirar las columnas heredadas `grupos.materia_id` y `materias.carrera_id`, ejecute la auditoria de solo lectura sobre una base migrada:

```powershell
php artisan academico:verificar-normalizacion
```

El comando informa conteos de materias, mallas, grupos y designaciones, y termina con error cuando encuentra una relacion inconsistente, codigos no numericos, colisiones o designaciones activas duplicadas. Use `--muestra=20` para ampliar los IDs mostrados por hallazgo.

## Calidad

Antes de integrar cambios, ejecutar:

```bash
composer audit --locked
vendor/bin/pint --test
php artisan test
```

GitHub Actions ejecuta los mismos controles en una instancia efímera de PostgreSQL: instala
dependencias bloqueadas, audita Composer, prepara el esquema con seeders, corre pruebas y verifica Pint.

Iniciar servidor:

```bash
php artisan serve
```

## Documentación del proyecto

- [`CLAUDE.md`](./CLAUDE.md) — contexto y reglas de trabajo del proyecto.
- [`docs/ERD.md`](./docs/ERD.md) — modelo de datos.
- [`docs/bitacora/`](./docs/bitacora/) — bitácora diaria de avance.
