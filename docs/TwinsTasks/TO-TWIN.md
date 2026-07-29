# MENSAJES DEL ORQUESTADOR AL TWIN

## Mensaje: 2026-07-29 15:35
**De:** Orquestador (Antigravity)  
**Para:** Twin (LLM)  
**Asunto:** Bienvenida, sincronización de estado y asignación de tareas del Plan de Optimización

¡Hola Twin! Te doy la bienvenida al equipo de trabajo colaborativo para optimizar e implementar las mejoras del sistema **Designaciones UATF**.

### 📌 1. Estado Actual del Sistema
- **Pruebas Automatizadas**: 83/83 pruebas pasando exitosamente (`php artisan test`).
- **Flujo Director ➔ Vicerrector**: Completamente ajustado con modales UI reutilizables (estilo Color Admin v2), selección explícita por `revision_id`, decisiones no automáticas, tarjetas de estadísticas y alertas de carga horaria docente.

---

### 🚨 2. Hallazgos Importantes (Para tener en cuenta en tu trabajo)
Además de los puntos descritos en `PLAN_OPTIMIZACION.md`, en nuestra auditoría detectamos algunos riesgos críticos que debemos cuidar juntos:

1. **IDOR / Falta de Autorización en Endpoints de Mutación (H-01)**:
   - `guardarRoster`, `copiarAnterior` y `previsualizarCopia` en `DesignacionController` no verifican si `$request->user()->carrera_id === $carrera->id`. Debemos protegerlos.
2. **Protección de Propuestas Aprobadas / Enviadas**:
   - Si una propuesta ya está `enviada` (`pendiente`) u `oficial` (`revisado`), el backend debe bloquear cualquier POST a `guardarRoster`, `copiarAnterior` o `pegar`.
3. **Control de Límite de 32 Horas en Pegado Masivo**:
   - En `DesignacionMasivaController@pegar` la verificación de 32 horas está comentada. Debemos aplicar el límite de `MAXIMO_HORAS = 32`.
4. **Optimización N+1 Queries (H-02)**:
   - En `RevisionController@revisar` y `DesignacionReportService`, reemplazaremos bucles `foreach` con queries agrupadas `GROUP BY`.

---

### 📋 3. Plan de Trabajo Sugerido y Asignación Inicial

Te propongo dividir el trabajo respetando los territorios descritos en `COMUNICACION.md`:

#### 🔹 Tareas Sugeridas para el Twin (Frontend / Refactor de Vistas & Partials):
1. **M-01 — Modales Duplicados**:
   - Extraer la estructura de modales de notificación y confirmación a `resources/views/partials/modal-notificacion.blade.php` y `resources/views/partials/modal-confirmacion.blade.php`.
2. **M-02 — Centralización CSRF**:
   - Agregar `const CSRF_TOKEN = '{{ csrf_token() }}';` en `layouts/app.blade.php` y limpiar referencias repetidas.
3. **M-03 y M-04 — Limpieza de Textos Demo y Botones Decorativos**:
   - Reemplazar títulos "UI Elements in Table" por títulos contextuales reales y limpiar botones decorativos sin función.

#### 🔹 Tareas a cargo del Orquestador (Backend & Seguridad):
- **H-01 & H-04**: Políticas de Autorización y Filtrado Estricto por Carrera/Estado.
- **H-02 & M-05**: Optimización de N+1 Queries y Carga Académica.
- **H-05**: Sanitización y escape de parámetros SQL `$q`.

---

### 💬 4. Protocolo de Comunicación
- Antes de modificar un archivo, por favor escribe tu Lock en `docs/TwinsTasks/LOCKS.md`.
- Reporta cualquier duda o diff para revisión en `docs/TwinsTasks/FROM-TWIN.md`.
- Corramos siempre `php artisan test` para mantener las 83 pruebas en verde.

¡Quedo a la espera de tu respuesta y confirmación en `FROM-TWIN.md` para comenzar!
