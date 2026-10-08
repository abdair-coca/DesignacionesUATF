# BUG-2026-09-15: Busqueda global de docentes en el modal

## Estado

RESUELTA.

## Problema

El combobox de docentes del modal de reasignacion mostraba primero el catalogo
de la carrera y solo consultaba docentes de toda la universidad al presionar la
lupa o Enter. Ademas, mostraba mensajes indicando el origen de los resultados.

## Reproduccion

Antes del cambio, la prueba de regresion
`JachasunDesignacionesDetailTest::test_modal_busca_docentes_solo_con_el_catalogo_global`
fallaba porque la vista contenia el filtro sobre `docentesDisponibles` y los
mensajes de carrera/universidad.

## Causa

El detalle cargaba `academico.f_lista_docentes(carrera)` al abrirse y el metodo
`docentesFiltrados()` elegia ese catalogo mientras no se ejecutaba la busqueda
global.

## Correccion

- Se elimino la carga del catalogo de docentes por carrera al abrir el detalle.
- El combobox usa exclusivamente los resultados de `public.f_buscar_docente`.
- Cambiar el texto limpia resultados anteriores; la consulta se ejecuta con la
  lupa y Enter busca si aun no hay resultados.
- Cuando ya existen resultados, Enter selecciona la opcion activa del
  combobox.
- Se eliminaron los dos mensajes inferiores sobre el origen de los resultados.
- Se conservaron el icono de lupa, el formulario de busqueda y sus eventos.

## Verificacion

- Prueba de regresion: pasa.
- Suites relacionadas: 66 pruebas, 425 aserciones, todas pasan.
- Vista cacheada, sintaxis PHP y Pint: pasan.
- Suite completa: 105 pruebas pasan; permanecen 10 fallos y 1 error
  preexistentes en autenticacion, lista principal y acceso.
