# BUG-2026-09-17: Grupo siguiente oficial bloqueado en modo degradado

## Estado

RESUELTA.

## Reproduccion

Con la fuente de grupos no disponible (`grupos_disponibles = false`):

- La UI devolvía `[]` para cualquier materia, por lo que el selector solo
  mostraba `Seleccionar grupo`.
- El servicio rechazaba toda fila nueva (`No se puede validar el grupo...`),
  aunque pidiera el siguiente consecutivo oficial (ej. materia con grupo 1,
  nueva con grupo 2).

## Regla oficial confirmada por el usuario

El siguiente grupo (`N + 1`) es oficial y automático. Se calcula con oferta
autorizada + aperturas registradas + filas actuales; el modal debe mostrarlo
y la fila nueva debe usarlo. Al editar se conserva el grupo actual.

## Regresion (fallaba antes del cambio)

- `JachasunDesignacionesServiceTest::test_guardar_detalle_sin_grupos_permite_nuevo_con_siguiente_oficial`

## Correccion

- UI `gruposDisponibles()`: ya no retorna `[]` anticipado en degradado;
  calcula `siguiente = max(grupo_siguiente, maxAsignado + 1)` con las filas
  actuales. Nueva (`id = 0`) expone solo `[siguiente]`; edición expone
  disponibles + siguiente y conserva el actual.
- Servicio `guardarDetalle()`: en degradado la fila nueva debe usar
  exactamente el siguiente; la edición puede conservar su grupo o usar el
  siguiente. La apertura se registra cuando `idGrupo === grupoSiguiente`
  (antes solo con catálogo disponible).
- `AGENTS.md`: regla de grupo siguiente oficial y `max asignado + 1` en
  degradado; materia seleccionable antes que el docente.

## Verificacion

- Regresión nueva: pasa.
- Suites relacionadas: servicio + escritura 50/50; detalle 14/15 (resto por
  deriva de log preexistente).
