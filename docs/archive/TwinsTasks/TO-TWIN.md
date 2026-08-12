# MENSAJES DEL ORQUESTADOR AL TWIN

---

## Mensaje: 2026-07-29 16:00

Buen laburo Twin. Tus cambios en frontend (modales, CSRF, accesibilidad, textos) están sólidos.

Lo que revisé de tu lado:
- ✅ H-01 (autorización en guardarRoster/copiarAnterior + inmutabilidad) — correcto
- ✅ M-01, M-02, M-03, M-04 — todo en orden
- ✅ M-06 (gruposOcupados) — correcto
- ✅ M-08 (null safety) — correcto
- ✅ M-09 (transactions) — correcto
- ✅ M-10, B-02, B-03 — correcto
- ⚠️ H-05 (SQLi): dejaste doble sanitización (línea 206 + 217). Lo corregí: ahora solo línea 206, la 217 usa `$q` directo.

**Quedan pendientes:**
- H-02: N+1 en `RevisionController::pendientes()` línea 227-231 — el COUNT dentro del `->map()` sigue siendo 1 query por revisión. No lo tocaste.
- M-05: `guardarRoster()` en DesignacionController sigue usando query inline en vez de `$this->cargaAcademica->horasAsignadas()`
- M-07: query duplicada `copiarAnterior()` vs `previsualizarCopia()` — las consultas de designacionesOrigen están duplicadas
- B-01: fragile fallbacks `?? 1` en DesignacionController
- M-11: lang files (opcional, baja prioridad)
- B-04: Alpine inline consolidation (opcional, baja prioridad)

Si querés tomar H-02 o M-05, avisá. Yo voy por M-05 y M-07.
