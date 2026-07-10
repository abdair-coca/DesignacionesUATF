# Designación de Materias a Docentes — UATF

Sistema web para gestionar la designación de materias a docentes en la Universidad Autónoma
Tomás Frías (UATF). Reemplaza el sistema antiguo, que no tenía control de roles.

## Stack técnico

- Laravel (última LTS estable) — monolito, sin API REST separada.
- Inertia.js + React para las vistas (reemplaza Blade en las páginas de la app).
- PostgreSQL (desarrollo y producción usan el mismo motor).
- Tailwind CSS v4.

> El proyecto está pensado para usar el template **Color Admin v2 (SeanTheme)**, pero los
> archivos del template todavía no están disponibles en este equipo. Mientras tanto, las
> vistas usan Tailwind con un layout propio simple. Ver `CLAUDE.md` para más detalle.

## Instalación

Requiere PostgreSQL corriendo localmente y una base de datos creada para el proyecto
(por convención, `designaciones_uatf`).

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# editar .env con las credenciales reales de tu Postgres local
php artisan migrate --seed
```

En dos terminales (o con `composer run dev`, que levanta ambos):

```bash
php artisan serve
npm run dev
```

## Documentación del proyecto

- [`CLAUDE.md`](./CLAUDE.md) — contexto y reglas de trabajo del proyecto.
- [`docs/ERD.md`](./docs/ERD.md) — modelo de datos.
- [`docs/bitacora/`](./docs/bitacora/) — bitácora diaria de avance.
