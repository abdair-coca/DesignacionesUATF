# BUG-2026-09-17: Modal no explica el grupo siguiente

## Estado

RESUELTA.

## Ambiente

Testing local mediante render de la vista con una materia que ya tiene grupos.

## Reproduccion

- Abrir una reasignacion para una materia con designaciones existentes.
- El selector calcula opciones, pero debajo del campo no aparece el aviso sobre
  el grupo siguiente ni sobre la conservacion del grupo actual.

## Resultado esperado

El modal muestra el grupo siguiente calculado por la fuente autorizada y explica
si la materia ya tiene designaciones. Una edicion conserva su grupo actual.

## Causa observada

La vista solo contiene `gruposDisponibles()` y `gruposMateriaSeleccionada()`;
no existe una funcion ni un mensaje contextual para el grupo.

## Regresion

`JachasunDesignacionesDetailTest::test_modal_muestra_la_ayuda_del_siguiente_grupo_de_la_materia`

## Correccion

- Se agregaron `grupoSiguienteMateria()` y `mensajeGrupoMateria()` al estado
  Alpine.
- El mensaje distingue materia sin designaciones, nueva fila y edicion que
  conserva el grupo actual.
- El calculo combina el grupo siguiente de la oferta con el maximo de las filas
  actuales; la regla server-side sigue siendo la autoridad final.
- La fuente degradada conserva el fallback `maximo asignado + 1`.

## Verificacion

- Mensaje de materia con designaciones y grupo siguiente: pasa.
- Selector de nueva fila y conservacion de grupo en edicion: pasa.
- Reglas de oferta y fallback sin grupos: pasan en pruebas unitarias.
