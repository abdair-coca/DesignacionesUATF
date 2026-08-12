# Matriz de pruebas vigente

La matriz protege el comportamiento existente. `CUBIERTA` significa que hay
prueba o evidencia vigente; `PENDIENTE` es trabajo futuro; `NEEDS_BUSINESS_CONFIRMATION`
requiere una decisión antes de fijar una expectativa.

| ID | Área | Caso protegido | Estado |
| --- | --- | --- | --- |
| AUTH-001 | Acceso | Invitado no entra a rutas protegidas | CUBIERTA |
| AUTH-002 | Autorización | Director no opera otra carrera | CUBIERTA |
| AUTH-003 | Revisión | Usuario incorrecto no decide ni retira | CUBIERTA |
| AUTH-004 | Login | Rate limit y redirección por rol | CUBIERTA |
| DES-001 | Propuestas | Crear y editar borrador de gestión actual | CUBIERTA |
| DES-002 | Propuestas | No enviar propuesta incompleta | CUBIERTA |
| DES-003 | Propuestas | Bloquear edición con versión pendiente | CUBIERTA |
| DES-004 | Propuestas | Validar grupos habilitados y docente | CUBIERTA |
| DES-005 | Versionado | Una versión pendiente y número secuencial | CUBIERTA |
| DES-006 | Versionado | Concurrencia de envío/edición | PENDIENTE |
| REV-001 | Revisión | Aprobar todas las filas | CUBIERTA |
| REV-002 | Revisión | Observar fila con motivo | CUBIERTA |
| REV-003 | Revisión | Revisión mixta y `aprobada_previamente` | CUBIERTA |
| REV-004 | Revisión | Decisión duplicada o snapshot ajeno | PENDIENTE |
| IMP-001 | Importación | Rechazar misma gestión y periodo | CUBIERTA |
| IMP-002 | Importación | Previsualización sin escrituras | CUBIERTA |
| IMP-003 | Importación | Precedencia oficial/legado | CUBIERTA |
| IMP-004 | Importación | No modificar filas aprobadas previamente | CUBIERTA |
| HRS-001 | Distribución | Horas enteras, no negativas y suficientes | CUBIERTA |
| HRS-002 | Distribución | Mínimo/máximo global | NEEDS_BUSINESS_CONFIRMATION |
| DB-001 | Integridad | Foreign keys, checks e inmutabilidad | CUBIERTA |
| NOT-001 | Notificaciones | Propiedad, lectura y eventos | CUBIERTA |
| INST-001 | Jachasun | `INF` y `UATF` con conexión simulada | CUBIERTA |
| INST-002 | Jachasun | Integración deshabilitada no consulta | CUBIERTA |
| INST-003 | Jachasun | Fallo externo no filtra secretos | CUBIERTA |
| INST-004 | Jachasun | Importación de docentes/materias/grupos/horas | PENDIENTE |
| INST-005 | Pantalla | Consulta inicial y tabla institucional con siete columnas visibles; programa y codigo quedan en el encabezado | CUBIERTA |
| INST-006 | Pantalla | Invitado, director y Vicerrectorado | CUBIERTA |
| INST-007 | Jachasun | Gestión y periodo `0` sin filtro (`INF / 0 / 0`) | CUBIERTA |

| LIST-001 | Lista | Director consulta su carrera con `sigla / 0 / 0` | CUBIERTA |
| LIST-002 | Lista | `/designaciones` no mezcla propuestas locales y usa Jachasun como fuente principal | CUBIERTA |
| LIST-003 | Lista | Jachasun deshabilitado o fallido bloquea con HTTP 503 seguro | CUBIERTA |
| LIST-004 | Lista | La tabla institucional oculta codigo y programa porque ya aparecen en el encabezado | CUBIERTA |
| LIST-005 | Lista | La tabla actual agrega Fecha y Observacion y usa `r_id` en `#` | CUBIERTA |
| LIST-006 | Lista | Acciones visibles pero deshabilitadas para registros institucionales | CUBIERTA |
| LIST-007 | Lista | `SOLICITADO` se presenta como Oficial; equivalencia pendiente de confirmacion | NEEDS_BUSINESS_CONFIRMATION |

La matriz no resuelve alcance de Vicerrectorado, propuestas múltiples,
precedencia institucional ni límites horarios: esos puntos permanecen en
[`../BUSINESS_RULES.md`](../BUSINESS_RULES.md) como
`NEEDS_BUSINESS_CONFIRMATION`.
