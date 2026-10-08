# BUG-2026-09-17: Guardado de filas falla por tabla auxiliar ausente

## Estado

RESUELTA.

## Ambiente

Produccion, conexion PostgreSQL de Jachasun.

## Reproduccion

- Crear una nueva fila o guardar una fila cuyo grupo coincide con el siguiente
  grupo calculado.
- El servicio intenta insertar en `designacion_grupo_aperturas` antes de llamar
  a la funcion de escritura.
- La tabla no existe en la conexion desplegada y el usuario recibe `No fue
  posible actualizar la asignación.`.

## Causa observada

La tabla `designacion_grupo_aperturas` pertenece a una migracion local de la
aplicacion y no existe en la base desplegada. El `INSERT` abortaba la
transaccion antes de ejecutar `designaciones.f_designacion_detalle`.

## Regresion

- `JachasunDesignacionesServiceTest::test_guardar_detalle_continua_si_no_existe_la_tabla_local_de_aperturas`

## Correccion

El servicio verifica la existencia de la tabla antes de insertar. Si existe,
conserva el registro de apertura; si no existe, continua con la funcion de
Jachasun y las reglas de grupo ya validadas.

## Verificacion

- Tabla ausente: regresion pasa y no se ejecuta el insert auxiliar.
- Tabla disponible: el registro auxiliar conserva su prueba de escritura.
- No se ejecutaron escrituras reales en produccion durante la verificacion.
