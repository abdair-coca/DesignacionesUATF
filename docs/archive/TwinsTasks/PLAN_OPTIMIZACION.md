# PLAN DE OPTIMIZACIÓN — Sistema Designaciones UATF

Generado: 2026-07-29
Estado: Arranque
Prioridad: Alta = bloqueante, Media = importante, Baja = mejora

## Leyenda

- [ ] Pendiente
- [x] Completado
- [~] En progreso

## ✅ COMPLETADO (fase inicial)

| ID | Issue | Archivos | Commit |
|----|-------|----------|--------|
| C-01 | Eliminar catalog controllers + CatalogoCrud trait | 7 PHP files | `13e5dbf` |
| C-02 | Eliminar vistas CRUD catálogos | 18 Blade files | `24a6a81` |
| C-03 | Eliminar app.blade.php, views heredan directo | app.blade.php + 4 views | `969ba9e` |
| C-04 | Remover credenciales hardcodeadas del login | login.blade.php | `26b8497` |
| C-05 | Eliminar className React en sidebar | sidebar.blade.php | `3386934` |
| C-06 | Dashboard con datos reales (charts sin mock) | dashboard/index.blade.php | `9f3bcce` |
| C-07 | Constantes carga académica | CargaAcademicaService.php | `9c7a002` |
| C-08 | Carreras dinámicas en sidebar revisiones | pendientes.blade.php | `a56deba` |
| C-09 | Eliminar design.md (placeholder) | design.md | `d0d6d59` |

---

## 📋 PENDIENTES POR PRIORIDAD

---

### 🟠 ALTO

#### H-01 — Autorización en mutaciones
**Archivos:** `DesignacionController`, `DesignacionMasivaController`, `RevisionController`
**Problema:** Cualquier user logueado escribe en cualquier carrera. No hay policies ni gates.
**Tareas:**
- [ ] Crear `app/Policies/DesignacionPolicy.php`
- [ ] Registrar en `AuthServiceProvider`
- [ ] Gate `revisar` para vicerrector (is_admin)
- [ ] Gate `modificar` para director (carrera_id match)
- [ ] Aplicar en: `guardarRoster`, `copiarAnterior`, `pegar`, `solicitar`, `procesar`, `completar`
**Dependencias:** Ninguna
**Estimado:** 3-4 hrs

#### H-02 — N+1 Queries (3 puntos)
**Archivos:**
- `DesignacionReportService.php:61-78` — resumenPorCarrera: 1 query por carrera
- `RevisionController.php:227-231` — COUNT designaciones por revision
- `RevisionController.php:283-291` — SUM horas por docente en loop
**Solución:** Subquery con JOIN + GROUP BY en 1 consulta
**Tareas:**
- [ ] Refactor `resumenPorCarrera()` a sola query con LEFT JOIN + GROUP BY
- [ ] Refactor conteo revisiones con subquery eager-load
- [ ] Refactor cargas globales docentes con query agrupada
**Dependencias:** Ninguna
**Estimado:** 2 hrs

#### H-03 — `getLimite()` semánticamente roto
**Archivos:** `CargaAcademicaService.php:22-25`, `DesignacionController.php:207`
**Problema:** `getLimite()` retorna 6 (mínimo), no 32 (máximo). Vista recibe `limite: 6`.
**Tareas:**
- [ ] Marcar `getLimite()` como deprecado definitivamente
- [ ] Reemplazar usos: donde es máximo usar `MAXIMO_HORAS`, donde es mínimo usar `getMinimo()`
- [ ] En `DesignacionController.php:207` pasar nombre correcto a la vista
**Dependencias:** Ninguna
**Estimado:** 30 min

#### H-04 — `completar()` aprueba designaciones fuera de revisión
**Archivo:** `RevisionController.php:439-451`
**Problema:** Al aprobar todo, marca oficiales TODAS las de la carrera, no solo las de la revisión.
**Tareas:**
- [ ] Filtrar designaciones por `Id_grupo IN (grupos de la revision)`
- [ ] Solo marcar `estado=oficial` en las que pertenecen a la revision
**Dependencias:** Ninguna
**Estimado:** 30 min

#### H-05 — SQL Injection potencial en LIKE
**Archivo:** `RevisionController.php:213-219`
**Problema:** `$q` sin sanitizar va a `LIKE "%{$q}%"`.
**Tareas:**
- [ ] Escapar `%` y `_` en `$q` con `str_replace`
- [ ] Validar longitud máxima (ej. max:100)
- [ ] Opcional: usar expresiones regular para permitir solo alfanumérico+espacios
**Dependencias:** Ninguna
**Estimado:** 15 min

---

### 🟡 MEDIO

#### M-01 — Modales duplicados (notificación + confirmación)
**Archivos:** `lista.blade.php:451-538`, `carrera.blade.php:629-716`
**Problema:** ~100 líneas de HTML/Alpine copiadas idénticas en 2 archivos.
**Tareas:**
- [ ] Crear `resources/views/partials/modal-notificacion.blade.php`
- [ ] Crear `resources/views/partials/modal-confirmacion.blade.php`
- [ ] Reemplazar duplicados con `@include`
- [ ] Mover funciones Alpine (`mostrarNotificacion`, etc.) a archivo compartido
**Dependencias:** Ninguna
**Estimado:** 2 hrs

#### M-02 — CSRF token repetido 8+ veces
**Archivos:** `lista.blade.php`, `carrera.blade.php`, `revisar.blade.php`
**Problema:** `document.querySelector('meta[name="csrf-token"]')...` repetido.
**Tareas:**
- [ ] Agregar `<script>const CSRF_TOKEN = '{{ csrf_token() }}';</script>` en `layouts/app.blade.php`
- [ ] Reemplazar todas las ocurrencias por `CSRF_TOKEN`
**Dependencias:** Ninguna
**Estimado:** 15 min

#### M-03 — Texto placeholder "UI Elements in Table"
**Archivos:** `lista.blade.php:58`, `carrera.blade.php:160`
**Problema:** Texto demo Color Admin visible en producción.
**Tareas:**
- [ ] Reemplazar con título contextual ("Propuestas de Designación", "Asignación por Carrera")
**Dependencias:** Ninguna
**Estimado:** 5 min

#### M-04 — Botones decorativos de ventana sin función
**Archivos:** `lista.blade.php:63-68`, `carrera.blade.php:165-170`
**Problema:** Círculos rojo/amarillo/verde + ⤢ ↻ − × no hacen nada.
**Tareas:**
- [ ] Opción A: Eliminarlos completamente
- [ ] Opción B: Darles función real (fullscreen, reload, minimize)
**Dependencias:** Ninguna
**Estimado:** 15 min

#### M-05 — `guardarRoster()` reimplementa `horasAsignadas()`
**Archivo:** `DesignacionController.php:241-254`
**Problema:** Cálculo de horas totales inline en vez de llamar `$this->cargaAcademica->horasAsignadas()`.
**Tareas:**
- [ ] Reemplazar consulta manual por llamada al service
**Dependencias:** H-03 (getLimite)
**Estimado:** 15 min

#### M-06 — Query duplicada "grupos ocupados" en pegado masivo
**Archivo:** `DesignacionMasivaController.php:34-37 + 102-105`
**Problema:** Misma consulta copiada en `pegar()` y `previsualizar()`.
**Tareas:**
- [ ] Extraer a método privado `gruposOcupados(int $gestionId, int $periodoId): array`
**Dependencias:** Ninguna
**Estimado:** 10 min

#### M-07 — Query duplicada "designaciones origen" en copiar
**Archivo:** `DesignacionController.php:331-336 + 409-414`
**Problema:** Misma consulta con `with` diferente.
**Tareas:**
- [ ] Extraer a método privado reutilizable
**Dependencias:** Ninguna
**Estimado:** 10 min

#### M-08 — Null safety inconsistente
**Archivo:** `DesignacionController.php:450,452 vs 503,525`
**Problema:** Unas líneas usan `?->`, otras crashean si docente/gestion es null.
**Tareas:**
- [ ] Unificar a `?->` en todas las líneas: 479, 503, 525, 526
**Dependencias:** Ninguna
**Estimado:** 10 min

#### M-09 — Missing DB transaction en solicitar()
**Archivo:** `RevisionController.php:111-116`
**Problema:** Update de revisión existente fuera de transacción.
**Tareas:**
- [ ] Envolver update en `DB::transaction()`
**Dependencias:** Ninguna
**Estimado:** 10 min

#### M-10 — Inglés/español mezclado en UI
**Archivos:** `pendientes.blade.php:24 "Folders"`, `:112 "View All"`
**Tareas:**
- [ ] "Folders" → "Carpetas"
- [ ] "View All (Pendientes)" → "Ver Todas (Pendientes)"
**Dependencias:** Ninguna
**Estimado:** 5 min

#### M-11 — Sin archivos de idioma (lang)
**Archivos:** Todo el proyecto
**Problema:** Cero uso de `__()` o `@lang()`. Todo hardcodeado en español con mezcla inglés.
**Tareas:**
- [ ] Crear `resources/lang/es.json`
- [ ] Migrar textos principales a `__()` (alcance: definir hasta dónde llegar)
**Dependencias:** Ninguna (proyecto grande)
**Estimado:** 4-6 hrs (opcional)

---

### 🟢 BAJO

#### B-01 — Fragile fallback con IDs hardcodeados
**Archivo:** `DesignacionController.php:49,117,118`
**Problema:** `?? 1` y `?? 0` asumen que esos IDs existen.
**Tareas:**
- [ ] Reemplazar con manejo de error explícito (abort 404 o redirect con mensaje)
**Dependencias:** Ninguna
**Estimado:** 15 min

#### B-02 — `onchange` en selects sin accesibilidad teclado
**Archivos:** `dashboard/index.blade.php:27`, `por-carrera.blade.php:16,22`, `carrera.blade.php:89,95`
**Problema:** `onchange="this.form.submit()"` no funciona con Enter/Espacio.
**Tareas:**
- [ ] Agregar botón submit explícito o `@keydown.enter`
**Dependencias:** Ninguna
**Estimado:** 30 min

#### B-03 — Checkbox sin `<label>` en pendientes
**Archivo:** `pendientes.blade.php:108`
**Tareas:**
- [ ] Envolver checkbox en `<label>` con texto oculto (sr-only)
**Dependencias:** Ninguna
**Estimado:** 5 min

#### B-04 — Alpine.js inline ~720 líneas en 4 archivos
**Archivos:** `dashboard`, `lista`, `carrera`, `revisar`
**Problema:** JS inline difícil de mantener si sigue creciendo.
**Tareas:**
- [ ] Consolidar funciones Alpine compartidas en `public/js/app.js`
- [ ] Dejar solo config inicial en Blade (datos PHP con @json)
**Dependencias:** M-01 (modales)
**Estimado:** 3-4 hrs

---

## 📊 MAPA DE DEPENDENCIAS

```
H-01 (autorización)    → sin dependencias
H-02 (N+1)             → sin dependencias
H-03 (getLimite)       → M-05 depende de H-03
H-04 (aprobar todo)    → sin dependencias
H-05 (SQLi)            → sin dependencias
M-01 (modales)         → sin dependencias
M-02 (CSRF)            → sin dependencias
M-03 (texto demo)      → sin dependencias
M-04 (botones deco)    → sin dependencias
M-05 (guardarRoster)   → espera H-03
M-06 (query duplicada) → sin dependencias
M-07 (query duplicada) → sin dependencias
M-08 (null safety)     → sin dependencias
M-09 (transaction)     → sin dependencias
M-10 (i18n textos)     → sin dependencias
M-11 (lang files)      → sin dependencias
B-01 (fallbacks)       → sin dependencias
B-02 (accesibilidad)   → sin dependencias
B-03 (checkbox label)  → sin dependencias
B-04 (Alpine inline)   → espera M-01
```

## 🧩 ASIGNACIÓN SUGERIDA

### LLM-A (Backend — PHP)
Prioridad: H-01, H-02, H-03, H-04, H-05, M-05, M-06, M-07, M-08, M-09, B-01
Archivos: Controllers, Services, Policies, Requests
Total: ~11 issues

### LLM-B (Frontend — Blade/Alpine)
Prioridad: M-01, M-02, M-03, M-04, M-10, M-11, B-02, B-03, B-04
Archivos: Views, Partials, JS
Total: ~9 issues

---

## 🚩 BLOQUEOS / DUDAS

- Autorización definitiva la implementará otro equipo (ver CLAUDE.md). H-01 debe ser temporal hasta entonces.
- No asumir reglas de carga horaria máxima sin confirmación del supervisor.
- M-11 (lang) es opcional — solo si se planea multi-idioma.
