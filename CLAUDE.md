# CLAUDE.md — Sistema de Designación de Materias a Docentes (UATF)

Este archivo es leído automáticamente por Claude Code al abrir este repositorio.
Contiene el contexto del proyecto y las reglas que **siempre** debes seguir al trabajar aquí.

## Contexto del proyecto

Sistema web en Laravel para gestionar la designación de materias a docentes en toda la
Universidad Autónoma Tomás Frías (UATF). Reemplaza un sistema antiguo sin control de roles.
Desarrollado como parte de las prácticas laborales de Abdair en el datacenter de la universidad.

Puntos clave del dominio (ver `docs/ERD.md` para el modelo completo):
- Una materia puede aparecer en la malla curricular de varias carreras (tabla `malla_curricular`).
- Gestión (año, ej. "2026") y periodo (ej. "1", "2", "Verano") son catálogos globales,
  compartidos por toda la universidad.
- Los grupos pertenecen a una materia y se reutilizan entre periodos; se pueden deshabilitar
  manualmente. No se debe "corregir" este comportamiento sin consultarlo antes.
- El flujo de aprobación de designaciones aún no está definido por el supervisor — el campo
  `estado` y `aprobado_por` deben quedar preparados pero sin lógica de permisos todavía.
- No existe categoría, dedicación ni especialidad del docente en los datos disponibles.
- La tabla `designaciones` usa nombres de columna `Id_docente`, `Id_materia`, `Id_grupo`,
  `Id_gestion`, `Id_periodo` (no la convención por defecto de Laravel) para mantener
  consistencia con el resto del sistema — declara las claves foráneas explícitamente en
  cada relación Eloquent.

## Stack técnico

- Laravel (última versión LTS estable) como backend monolítico — sin API REST separada.
- **Inertia.js + React** como capa de vistas (reemplaza Blade para las páginas de la app).
  Los controllers devuelven `Inertia::render(...)`, no `view(...)`. El routing sigue siendo
  100% Laravel (`routes/web.php`), no hay cliente SPA independiente ni tokens de API.
  Páginas en `resources/js/Pages/`, layouts compartidos en `resources/js/Layouts/`.
- **PostgreSQL** en desarrollo y producción (mismo motor que la base de datos real de la
  universidad, para evitar sorpresas al desplegar). No usar SQLite en este proyecto.
- Tailwind CSS v4 (ya viene con el scaffold de Laravel) para estilos, hasta que se defina
  qué hacer con el template de interfaz (ver abajo).
- **Diseño de interfaz: seguir estrictamente `design.md`** (tokens, componentes,
  animaciones y prohibiciones — ej. los gradientes están prohibidos). Toda pantalla nueva
  o modificada debe cumplirlo.
- PSR-12 para estilo de código PHP.

## Estado actual del template de interfaz (Color Admin v2)

Los archivos de Color Admin v2 (SeanTheme) todavía no están disponibles en este equipo
(es un template de pago, no se debe descargar de otra fuente). Con el cambio a React
(Inertia), si el template llega, hay que portar su HTML/CSS a componentes React reutilizables
en `resources/js/Pages` y `resources/js/Components` — ya no aplica copiarlo como Blade.
Mientras tanto, las páginas usan Tailwind con un layout propio simple (`AppLayout.jsx`).

## Base de datos de desarrollo

- Motor: PostgreSQL local (servicio `postgresql-x64-18` en este equipo).
- Base de datos dedicada a este proyecto: `designaciones_uatf` (no reutilizar `colab_db` ni
  otras bases de datos de otros proyectos que puedan compartir el mismo servidor Postgres).
- Credenciales reales solo en `.env` (no versionado). `.env.example` trae la estructura de
  variables sin credenciales reales.

## Reglas de documentación (OBLIGATORIO)

1. Al finalizar cada sesión de trabajo, crea o actualiza el archivo
   `docs/bitacora/AAAA-MM-DD.md` (fecha de hoy) siguiendo exactamente la plantilla en
   `docs/bitacora/PLANTILLA.md`. No lo omitas aunque el avance del día haya sido pequeño.
2. La bitácora debe explicar el trabajo en términos que un supervisor no técnico pueda
   entender, no solo listar nombres de archivos o funciones.
3. Si tomaste una decisión de diseño no explícitamente pedida, regístrala en la sección
   "Decisiones tomadas" de la bitácora.
4. Si encuentras algo que requiere confirmación del supervisor, NO la asumas en silencio —
   anótala en "Bloqueos / dudas" y sigue con lo que sí esté claro.
5. Haz commit de la bitácora junto con el código del día.

## Convención de commits

Conventional Commits, en español: `feat:`, `fix:`, `docs:`, `refactor:`, `chore:`.

## Convención de ramas

- `main`: siempre estable.
- `feature/nombre-corto`: una rama por funcionalidad.
- Fusionar a `main` mediante Pull Request.

## Qué NO hacer sin preguntar

- No subir el archivo `.env` ni credenciales reales.
- No implementar lógica de roles/permisos definitiva — el sistema de roles lo implementará
  otro equipo; solo dejar los campos preparados.
- No asumir reglas de negocio no confirmadas (carga horaria máxima, aprobación, etc.).
