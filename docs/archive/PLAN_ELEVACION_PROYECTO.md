# Plan de elevación — Sistema de Designaciones UATF

## Propósito

Convertir sistema actual en monolito Laravel seguro, trazable y mantenible para gestión de designaciones por carrera y revisión institucional.

## Instrucciones para siguiente LLM

Este documento es fuente principal para mejora acordada. Leer también `CLAUDE.md`, `design.md`, `docs/ERD.md`, migraciones, modelos y pruebas antes de editar.

- No inventar reglas académicas, roles adicionales, límites horarios ni aprobación distinta a este documento.
- PostgreSQL es motor obligatorio. No usar SQLite.
- Aplicación es Laravel monolítica con Blade, Alpine.js y Tailwind CDN; no introducir Inertia, React, API separada, npm ni Vite.
- Mantener PSR-12, Form Requests, transacciones para cambios multi-tabla y relaciones Eloquent con claves no convencionales explícitas.
- No exponer secretos. `phpunit.xml` contiene credencial expuesta: rotarla y retirarla antes de otra entrega.
- No modificar migraciones históricas aplicadas. Crear migraciones nuevas, reversibles y con validación previa/posterior.
- Respaldar PostgreSQL antes de migraciones de Fase 1. Si una asociación histórica es ambigua, detener migración y reportar IDs ambiguos; nunca adivinar.
- Cada cambio funcional debe incluir pruebas Feature/Unit PostgreSQL y actualización de documentación/bitácora.
- Eliminar Dashboard incluye rutas, navegación, controlador, vista y pruebas. Si entorno no permite borrar archivos, retirar referencias y registrar archivos pendientes de borrado.

## Estado técnico actual

- `app/Http/Controllers/DesignacionController.php` contiene roster por carrera, copia, CRUD individual y reglas de carga horaria no aprobadas.
- `app/Http/Controllers/RevisionController.php` mezcla borrador, envío y revisión; consulta designaciones vivas en vez de snapshot.
- `app/Http/Controllers/DesignacionMasivaController.php` contiene pegado/importación alterna que debe respetar mismo modelo de borrador.
- `routes/web.php` protege globalmente solo con `auth`; rutas resource de designaciones mantienen bypasses de permisos y estado.
- `users` usa `is_admin` y `carrera_id`; objetivo es `rol` explícito.
- `materias` tiene `carrera_id`, `malla_curricular` relaciona carrera/materia y `grupos` hoy apunta a `materia_id`. Esta estructura no distingue grupos homónimos de carreras distintas.
- `designaciones` hoy guarda `Id_docente`, `Id_materia`, `Id_grupo`, `Id_gestion`, `Id_periodo`, estado y auditoría. Su integridad grupo-materia no está garantizada por DB.
- `revisiones` hoy no referencia versiones/snapshots de filas. No debe seguir siendo única fuente de propuestas.
- Vistas `resources/views/designaciones/create.blade.php`, `edit.blade.php` e `historial.blade.php` son incompletas. Flujo objetivo vive en propuesta/roster por carrera.
- `DashboardController` y `resources/views/dashboard/index.blade.php` existen, pero no hay ruta Dashboard funcional; retirarlos.
- `README.md`, `CLAUDE.md`, `opencode.md` y `docs/ERD.md` tienen contradicciones: algunos declaran Blade, otros Inertia/React; algunos declaran ausencia de roles/aprobación aunque código los implementa.

## Hallazgos que deben corregirse

1. Credencial DB en `phpunit.xml`; no debe quedar en Git ni compartirse con desarrollo.
2. Director puede alcanzar CRUD/resource y revisiones de otra carrera por IDs manipulados.
3. `solicitar`, `retirar` y `destroy` de revisión no verifican dueño/carrera de manera suficiente.
4. Revisión y aprobación actúan sobre filas actuales, por lo que una propuesta enviada cambia retroactivamente.
5. Validación permite enviar `Id_grupo` e `Id_materia` incompatibles.
6. Índice actual permite múltiples docentes activos para mismo grupo, gestión y período.
7. Copia de período puede sobrescribir filas enviadas/oficiales y degradar su estado.
8. Lógica actual suma carga existente y cambios nuevamente. No existe una regla aprobada de mínimos o máximos de carga: retirar bloqueos por horas y conservar únicamente el cálculo informativo.
9. `completar` permite cerrar revisión sin decisión explícita y acciones por lote pueden aceptar filas fuera de versión.
10. Login carece de limitación de intentos.
11. Dashboard muestra porcentajes ficticios; prohibido por `design.md`.
12. No hay CI visible para pruebas, Pint y `composer audit`.

## Arquitectura objetivo

### Catálogos académicos

- `materias` es catálogo global sin `carrera_id`.
- `malla_curricular` representa relación carrera-materia, incluso cuando materia pertenece a una sola carrera.
- `grupos` referencia `malla_curricular_id`, no `materia_id`.
- Grupo se identifica de forma única por `malla_curricular_id + codigo`.
- Código es entero positivo mostrado como texto simple: `1`, `2`, `3`.
- Misma materia y mismo código en carreras distintas son grupos distintos, estudiantes distintos y designaciones distintas.
- `gestiones` contiene marca administrativa exclusiva `es_actual`; solo una fila puede ser actual. No inferir gestión con fecha de servidor.
- Catálogos no tienen CRUD web. Carga ocurre mediante migraciones, seeders o proceso DB controlado fuera de aplicación.

### Identidad, roles y visibilidad

- `users.rol` acepta exclusivamente `director_carrera` o `vicerrectorado`.
- Director requiere `carrera_id` y no puede leer, crear, importar, retirar, modificar ni consultar historial fuera de ella.
- Vicerrectorado no tiene `carrera_id`; no administra borradores ni catálogos.
- Vicerrectorado solo puede ver versiones enviadas a revisión y sus datos históricos autorizados.
- Toda restricción se aplica en servidor mediante Policies/Gates o middleware dedicado y scopes de consulta; ocultar botones no es autorización.
- Validar pertenencia de cada ID recibido: propuesta, versión, designación, grupo, malla, carrera, gestión y período.

### Borradores, versiones y snapshots

- Existe un único borrador editable por carrera, gestión y período actuales.
- Borrador contiene designaciones editables. No usar CRUD resource individual.
- Envío crea una versión inmutable secuencial. Versión conserva relación con propuesta/borrador y número de versión.
- Snapshot debe almacenar IDs de referencia y valores de presentación al momento de envío: docente, materia, carrera, grupo, código, horas oficiales, horas pagadas, horas no pagadas, justificación de remuneración, gestión, período y estado. Así historial sobrevive cambios posteriores de catálogos.
- Versiones anteriores nunca se reescriben; retiro, reenvío, observación y aprobación son eventos/auditoría nuevos.
- Director puede retirar solo versión pendiente propia. Retiro restaura capacidad de editar borrador; siguiente envío crea versión nueva.
- Propuesta oficial y filas oficialmente aprobadas quedan bloqueadas. No borrar; anular con evento/estado cuando negocio lo permita.

### Revisión Vicerrectorado

- Bandeja muestra solo versiones con estado pendiente/enviada.
- Vicerrectorado puede aprobar versión completa o decidir por filas de ESA versión.
- Acción por fila debe validar que snapshot fila pertenece a versión solicitada.
- Observación admite texto general y texto por fila.
- Si una fila requiere observación/rechazo, versión completa queda `observada`; Director corrige y reenvía.
- Filas aprobadas individualmente quedan bloqueadas. Reenvío las muestra como `aprobada_previamente`, visibles sin controles de edición/decisión.
- No permitir finalizar versión con decisión nula. Aprobar versión completa requiere acción explícita.
- Aprobación final oficial bloquea propuesta y filas relacionadas definitivamente.

### Importación

- Solo Director dueño puede importar, solo sobre borrador de gestión actual, nuevo o existente, no enviado ni oficial.
- Origen puede ser cualquier gestión/período histórico visible de misma carrera.
- Flujo obligatorio: seleccionar origen → previsualizar diferencias → confirmar aplicación en borrador editable.
- Importación no sobrescribe versiones pendientes, observadas u oficiales.
- Importación no crea copia ineditable ni muta datos históricos.
- Importación conserva las horas pagadas, horas no pagadas y la justificación de remuneración.

### Horas y notificaciones

- Calcular y mostrar carga horaria informativa por docente. No imponer límite máximo ni mínimo automático de horas: la carga puede mostrarse como dato informativo, pero nunca debe bloquear el guardado, envío, revisión o aprobación.
- Las horas pagadas no pueden superar las horas oficiales de la materia y la suma pagada/no pagada debe cubrirlas. Las horas adicionales no pagadas no tienen tope automático y quedan a criterio de Vicerrectorado.
- Notificaciones internas: estado leído/no leído y enlace a propuesta/versión.
- Envío, reenvío y retiro notifican Vicerrectorado.
- Observación, aprobación parcial y aprobación final notifican Director dueño.

## Diseño de datos recomendado

Nombres finales pueden ajustarse a convenciones actuales, pero separar responsabilidades:

| Responsabilidad | Estructura recomendada | Reglas mínimas |
|---|---|---|
| Borrador | `propuestas` y filas de borrador | Único por carrera, gestión, período mientras editable. |
| Envío inmutable | `propuesta_versiones` | Secuencia única por propuesta; estado, enviado/retirado/revisado, usuarios y fechas. |
| Snapshot de filas | `propuesta_version_designaciones` | FK a versión; copia IDs, horas oficiales, horas pagadas, horas no pagadas, justificación, valores de visualización, decisión y observación por fila. |
| Notificación | tabla Laravel `notifications` o estructura equivalente | Usuario destinatario, tipo, datos, fecha de lectura. |
| Auditoría | historial append-only | Usuario, evento, fecha, entidad y valores relevantes. |

Usar restricciones DB además de validación Laravel:

- FK de grupo hacia malla curricular.
- Índice único `grupos(malla_curricular_id, codigo)`.
- Restricción/índice parcial para una designación activa por `grupo + gestión + período`.
- Restricción de una gestión `es_actual = true`.
- Restricciones de remuneración: horas enteras y no negativas; horas pagadas no mayores a las horas oficiales; suma pagada/no pagada suficiente para cubrir la materia.
- No crear una restricción de máximo total de horas ni de horas adicionales no pagadas.
- Restricciones de pertenencia no expresables en FK se validan en servicio/transacción y se cubren con pruebas.

## Migración Fase 1: procedimiento seguro

1. Respaldar base PostgreSQL y validar restauración.
2. Inventariar materias, mallas, grupos, designaciones y códigos existentes.
3. Crear/validar fila `malla_curricular` para cada asociación histórica `materias.carrera_id` que no exista.
4. Agregar nueva FK `grupos.malla_curricular_id`; completar usando malla de carrera histórica de materia.
5. Detectar materias compartidas sin carrera determinable. Detener y listar IDs; no mapear arbitrariamente.
6. Convertir códigos `A..Z` a `1..26`; conservar numéricos válidos; detectar colisiones por malla antes de imponer índice único.
7. Migrar designaciones para mantener relación correcta con grupo/malla/carrera.
8. Verificar conteos antes/después, FKs, unicidad y muestras por carrera.
9. Solo tras verificación, retirar `grupos.materia_id` y `materias.carrera_id` mediante migración posterior reversible.
10. Actualizar modelos, seeders, factories, servicios, queries y pruebas que usen relaciones antiguas.

## Ejecución por entregas atómicas

No intentar fases completas en un único cambio. Cada entrega debe pasar pruebas antes de siguiente.

### Fase 0

1. Extraer secreto de pruebas, documentar variables locales y preparar DB de testing aislada.
2. Añadir throttle de login y pruebas correspondientes.
3. Actualizar dependencias auditadas y aplicar Pint sin cambios funcionales.
4. Añadir CI para `php artisan test`, `vendor/bin/pint --test` y `composer audit --locked`.

### Fase 1

1. Crear migración aditiva para malla/grupo y marca `gestiones.es_actual`; añadir pruebas de esquema/datos.
2. Migrar asociaciones históricas materia-carrera hacia malla, con reporte ante ambigüedades.
3. Migrar grupos a malla y códigos alfabéticos a numéricos, detectando colisiones.
4. Migrar designaciones y añadir restricciones de unicidad/integridad.
5. Adaptar modelos, factories, seeders y servicios a relaciones nuevas.
6. Retirar columnas antiguas solo después de pruebas de regresión y verificación manual de datos.

### Fases 2 a 8

Seguir orden descrito en este plan. No implementar snapshots antes de normalización académica. No retirar CRUD individual hasta que borrador/importación nuevo esté operativo. No retirar Dashboard hasta que rutas raíz y navegación destino estén cubiertas por pruebas.

## Pruebas obligatorias

- Director recibe 403/404 seguro para recursos de otra carrera, incluso alterando IDs/URLs/payload.
- Vicerrectorado no puede leer o cambiar borradores no enviados.
- Grupo de Carrera A y grupo homónimo de Carrera B permiten docentes distintos mismo período sin colisión.
- Segundo docente activo para mismo grupo/malla/gestión/período es rechazado atómicamente.
- Envío genera snapshot; cambio posterior de borrador/catálogo no altera versión enviada.
- Distribución de materia de 6 horas: `6+0`, `4+2`, `0+6` y `6+2` son válidas.
- Distribución `5+0` para materia de 6 horas, valores negativos, decimales y horas pagadas mayores a las oficiales son rechazados.
- No existe bloqueo automático por superar 32 horas, tener menos de 6 horas o usar horas adicionales no pagadas.
- Retiro solo funciona para dueño y versión pendiente.
- Decisión por fila externa a versión es rechazada.
- Observación general y por fila se conservan; fila aprobada sigue bloqueada y visible en reenvío.
- Importación solo modifica borrador permitido y nunca versión enviada/oficial.
- Gestión no actual permite lectura histórica, pero rechaza creación, importación y envío.
- Notificaciones se crean para destinatario correcto y marcan lectura.
- Ruta raíz redirige según rol; Dashboard y CRUD individual no quedan accesibles.

## Documentación final requerida

- `README.md`: instalación segura, variables, DB de pruebas, comandos de calidad y arquitectura real.
- `docs/ERD.md`: malla/grupos normalizados, roles, propuesta, versión, snapshots, notificaciones e historial.
- `docs/LOGICA_NEGOCIO_Y_FLUJO_BOCETO.md`: reglas funcionales del boceto, incluida la distribución de horas y la ausencia de límites automáticos de carga.
- `CLAUDE.md` y `opencode.md`: unificar stack Blade/Alpine, roles reales y reglas actuales; eliminar referencias Inertia/React si persisten.
- `docs/bitacora/AAAA-MM-DD.md`: explicar cambios en lenguaje no técnico, decisiones, bloqueos y próximos pasos.
