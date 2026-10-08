# Reproducción de errores en funciones de BD Jachasun (schema `designaciones`)

Fecha de detección: 2026-09-03
Fecha de resolución: 2026-09-03 (funciones aplicadas por el administrador en la BD real)

## Resumen

Se detectaron dos defectos en las funciones `designaciones.f_designacion` y
`designaciones.f_copiar_designacion` verificados contra la BD real
(`10.10.166.120:5432/jachasun`). El administrador aplicó las correcciones
(registradas en `scripts-bd.sql`) y los smoke tests en BD real confirmaron el
comportamiento correcto.

---

## Problema 1 — `f_designacion` con `_tipo = ''` (consultar por id) devolvía vacío

### Cuándo ocurría
Cada vez que la aplicación consultaba una designación por su id en modo select
(`obtener()` en `JachasunDesignacionesService`, usado por la edición de cabecera
en `POST /designaciones/{id}`). Al editar una designación existente, la app
consideraba que "no existía" y fallaba la actualización.

### Reproducción (id real verificado: 2131)
```sql
SELECT * FROM designaciones.f_designacion(2131, NULL, '', 0, 0, NULL, '');
```
Resultado antes de corregir: `0 filas` (esperado: 1). Después de corregir: `1 fila`.

### Causa
La función terminaba con `RETURN QUERY ... WHERE id = vl_id` y `vl_id` solo se
asignaba en `'INS'`/`'UPD'`; en modo select quedaba `NULL` (`WHERE id = NULL`
no matchea nada). Corrección: `vl_id := _id` al inicio.

---

## Problema 2 — `f_copiar_designacion` copiaba los detalles de otra designación (o ninguno)

### Cuándo ocurría
Al crear una designación nueva con "Importar de una gestión anterior"
(`POST /designaciones` con `importar_desde`). Devuelve éxito pero copiaba
detalles ajenos (o ninguno), corrompiendo el resultado silenciosamente.

### Reproducción (origen verificado: 2131)
```sql
-- detalle real de la designacion 2131:
SELECT id, id_materia, id_docente, id_grupo
  FROM designaciones.asignaciones_detalles WHERE id_asignaciones = 2131;

-- que tomaba la funcion (filtraba por id, no por id_asignaciones):
SELECT id, id_materia, id_docente, id_grupo
  FROM designaciones.asignaciones_detalles WHERE id = 2131;
```
Antes: la copia creaba la fila pero con detalles de otra designación.
Después de corregir (`WHERE id_asignaciones = _id`): copia los detalles correctos.

### Causa
El `INSERT` de detalles filtraba `WHERE id = _id` en vez de
`WHERE id_asignaciones = _id`. En `asignaciones_detalles`, `id` es la clave
primaria del detalle e `id_asignaciones` la referencia a la designación.

---

## Regla de negocio adicional (confirmada por el administrador)

`f_copiar_designacion` guarda los detalles con `id_docente = 898` como
`id_docente = 0` al copiar (`IFF(id_docente = 898, 0, id_docente)`). Hay
12.274 detalles con docente 898. Se registró como regla confirmada en
`spec.md` y `proposal.md`.

`estado = 'SOLICITADO'` en filas nuevas lo garantiza el `DEFAULT` de la
columna `estado` (`'SOLICITADO'::character varying`); no está en los `INSERT`.

---

## Verificación final (smoke en BD real, 2026-09-03, con rollback)

- `f_designacion(2131, NULL, '', 0, 0, NULL, '')` → 1 fila (prog=INF, gestión=2026).
- `INS` → devuelve la fila nueva con `estado=SOLICITADO`.
- `UPD` → devuelve la fila actualizada.
- `f_copiar_designacion(2131, 9997, 1, 'SMOKE-COPIA')` → copia el detalle correcto
  (docente 602), nueva fila con `estado=SOLICITADO`.
- Origen inexistente → no crea filas.

## Estado

Resuelto. Funciones aplicadas en la BD real; falta solo ejecutar la suite
automatizada (`phpunit`/`pint`) tras `composer install --dev`, bloqueada por
dev-deps ausentes en este servidor.