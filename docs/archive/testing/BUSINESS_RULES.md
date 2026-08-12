# Reglas de negocio observadas

Fuente: código ejecutable y migraciones inspeccionadas. No se agregan reglas universitarias externas.

## Confirmadas por el código

1. Solo existen dos roles válidos en la base: `director_carrera` y `vicerrectorado`. Un director debe tener `carrera_id`; Vicerrectorado debe tenerlo nulo (`database/migrations/*role*`, `User`).
2. Director opera propuestas de su carrera (`PropuestaPolicy`, `CarreraPolicy`, `PropuestaController`).
3. Vicerrectorado puede consultar/revisar versiones según rol y estado (`PropuestaVersionPolicy`, `RevisionPropuestaController`).
4. Crear y editar propuesta exige gestión marcada `es_actual`; la edición exige estado de propuesta `borrador` y ausencia de versión pendiente (`PropuestaService`).
5. Una fila actual identifica propuesta + grupo; la materia enviada debe pertenecer a la malla del grupo y carrera de la propuesta (`propuesta_designaciones`, `PropuestaService`).
6. Para enviar, debe existir al menos una fila y toda agrupación habilitada de la carrera debe tener fila con docente (`PropuestaService::enviar`).
7. Una propuesta no puede tener más de una versión pendiente por constraint parcial; el número de versión es secuencial por propuesta (`propuesta_versiones` migrations, `PropuestaService`).
8. El envío guarda snapshot desnormalizado. Snapshots y decisiones no pueden modificarse/eliminarse por triggers PostgreSQL (`propuesta_version_designaciones`, `propuesta_version_decisiones` migrations).
9. Revisión admite `approve_all` o decisión por filas. En modo por filas todas las filas revisables deben tener decisión única; decisión observada exige observación por fila o general; aprobada no admite observación de fila (`RevisionPropuestaService`).
10. Si una revisión contiene observaciones, la versión queda `observada`; sin observaciones queda `aprobada` y la propuesta `oficial`. En revisión mixta, filas aprobadas pasan a `aprobada_previamente`; aprobación total marca filas oficiales (`RevisionPropuestaService`).
11. Horas pagadas/no pagadas son enteros no negativos; pagadas no superan horas oficiales de materia y suma debe cubrir horas oficiales. Hay constraints de DB y validación de servicio (`PropuestaService`, migration `add_remuneration...`).
12. Importación no permite misma gestión y periodo como fuente y destino; previsualización no escribe; aplicación omite filas `aprobada_previamente`, conserva sin cambios exactos y registra evento (`ImportacionPropuestaService`).
13. Importación busca primero asignaciones oficiales de propuestas y usa legado `designaciones` para completar grupos faltantes, filtrando rechazadas y grupos no habilitados (`ImportacionPropuestaService`).
14. Retirar versión solo es posible si está pendiente y el usuario es quien envió; además debe administrar la carrera (`PropuestaVersionPolicy`, `PropuestaService`).
15. Notificaciones son persistidas en `notifications`, dirigidas a usuarios Vicerrectorado al enviar/retirar y al creador al decidir. Lectura exige pertenencia del notifiable (`PropuestaService`, `RevisionPropuestaService`, `NotificacionController`).
16. Login limita intentos a cinco por combinación email normalizado/IP durante 60 segundos y redirige Vicerrectorado a revisiones (`AuthenticatedSessionController`).
17. Diseño frontend actual es Blade + Alpine.js + Tailwind CDN. No hay compilación npm (`resources/views/layouts/app.blade.php`, `composer.json`, `docs/PLAN_FRONTEND.md`).
18. No existe flujo de archivos de designación: no hay `Storage::`, `UploadedFile`, descarga ni reporte en código de aplicación inspeccionado.

## Reglas inferidas, no confirmadas por requerimiento

- `gestiones.es_actual` parece representar única gestión editable; la base permite como máximo una actual mediante índice parcial.
- Vicerrectorado parece ser una autoridad transversal: código no vincula usuario Vicerrectorado a carrera.
- `designaciones` es fuente histórica/compatibilidad; `propuesta_*` es flujo versionado actual.
- `aprobada_previamente` parece bloquear cambios de filas ya aprobadas durante correcciones.
- La notificación representa aviso interno, no correo, broadcast ni push: configuración y `Notifiable` apuntan a base de datos.

## Reglas contradictorias o documentación desalineada

- `config/designaciones.php` define `DESIGNACIONES_MINIMO_HORAS` con comentario de mínimo 6; servicio no consulta ese valor y test/documentación indican que cargas menores a 6 o mayores a 32 no bloquean envío. No asumir límite.
- Algunas descripciones históricas hablan de propuesta “pendiente/observada” como estado global; código separa estado de `propuestas` (`borrador/oficial`) y estado de `propuesta_versiones` (`pendiente/retirada/observada/aprobada`).
- Documentación de frontend menciona páginas de dashboard/catálogos que no aparecen en `resources/views` actual; tratar como documentación histórica hasta confirmación.
- `designaciones` legado usa `propuesta/aprobada/rechazada`; flujo versionado usa observación y retiro, sin transición actual equivalente a rechazo.
- Migración posterior permite múltiples propuestas para misma carrera/gestión/periodo, mientras una lectura simplificada del dominio podría esperar una sola. La intención no está declarada en requerimiento.

## NEEDS_BUSINESS_CONFIRMATION

- Si existe mínimo, máximo o excepción de carga horaria; decidir qué hacer con `config/designaciones.php`.
- Si Vicerrectorado debe revisar todas las carreras o tener alcance por unidad/carrera.
- Si se permiten múltiples propuestas del mismo contexto académico y cómo se elige la vigente.
- Precedencia oficial-versus-legado y cuándo una asignación histórica es válida para importar.
- Significado operativo de `rechazada` legado frente a `observada`, `retirada` y corrección en flujo actual.
- Si toda agrupación habilitada debe ser obligatoria o si hay excepciones académicas.
- Reglas para gestión/periodo cerrados, conflicto de dos pestañas y reenvío después de observaciones.
- Necesidad, formato, permisos y retención de cargas, descargas o reportes; hoy no existe implementación.
- Política esperada ante notificaciones repetidas por reintento o por eventos distintos de una misma versión.
