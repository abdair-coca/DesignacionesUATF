# STATUS — Sistema Designaciones UATF

Actualizado: 2026-07-29 16:10

## En progreso
- (esperando próximo movimiento)

## Completados

### Fase 0 — Limpieza inicial (Orquestador)
- C-01: Eliminar catalog controllers + CatalogoCrud trait
- C-02: Eliminar vistas CRUD catálogos
- C-03: Eliminar app.blade.php, views heredan directo
- C-04: Remover credenciales hardcodeadas del login
- C-05: Eliminar className React en sidebar
- C-06: Dashboard con datos reales (charts sin mock)
- C-07: Constantes carga académica (MAXIMO_HORAS, getMaximo())
- C-08: Carreras dinámicas en sidebar revisiones
- C-09: Eliminar design.md

### Fase 1 — Backend (Twin + Orquestador)
- H-01: Autorización en mutaciones (guardarRoster, copiarAnterior + inmutabilidad propuestas)
- H-03: getLimite() → getMinimo()/getMaximo() en todos los callers
- H-04: completar() solo afecta designaciones con estado=propuesta
- H-05: SQLi sanitization en LIKE ($q)
- M-05: guardarRoster usa CargaAcademicaService::horasAsignadas()
- M-06: gruposOcupados() extraído a método privado
- M-07: designacionesEnOrigen() extraído a método privado
- M-08: Null safety consistente (?-> en docente, gestion, periodo)
- M-09: DB::transaction() en solicitar() write paths

### Fase 2 — Frontend (Twin)
- M-01: Modales extraídos a partials reutilizables
- M-02: CSRF_TOKEN centralizado en app.blade.php
- M-03/M-04: Textos demo reemplazados, botones decorativos eliminados
- M-10: Traducciones UI (Folders→Carpetas, View All→Bandeja)
- B-02: Accesibilidad selects (label for/id + aria-label)
- B-03: Checkbox labels accesibles

## Pendientes (baja prioridad)
- H-02: N+1 en pendientes() COUNT dentro del map (1 query por revisión)
- M-11: Archivos de idioma (lang)
- B-01: Fragile fallbacks ?? 1, ?? 0 en DesignacionController
- B-04: Alpine inline consolidation (~720 líneas)

## Bloqueos
- Ninguno
