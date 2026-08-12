# Migración Frontend — Inertia/React → Blade — COMPLETADA

> **Estado**: COMPLETADA. Toda la interfaz funciona con Blade + Alpine.js.
> Este documento queda como registro histórico del plan ejecutado.

---

## Resumen

Migración completa de Inertia.js/React a Laravel Blade monolítico con diseño
inspirado en Color Admin v2. Se implementó sección por sección replicando
la paleta de colores, tipografía y componentes del template.

## Stack final

- **Frontend**: Blade + Alpine.js + Tailwind CSS v4 (CDN)
- **No hay**: Inertia, React, Vue, Vite, npm, node_modules, resources/js/

## Vistas migradas

| Página | Ruta Blade | Estado |
|--------|-----------|--------|
| Layout base | `resources/views/layouts/app.blade.php` | ✅ |
| Header | `resources/views/layouts/header.blade.php` | ✅ |
| Sidebar | `resources/views/layouts/sidebar.blade.php` | ✅ |
| Login | `resources/views/auth/login.blade.php` | ✅ |
| Dashboard | `resources/views/dashboard/index.blade.php` | ✅ |
| Designaciones por Carrera | `resources/views/designaciones/carrera.blade.php` | ✅ |
| Lista de Designaciones | `resources/views/designaciones/lista.blade.php` | ✅ |
| Bandeja de Revisiones | `resources/views/revisiones/pendientes.blade.php` | ✅ |
| Revisar Propuesta | `resources/views/revisiones/revisar.blade.php` | ✅ |
| Catálogos (CRUDs) | `resources/views/{carreras,materias,grupos,docentes,gestiones,periodos}/` | ✅ |
