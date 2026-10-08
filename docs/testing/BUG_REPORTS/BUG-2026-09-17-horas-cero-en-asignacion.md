# BUG-2026-09-17: Asignacion con todas las horas en cero

## Estado

RESUELTA.

## Reproduccion

Enviar una actualizacion de detalle con `hrs_teoria=0`,
`hrs_practica=0` y `hrs_laboratorio=0`. La validacion anterior permitia que la
operacion continuara.

## Regla

Cada campo de horas puede ser `0`, pero la suma de las tres horas debe ser
mayor que `0`.

## Correccion

`JachasunDesignacionesService::guardarDetalle()` rechaza la operacion antes de
consultar o escribir datos cuando las tres horas son cero. Se mantienen los
limites no negativos y las horas oficiales de la materia.

## Regresion

- `JachasunDesignacionesServiceTest::test_guardar_detalle_rechaza_todas_las_horas_en_cero`
- `JachasunDesignacionesServiceTest::test_guardar_detalle_sin_grupos_permite_reasignar_docente_y_horas_validas`
