# BUG-2026-09-10: horas no editables sin catálogo de grupos

## Estado

Resuelto.

## Reproducción

1. Abrir el detalle de una designación sin catálogo autorizado de grupos.
2. Intentar modificar las horas teóricas, prácticas o de laboratorio.
3. Observar que los campos estaban deshabilitados o que el servicio reemplazaba los valores enviados por los valores oficiales.

## Causa

La interfaz vinculaba la edición de horas a la disponibilidad de grupos y el servicio no distinguía entre la ausencia del catálogo de grupos y una solicitud de cambio de grupo.

## Corrección

Los campos de horas permanecen editables, el grupo actual se conserva y el servicio valida que cada hora sea válida y no supere la oferta oficial.

## Regresión

`JachasunDesignacionesDetailTest::test_modal_permite_modificar_horas_aunque_no_haya_catalogo_de_grupos`

`JachasunDesignacionesServiceTest::test_guardar_detalle_rechaza_horas_superiores_a_la_oferta_oficial`
