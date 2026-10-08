# BUG-2026-09-11: Select de grupos bloqueado por catalogo previo

## Estado

Corregido en la fase actual.

## Reproduccion

- Materia con dos grupos existentes.
- Abrir la edicion de una asignacion.
- Intentar seleccionar el grupo 3.

Antes del cambio, el servicio rechazaba el grupo porque no aparecia previamente
en la lista del catalogo y la interfaz podia deshabilitar el `select` cuando la
consulta de grupos no estaba disponible.

## Regla confirmada

- El conteo aplica por materia y gestion.
- Con `N` grupos se permiten los numeros del 1 al `N + 1`.
- Guardar `N + 1` registra la apertura del nuevo grupo.
- No se exige que el grupo nuevo exista previamente en el catalogo.
- Si dos usuarios intentan abrir el mismo siguiente grupo, solo gana la primera
  transaccion.

## Correccion

- Se agrego `designacion_grupo_aperturas` con restriccion unica por materia,
  gestion y numero.
- Se agrego la vista autorizada `v_designacion_grupos_abiertos` para consultar
  el ultimo grupo abierto.
- El servicio ahora ofrece el rango consecutivo y valida contra ese rango, no
  contra la existencia previa del grupo.
- La apertura se registra dentro de la misma transaccion de guardado.
- El `select` de grupos ya no se deshabilita por la bandera del catalogo.

## Pruebas

- `JachasunDesignacionesServiceTest::test_oferta_materias_incluye_unicamente_el_siguiente_grupo`
- `JachasunDesignacionesDetailTest::test_selector_de_grupo_se_mantiene_editable`
- Suite relacionada de servicio: 30 pruebas, 30 correctas.
- Suite relacionada de detalle: 10 pruebas, 10 correctas.
- Suite de escritura: 17 pruebas, 17 correctas.

## Riesgos pendientes

- La migracion debe ejecutarse antes de usar la apertura atomica en un entorno
  que aun no tenga la tabla y la vista nuevas.
- La suite completa mantiene fallos fuera del alcance de este cambio en
  autenticacion y listado principal; deben diagnosticarse por separado.
