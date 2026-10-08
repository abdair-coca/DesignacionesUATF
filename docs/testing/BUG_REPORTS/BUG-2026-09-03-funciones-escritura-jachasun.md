# BUG-2026-09-03: Funciones de escritura Jachasun (`f_designacion`, `f_copiar_designacion`)

## Estado

RESUELTO (funciones corregidas y aplicadas en la BD real por el administrador, 2026-09-03)

## Sintoma

La feature "copiar y editar designaciones" fallaba en dos operaciones contra la
BD de Jachasun:

1. **Consultar por id (`f_designacion` con `_tipo = ''`)** devolvía 0 filas.
   En la UI, al editar la cabecera de una designación existente (`POST
   /designaciones/{id}`), la app la consideraba inexistente y la actualización
   fallaba.
2. **Copiar a nueva gestión (`f_copiar_designacion`)** devolvía éxito pero
   copiaba los detalles de otra designación (o ninguno), corrompiendo el
   resultado silenciosamente.

## Reproduccion segura

Verificado contra la BD real (`10.10.166.120:5432/jachasun`), solo lectura y
transacciones con rollback (sin dejar datos):

```sql
-- Problema 1 (id real 2131):
SELECT * FROM designaciones.f_designacion(2131, NULL, '', 0, 0, NULL, '');
-- Antes: 0 filas (esperado: 1).

-- Problema 2 (origen 2131):
SELECT id, id_materia, id_docente, id_grupo
  FROM designaciones.asignaciones_detalles WHERE id_asignaciones = 2131;  -- detalle real
SELECT id, id_materia, id_docente, id_grupo
  FROM designaciones.asignaciones_detalles WHERE id = 2131;               -- lo que tomaba
```

Evidencia capturada:

```text
SELECT f_designacion(2131, NULL, '', 0, 0, NULL, '') => 0 filas
f_copiar_designacion(2131, 9998, 1, 'REPRO-ADMIN') => "Copia realizada correctamente"
Designacion nueva id=2136, detalles copiados: 1  => FALLO: detalles con datos de OTRA designacion
```

## Impacto

La app no podía editar la cabecera de una designación existente y la importación
desde una gestión anterior producía designaciones con detalles incorrectos.

## Causa

- `f_designacion`: `RETURN QUERY ... WHERE id = vl_id`, pero `vl_id` solo se
  asignaba en `'INS'`/`'UPD'`; en modo select quedaba `NULL`.
- `f_copiar_designacion`: el `INSERT` de detalles filtraba `WHERE id = _id` en
  vez de `WHERE id_asignaciones = _id` (`id` es la PK del detalle; la referencia
  a la designación es `id_asignaciones`).

## Correccion aplicada

Se prepararon las funciones corregidas en
`docs/specs/changes/copiar-y-editar-designaciones/scripts-bd.sql`:

- `f_designacion`: `vl_id := _id` al inicio, para que `WHERE id = vl_id`
  devuelva la fila en `INS` (tras el `RETURNING`), `UPD` y select.
- `f_copiar_designacion`: `WHERE id_asignaciones = _id` en el `INSERT` de
  detalles, más la regla de negocio confirmada por el administrador
  `IFF(id_docente = 898, 0, id_docente)`.
- `estado = 'SOLICITADO'` lo garantiza el `DEFAULT` de la columna.

El administrador las aplicó en la BD real (2026-09-03).

## Prueba de regresion

Smoke tests en BD real (transacciones con rollback): select por id devuelve
1 fila; `INS` devuelve la fila nueva con `estado = 'SOLICITADO'`; `UPD`
actualiza y devuelve; `f_copiar_designacion(2131, 9997, 1, 'SMOKE-COPIA')`
copia el detalle correcto (docente 602) con la nueva fila en `SOLICITADO`;
origen inexistente no crea filas. Los tests unit/feature de la app
(`JachasunDesignacionesServiceTest`, `JachasunDesignacionesEscrituraTest`)
quedaron escritos pero su ejecución sigue bloqueada por dev-deps ausentes
(`phpunit/phpunit`, `pint`).

## Riesgos / pendientes

- Ejecutar la suite automatizada tras `composer install --dev`.
- Confirmar el resto de `NEEDS_BUSINESS_CONFIRMATION` de `proposal.md`
  (cambio de `id_programa` en `UPD`; origen de `_obs` al copiar).