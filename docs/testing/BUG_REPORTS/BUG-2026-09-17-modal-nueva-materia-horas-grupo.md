# BUG-2026-09-17: Modal nuevo bloqueaba materia y no enviaba horas/grupo

## Estado

RESUELTA.

## Reproduccion

- Abrir `Nueva designación` (fila nueva, `id = 0`): el campo Materia estaba
  deshabilitado (`:disabled="!editFilaForm.docente_id"`) y mostraba
  `Selecciona un docente primero`. En reasignación funcionaba porque el
  docente ya venía seleccionado.
- Aunque las horas se veían cargadas o modificadas, al guardar el backend
  respondía `The hrs teoria field is required`, `hrs practica`, `hrs
  laboratorio` y `id grupo`: los inputs de horas y el `select` de grupo
  estaban fuera de `form-editar-fila`, por lo que nunca se enviaban. El grupo
  además se enviaba solo vía un `hidden` espejo (`:value`), con una sola
  opción visible (`Seleccionar grupo`) cuando no había materia seleccionada.

## Regresion (fallaba antes del cambio)

- `JachasunDesignacionesDetailTest::test_modal_permite_materia_sin_docente_y_envia_horas_y_grupo`

## Correccion

- Materia habilitada desde el inicio; se eliminó el mensaje `Selecciona un
  docente primero` (queda `Sin materias disponibles`).
- El `select` de grupo ahora lleva `name="id_grupo"` y
  `form="form-editar-fila"`; se eliminó el `hidden` espejo de `id_grupo`.
- Las tres horas llevan `form="form-editar-fila"`, por lo que se envían con
  el mismo `submit()` de confirmación (evita formularios anidados con el
  buscador interno de docentes).
- Al seleccionar materia se cargan horas oficiales y el grupo siguiente
  siempre (antes solo cuando el catálogo de grupos estaba disponible).

## Verificacion

- Regresión nueva: pasa.
- `JachasunDesignacionesServiceTest` + `JachasunDesignacionesEscrituraTest`:
  50/50.
- `JachasunDesignacionesDetailTest`: 14/15 (el restante es deriva de mensaje
  de log preexistente, fuera de alcance; ver fase).
- `view:cache`, `php -l` y `pint --test` en archivos tocados: OK.
