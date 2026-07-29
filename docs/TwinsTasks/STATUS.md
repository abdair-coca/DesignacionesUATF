# STATUS — Sistema Designaciones UATF

Actualizado: 2026-07-29 15:37

## En progreso
- Tareas backend y seguridad a cargo del Orquestador (H-01, H-02, H-04, H-05)

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

## Pendientes
Ver PLAN_OPTIMIZACION.md

## Bloqueos
- Ninguno por parte del Twin (Tareas M-01 a M-04 finalizadas y verificadas con 83/83 tests pasando).

