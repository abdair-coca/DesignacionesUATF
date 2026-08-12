# Estados y transiciones

No hay enum ni máquina de estados única. Transiciones se distribuyen entre servicios, Policies, constraints y estados derivados de la interfaz.

## Propuesta (`propuestas.estado`)

| Estado | Transición | Disparador | Evidencia |
| --- | --- | --- | --- |
| `borrador` | `oficial` | Revisión sin observaciones | `RevisionPropuestaService::decidir` |
| `borrador` | permanece `borrador` | Envío pendiente, retiro, observación o edición | `PropuestaService`, `RevisionPropuestaService` |
| `oficial` | ninguna funcional confirmada | Propuesta aprobada | `PropuestaPolicy`, `PropuestaService` |

La UI deriva: `propuesta` si borrador sin pendiente/observada; `enviado` si tiene pendiente; `con_observaciones` si tiene versión observada; `oficial` si estado oficial (`PropuestaController@index`). No existe `propuestas.estado=pendiente` ni `observada` en la migración actual.

## Versión (`propuesta_versiones.estado`)

```text
pendiente ── retirar por remitente ──> retirada
pendiente ── revisar con alguna observación ──> observada
pendiente ── revisar sin observaciones ──> aprobada
```

- Solo una versión `pendiente` por propuesta.
- Una versión retirada no se reabre; el reenvío crea número nuevo.
- Revisión exige versión pendiente y snapshots revisables.
- `retirada`, `observada` y `aprobada` son visibles según permisos/filtros; la Policy de vista permite esos estados a Vicerrectorado.

## Filas actuales (`propuesta_designaciones.estado`)

```text
propuesta ── aprobación mixta con otras observadas ──> aprobada_previamente
propuesta ── aprobación total sin observaciones ──> oficial
```

- Fila con `aprobada_previamente` no cambia docente durante edición/importación.
- La fila observada no recibe, según servicio inspeccionado, un estado persistido `observada`; la observación se representa en snapshot/decisión y comparación de valores para la UI. Confirmar si se requiere estado de fila explícito.
- `propuesta_designaciones` no tiene estado `pendiente` global; la pendiente pertenece a la versión.

## Snapshot y decisión

- Snapshot se crea al enviar y congela docente, materia, grupo, malla, carrera, gestión, periodo, estado, horas y observación.
- Decisión inicia ausente y pasa una sola vez a `approved` o `observed` por constraint única y trigger de inmutabilidad.
- Decisión `observed` requiere motivo por fila o general; `approved` no admite observación de fila.

## Designación legado

`designaciones.estado` admite `propuesta`, `aprobada`, `rechazada`. Scope `active` excluye `rechazada`. Este estado no participa directamente en revisión versionada; sirve de fuente histórica con filtros de importación.

## Notificación

```text
unread (read_at = NULL) ── marcar una/todas ──> read (read_at != NULL)
```

El registro no se elimina. El controlador verifica que pertenezca al usuario autenticado.

## Rol

`users.role` queda restringido a `director_carrera` o `vicerrectorado`. No hay transición funcional de rol documentada; cambios dependen de administración/base de datos.

## Transiciones rechazadas por código

- Editar/enviar propuesta oficial, de gestión no actual o con versión pendiente.
- Retirar versión no pendiente o enviada por otro usuario.
- Revisar versión no pendiente, decidir snapshot ajeno, omitir decisiones o aprobar con observación de fila.
- Importar misma gestión y periodo, sobrescribir fila `aprobada_previamente` o romper consistencia grupo-materia-malla.
