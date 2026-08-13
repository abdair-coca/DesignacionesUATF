# Matriz de pruebas vigente

`CUBIERTA` significa que existe una prueba o evidencia vigente.
`PENDIENTE` requiere trabajo futuro y `NEEDS_BUSINESS_CONFIRMATION` requiere
una decisión universitaria antes de fijar una expectativa.

| ID | Area | Caso protegido | Estado |
| --- | --- | --- | --- |
| AUTH-001 | Acceso | Invitado no entra a rutas protegidas | CUBIERTA |
| AUTH-002 | Autorizacion | Director no opera otra carrera | CUBIERTA |
| AUTH-003 | Revision | Usuario incorrecto no decide ni retira | CUBIERTA |
| AUTH-004 | Login | Rate limit y redireccion por rol | CUBIERTA |
| DES-001 | Propuestas | Crear y editar borrador de gestion actual | CUBIERTA |
| DES-002 | Propuestas | No enviar propuesta incompleta | CUBIERTA |
| DES-003 | Propuestas | Bloquear edicion con version pendiente | CUBIERTA |
| DES-004 | Propuestas | Validar grupos habilitados y docente | CUBIERTA |
| DES-005 | Versionado | Una version pendiente y numero secuencial | CUBIERTA |
| DES-006 | Versionado | Concurrencia de envio/edicion | PENDIENTE |
| REV-001 | Revision | Aprobar todas las filas | CUBIERTA |
| REV-002 | Revision | Observar fila con motivo | CUBIERTA |
| REV-003 | Revision | Revision mixta y `aprobada_previamente` | CUBIERTA |
| REV-004 | Revision | Decision duplicada o snapshot ajeno | PENDIENTE |
| IMP-001 | Importacion | Rechazar misma gestion y periodo | CUBIERTA |
| IMP-002 | Importacion | Previsualizacion sin escrituras | CUBIERTA |
| IMP-003 | Importacion | Precedencia oficial/legado | CUBIERTA |
| IMP-004 | Importacion | No modificar filas aprobadas previamente | CUBIERTA |
| HRS-001 | Distribucion | Horas enteras, no negativas y suficientes | CUBIERTA |
| HRS-002 | Distribucion | Minimo/maximo global | NEEDS_BUSINESS_CONFIRMATION |
| DB-001 | Integridad | Foreign keys, checks e inmutabilidad | CUBIERTA |
| NOT-001 | Notificaciones | Propiedad, lectura y eventos | CUBIERTA |
| JAC-001 | Jachasun | `INF / 0 / 0` con conexion simulada | CUBIERTA |
| JAC-002 | Jachasun | Consulta parametrizada y transaccion `READ ONLY` | CUBIERTA |
| JAC-003 | Jachasun | Fallo externo no filtra secretos | CUBIERTA |
| JAC-004 | Jachasun | Importacion de docentes/materias/grupos/horas | PENDIENTE |
| LIST-001 | Lista | Director consulta su carrera con `sigla / 0 / 0` | CUBIERTA |
| LIST-002 | Lista | `/designaciones` no mezcla propuestas locales | CUBIERTA |
| LIST-003 | Lista | Jachasun fallido bloquea con HTTP 503 seguro | CUBIERTA |
| LIST-004 | Lista | Codigo y programa quedan en el encabezado | CUBIERTA |
| LIST-005 | Lista | Fecha, observacion y `r_id` se muestran correctamente | CUBIERTA |
| LIST-006 | Lista | Acciones visibles pero deshabilitadas | CUBIERTA |
| LIST-007 | Lista | `SOLICITADO` se presenta como Oficial | NEEDS_BUSINESS_CONFIRMATION |
| AUTH-005 | Login | Proveedor demo aislado con cuatro cuentas y carrera | CUBIERTA |
| AUTH-006 | Login | Contraseña demo incorrecta y sesión persistente | CUBIERTA |

La matriz no resuelve alcance de Vicerrectorado, precedencia institucional ni
limites horarios. Esos puntos permanecen en `BUSINESS_RULES.md`.
