# STATUS — Sistema Designaciones UATF

Actualizado: 2026-07-29 15:42

## En progreso
- Tareas backend y refactorización a cargo del Orquestador (M-05, M-06, M-07, M-08, M-09, B-01)

## Completados
- C-01: Eliminar catalog controllers + CatalogoCrud trait
- C-02: Eliminar vistas CRUD catálogos
- C-03: Eliminar app.blade.php, views heredan directo
- C-04: Remover credenciales hardcodeadas del login
- C-05: Eliminar className React en sidebar
- C-06: Dashboard con datos reales (charts sin mock)
- C-07: Constantes carga académica
- C-08: Carreras dinámicas en sidebar revisiones
- C-09: Eliminar design.md
- Refactor Flujo Director ➔ Vicerrector (Envío por `revision_id`, decisión explícita, historial y modales UI)
- M-01: Extracción de modales reutilizables (`modal-notificacion.blade.php`, `modal-confirmacion.blade.php`)
- M-02: Centralización CSRF token en `app.blade.php` (`const CSRF_TOKEN = '{{ csrf_token() }}';`)
- M-03 & M-04: Reemplazar títulos demo ("Propuestas de Designación", "Asignación por Carrera") y limpiar botones decorativos de ventana sin función
- M-10: Traducción e idioma UI en `pendientes.blade.php` ("Folders" -> "Carpetas", "Ver Todas (Pendientes)")
- B-02: Accesibilidad en selects con `onchange` en `dashboard/index.blade.php` y `carrera.blade.php` (asociación label `for/id` + `aria-label`)
- B-03: Checkbox envuelto con `<label>` accesible `sr-only` en `pendientes.blade.php`

## Pendientes
Ver PLAN_OPTIMIZACION.md

## Bloqueos
- Ninguno por parte del Twin (Tareas M-01 a M-04, M-10, B-02, B-03 finalizadas).

