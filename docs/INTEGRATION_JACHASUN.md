# Integración principal con Jachasun

## Funciones utilizadas

La aplicación usa funciones PostgreSQL de lectura y, desde la feature
"copiar y editar designaciones", funciones de escritura del mismo schema.

```sql
SELECT * FROM designaciones.f_asignaciones(?, ?, ?);
SELECT * FROM designaciones.f_asignaciones_detalles(?);
```

`f_asignaciones` recibe código de programa, gestión y periodo. La lista
principal usa siempre la sigla de la carrera autenticada con gestión `0` y
periodo `0`.

La bandeja de Vicerrectorado consulta el alcance universitario con gestión
parametrizada y combina los períodos institucionales `1` y `2`:

```sql
SELECT * FROM designaciones.f_asignaciones('UATF', ?, '1');
SELECT * FROM designaciones.f_asignaciones('UATF', ?, '2');
```

La consulta no filtra `r_estado`; la aplicación ordena por `r_fecha` descendente
y usa `r_id` como desempate estable.

`f_asignaciones_detalles` recibe el `r_id` de una fila de
`f_asignaciones` y devuelve las filas académicas asociadas.

## Columnas conocidas

Lista:

```text
r_id, r_id_programa, r_programa, r_detalle, r_fecha,
r_id_gestion, r_id_periodo, r_obs, r_estado
```

Detalle:

```text
r_id, r_id_docente, r_ci, r_nombres, r_id_materia, r_sigla,
r_materia, r_id_grupo, r_hrs_teoricas, r_hrs_practicas,
r_hrs_laboratorio
```

El estado de cabecera se muestra como viene en `r_estado`. La revisión de
Vicerrectorado usa `SOLICITADO`, `APROBADO` y `OBSERVADA`; la aplicación de la
regla automática y de las acciones generales se define en el contrato autorizado
de decisiones.

## Flujo

1. Director autenticado abre `GET /designaciones`.
2. El backend obtiene la sigla desde el usuario autenticado.
3. `JachasunDesignacionesService` ejecuta `f_asignaciones(sigla, '0', '0')`.
4. Cada `r_id` puede abrirse en `GET /designaciones/{id}`.
5. El backend confirma que el identificador pertenece a la carrera antes de
   ejecutar `f_asignaciones_detalles(?)`.
6. La vista `designaciones/carrera.blade.php` muestra datos de solo lectura.
7. `?print=1` abre la misma vista e inicia impresión.

Todas las consultas usan parámetros enlazados dentro de una transacción
`READ ONLY`.

## Escrituras (copiar y editar designaciones)

Además de las consultas de solo lectura, el flujo copia y edita designaciones
mediante funciones de escritura del schema `designaciones`:

```sql
SELECT * FROM designaciones.f_copiar_designacion(?, ?, ?, ?);
SELECT * FROM designaciones.f_designacion(?, ?, ?, ?, ?, ?, ?);
SELECT * FROM designaciones.f_designacion_detalle(?, ?, ?, ?, ?, ?, ?, ?);
```

- `f_copiar_designacion(_id, _id_gestion, _id_periodo, _obs)` copia la
  designación origen (programa y detalles) a una gestión/periodo nuevo; la fila
  nueva se crea con `estado = 'SOLICITADO'` (default de la columna). Regla de
  negocio confirmada: al copiar, los detalles con `id_docente = 898` se guardan
  con `id_docente = 0`.
- `f_designacion(_id, _fecha, _id_programa, _id_gestion, _id_periodo, _obs,
  _tipo)`: `INS` inserta (fila con `estado = 'SOLICITADO'`), `UPD` actualiza la
  cabecera y `''` consulta por id. Las funciones aplicadas devuelven la fila en
  los tres modos (`vl_id := _id` al inicio en `f_designacion`).
- La fecha no se pide manualmente: al copiar, la fila nueva recibe `now()` por
  el `DEFAULT (now())` de la columna `fecha`; al insertar/actualizar con
  `_fecha` vacío, `f_designacion` aplica `now()`. Al editar se conserva la fecha
  existente; solo las designaciones con `fecha = NULL` reciben la fecha actual
  automáticamente. El campo "Fecha" está oculto en los formularios de
  crear/importar y editar cabecera.
- Las escrituras se ejecutan en transacción normal (sin `SET TRANSACTION
  READ ONLY`) con parámetros enlazados. El programa siempre proviene de la
  carrera autenticada; nunca de la entrada del navegador.
- `f_designacion_detalle(_id, _id_detalle, _id_docente, _id_materia,
  _id_grupo, _hrs_teoria, _hrs_practica, _hrs_laboratorio)` inserta
  (`_id_detalle = 0`) o actualiza (`_id_detalle > 0`) una fila de
  `asignaciones_detalles` y devuelve la fila afectada. Se usa desde
  `JachasunDesignacionesService::guardarDetalle()` para editar las filas de
  detalle desde `POST /designaciones/{id}/detalle`. Aplicada y verificada en la
  BD real (2026-09-04). Definición en
  `docs/specs/changes/editar-filas-designacion/scripts-bd.sql`. Nota: la vista
  del `RETURN QUERY` debe ir calificada (`academico.v_facultades_programas`)
  porque el usuario de la app no tiene `academico` en su `search_path`.

## Decisiones de Vicerrectorado por asignación docente

La aplicación integra persistencia de decisión por fila en
`designaciones.asignaciones_detalles`. El contrato versionado, pendiente de
aplicación por el DBA, está en
`docs/specs/changes/decisiones-vicerrectorado-jachasun/scripts-bd.sql`:

```sql
SELECT * FROM designaciones.f_listar_decisiones_asignacion_detalle(?);
SELECT * FROM designaciones.f_guardar_decision_asignacion_detalle(?, ?, ?, ?);
```

Los estados permitidos son `APROBADA` y `RECHAZADA`. La observación es opcional;
un valor vacío limpia la observación previa. Cada asignación se escribe en su
propia transacción, por lo que una falla no revierte las demás filas válidas del
lote. La función de escritura verifica que el detalle pertenezca al `r_id`
recibido.

## Estado general de la designación

El detalle permite aprobar u observar la cabecera sin cambiar las decisiones de
las filas. La observación general de Vicerrectorado se almacena por separado de
`r_obs` y de las observaciones por fila. Es obligatoria al marcar `OBSERVADA` y
se limpia al aprobar.

```sql
SELECT * FROM designaciones.f_obtener_revision_designacion(?);
SELECT * FROM designaciones.f_guardar_revision_designacion(?, ?, ?);
```

El estado general inicia en `SOLICITADO`. El guardado de una decisión por fila
recalcula el estado de cabecera dentro de la misma transacción: sin filas o con
alguna pendiente permanece `SOLICITADO`; todas aprobadas produce `APROBADO`; y
todas decididas con al menos una rechazada produce `OBSERVADA`. Una acción
general puede establecer `APROBADO` u `OBSERVADA` con filas pendientes. La
siguiente modificación de fila vuelve a calcularlo. Si no están disponibles las
consultas y escrituras del contrato completo, el detalle bloquea ambas clases de
decisiones.

**Requisito previo al despliegue:** el DBA de Jachasun debe revisar que columnas
y tipos coincidan con el esquema, aplicar el script y conceder `EXECUTE` al
usuario de aplicación. Laravel no aplica DDL ni realiza DML directo. El contrato
general y el recálculo automático no se han aplicado ni probado en la BD
institucional.

## Seguridad y límites

- La carrera nunca proviene de una entrada del navegador.
- Un director no puede consultar identificadores de otra carrera.
- Errores externos devuelven mensajes seguros y registran solo la clase de
  excepción.
- La validación real depende de que el servidor Jachasun autorice la IP de la
  aplicación en `pg_hba.conf`.
