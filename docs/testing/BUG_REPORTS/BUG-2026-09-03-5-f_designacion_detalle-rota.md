# BUG-2026-09-03-5: `designaciones.f_designacion_detalle` no ejecuta (UPDATE/INSERT rotos)

## Estado

RESUELTO Y APLICADO (administrador, 2026-09-04); verificado con smoke por la
conexión de la app (rollback): UPDATE e INSERT devuelven la fila afectada sin
errores.

## Sintoma

Al integrar la edición de filas de detalle de una designación, la función
`designaciones.f_designacion_detalle` no ejecuta en ninguno de sus caminos:

1. **Actualizar** una fila existente (`_id_detalle > 0`) → error.
2. **Insertar** una fila nueva (`_id_detalle = 0`) → error.

## Reproduccion segura

Verificado contra la BD real (`10.10.166.120:5432/jachasun`), en transacciones
con rollback (sin dejar datos). Ids reales: designación 2138, detalle 80360.

```sql
-- UPDATE (detalle existente 80360, docente 591):
SELECT * FROM designaciones.f_designacion_detalle(2138, 80360, 672, 3868, 5, 10, 2, 1);
-- ERROR 42601: multiple assignments to same column "id_docente"

-- INSERT (_id_detalle = 0):
SELECT * FROM designaciones.f_designacion_detalle(2138, 0, 602, 9595, 1, 0, 0, 0);
-- ERROR 42701: column "id_materia" specified more than once
```

Evidencia capturada:

```text
[UPDATE] ERROR (42601) multiple assignments to same column "id_docente"
[INSERT] ERROR (42701) column "id_materia" specified more than once
despues rollback detalle 80360: doc=591 grp=5 fecha=NULL  (sin efectos)
```

## Impacto

La app no puede editar las filas de detalle (docente, materia, grupo, horas) de
una designación: el modal "Editar" existente quedaba inoperante.

## Causa

- UPDATE: `SET ... id_docente = _id_docente, id_docente = _id_grupo, ...` asigna
  `id_docente` dos veces (error `42601`) y nunca asigna `id_grupo`.
- INSERT: lista de columnas con `id_materia` duplicada (`42701`), columnas
  inexistentes `hrs_teoria`/`hrs_practica` (las reales son `hrs_teoricas`/
  `hrs_practicas`) y `id_docente` omitido.
- `RETURN QUERY`: JOIN `ON da.id_asignaciones = da.id` (columna inexistente),
  13 columnas vs 12 del `RETURNS TABLE`, `WHERE id` ambiguo, y sin validación de
  pertenencia del detalle a la designación ni filtro `NOT IN (0, 898)`.

## Correccion aplicada

Definición corregida en
`docs/specs/changes/editar-filas-designacion/scripts-bd.sql`, **aplicada en la
BD real** por el administrador (2026-09-04). La versión aplicada usa `RETURNS
TABLE` de 14 columnas y devuelve solo la fila afectada. La corrección clave fue
calificar la vista como `academico.v_facultades_programas`: sin `academico.`,
la función fallaba con `42P01` en la app porque `usr_designaciones` tiene
`search_path = public` (la sesión del admin resuelve la vista por tener
`academico` en su path). Durante la fase rota, llamar a la función dejaba
transacciones colgadas con locks sobre `asignaciones_detalles` (procesos
terminados y datos restaurados).

## Prueba de regresion

Smoke en BD real por la conexión de la app (transacciones con rollback):
`f_designacion_detalle(2138, 80360, 672, 3868, 5, 10, 2, 1)` devuelve la fila
afectada (doc=672, r_docente=CONDORI LLANOS MARIBEL ROSARIO, hT=10) y tras el
rollback el detalle 80360 queda intacto (doc=591, horas 0);
`f_designacion_detalle(2138, 0, 602, 9595, 3, 4, 2, 0)` crea la fila nueva y
tras el rollback 2138 conserva 119 filas. Los tests unit/feature de la app
(`JachasunDesignacionesServiceTest`, `JachasunDesignacionesEscrituraTest`)
quedaron escritos; su ejecución sigue bloqueada por la base de testing
(`127.0.0.1:55432`) no disponible.

## Riesgos / pendientes

- Aplicar `scripts-bd.sql` en la BD real (administrador) y smoke final.
- `fecha = now()` en cada UPDATE de fila: heredado de la función original, a
  confirmar con el dueño.
- Fuente de horas sin conciliar entre el listado (`pln_materias`) y la escritura
  (manual en el detalle).

## Anexo: regresión de renderizado del detalle (2026-09-03)

La primera integración pasaba los catálogos de docente/materia al `x-data` de
Alpine con `@json(...)`. `json_encode` no escapa las comillas estructurales del
JSON, por lo que las `"` del JSON rompían el atributo `x-data="..."` y el
navegador mostraba el código JavaScript como texto visible al abrir una
designación. Se corrigió usando `@js(...)` (emite `JSON.parse('...')` con las
comillas escapadas). Verificado por HTTP real en la página de detalle y con la
regresión `assertSee("JSON.parse('", false)` /
`assertDontSee('docentesDisponibles: [{', false)` en
`JachasunDesignacionesEscrituraTest`.