# Tareas Pendientes — DesignacionesUATF

Al completar una tarea: borrarla de acá y mencionarlo en `docs/bitacora/AAAA-MM-DD.md`.

---

## Frontend — Refactorización de componentes (`docs/PLAN_FRONTEND.md`)

### Hooks

- [ ] **A1 — `useDebouncedSearch` hook**
  Crear `resources/js/Hooks/useDebouncedSearch.js`. Migrar `Docentes/Index.jsx` y `Designaciones/PorCarrera.jsx`.

- [ ] **A2 — `useResumenCarga` hook**
  Crear `resources/js/Hooks/useResumenCarga.js`. Migrar `Designaciones/Create.jsx` y `Designaciones/Edit.jsx`.

### FilterBar

- [ ] **A3 — Expandir `FilterBar`**
  Agregar soporte para campo de búsqueda (`buscar` prop) y layout `compact`. Migrar `Docentes/Index.jsx`, `Materias/Index.jsx`, `Grupos/Index.jsx`, `Designaciones/PorCarrera.jsx`.

### Componentes reutilizables

- [ ] **B1 — `FormActions` componente**
  Crear `Components/FormActions.jsx`. Migrar los 14 formularios Create/Edit.

- [ ] **B2 — `DataTable` componente**
  Crear `Components/DataTable.jsx`. Migrar las 7 páginas Index.

- [ ] **B3 — `FilaAcciones` componente**
  Crear `Components/FilaAcciones.jsx`. Migrar las 7 páginas Index.

- [ ] **B4 — `GraficoAnillo` + `BarraProgreso`**
  Crear `Components/GraficoAnillo.jsx` y `Components/BarraProgreso.jsx`. Migrar desde `PorCarrera.jsx` y `Carrera.jsx`.

- [ ] **B5 — Unificar estilos Login y Copiar**
  `Login.jsx`: usar `inputClass` de `Field`. `Copiar.jsx`: usar `Select` compartido.

---

## Dashboard — Rediseño (`docs/plan_redisenar_dashboard.md`)

- [ ] **Paso 1 — Backend de datos y controlador**
  Método `evolucionDesignaciones()` en `DesignacionReportService`. Pasar `carrerasReport` y `evolucion` a la vista.

- [ ] **Paso 2 — Tarjeta "Progreso General" y barra de filtros**
  Fondo oscuro, anillo de progreso, selectores, indicador "Actualizado hace X min".

- [ ] **Paso 3 — Tarjetas de estadísticas con sparklines**
  Icono semántico + sparkline SVG en cada KPI.

- [ ] **Paso 4 — Listado carreras, gráfico evolución, prioridad atención**
  Barras de progreso por carrera, gráfico línea interactivo, tarjetas de prioridad.

- [ ] **Paso 5 — Barra lateral (aside)**
  Resumen con dona, insights, acciones rápidas.

---

## Revisión — Motivo de rechazo por fila

- [ ] **Motivo de rechazo en designaciones**
  Agregar columna `motivo_rechazo` a `designaciones`. Mostrar campo de texto en `Revisar.jsx` al rechazar una fila. Mostrar motivo en roster (`Carrera.jsx`) y dashboard.

---

## Template — Color Admin v2

- [ ] **Portar Color Admin v2 a componentes React**
  Cuando los archivos estén disponibles. Convertir HTML/CSS a componentes en `resources/js/Components/` y `resources/js/Pages/`.

---

## Arquitectura — Por definir (pendiente de supervisor/otro equipo)

- [ ] **Roles y permisos definitivos**
  Implementado solo `is_admin` para proteger revisión. El resto queda para otro equipo.

- [ ] **Reglas de negocio no confirmadas**
  Carga horaria máxima, aprobación automática, etc. Esperando definición del supervisor.
