# BUG-2026-09-17: Grupo siguiente por materia

## Estado

RESUELTA.

## Reproduccion

Crear una nueva fila para una materia que ya tiene el grupo `1`. El selector
mostraba grupos que no correspondian al siguiente consecutivo y el servicio no
exigia que una insercion nueva usara el grupo `2`.

## Regla

Una nueva asignacion debe usar el siguiente grupo consecutivo de la materia en
el contexto academico. Si existe `N`, el siguiente es `N + 1`. Una edicion
puede conservar su grupo actual.

## Correccion

- La oferta calcula el ultimo grupo con el mayor numero real, no con el conteo
  de filas del catalogo.
- El servicio combina la oferta autorizada, las aperturas registradas y las
  filas actuales para calcular el siguiente grupo.
- Las inserciones nuevas exigen el siguiente grupo; las ediciones conservan el
  grupo actual y pueden usar el siguiente grupo valido.
- La interfaz muestra solo el siguiente grupo para una fila nueva.

## Regresion

- `JachasunDesignacionesServiceTest::test_guardar_detalle_nuevo_exige_el_siguiente_grupo`
- `JachasunDesignacionesDetailTest::test_modal_de_nueva_designacion_muestra_solo_el_siguiente_grupo`
- `JachasunDesignacionesServiceTest::test_oferta_materias_incluye_unicamente_el_siguiente_grupo`
