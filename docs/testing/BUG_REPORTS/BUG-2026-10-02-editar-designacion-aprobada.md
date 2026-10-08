# BUG-2026-10-02: Dirección podía modificar una designación aprobada

Estado: **RESUELTO**
Severidad: Media
Ambiente: Flujo de Dirección de Carrera

## Precondiciones

La designación tiene estado general `APROBADO`.

## Reproducción

1. Abrir la designación desde Dirección de Carrera.
2. Intentar editar la cabecera, reasignar una fila o agregar otra asignación.
3. Enviar directamente un POST a los endpoints de actualización.

## Resultado esperado y actual

- Esperado: la designación aprobada se consulta e imprime, pero no se modifica.
- Actual: la pantalla y los endpoints no comprobaban el estado general antes de
  editar.

## Causa raíz

Las rutas de escritura autorizaban al rol y al alcance de carrera, pero no
comprobaban si la designación ya estaba aprobada.

## Regresión y corrección

- `JachasunDesignacionesEscrituraTest::test_director_no_puede_actualizar_una_designacion_aprobada`
- `JachasunDesignacionesEscrituraTest::test_director_no_puede_modificar_asignaciones_de_una_designacion_aprobada`
- `JachasunDesignacionesDetailTest::test_designacion_aprobada_se_muestra_solo_para_consulta_a_direccion`
- Se ocultan los controles de edición y se rechazan las escrituras de cabecera
  y filas en el servidor. La consulta aprobada ya no depende de cargar la oferta
  que solo usa el editor.

## Riesgos y verificación

- Las designaciones `SOLICITADO` y `OBSERVADA` mantienen el flujo de edición
  actual; no se altera la decisión existente de filas revisadas.
- Regresiones focalizadas: 3 pruebas, 21 aserciones, OK.
- La suite de edición completa está bloqueada por indisponibilidad del entorno
  PostgreSQL de pruebas.
