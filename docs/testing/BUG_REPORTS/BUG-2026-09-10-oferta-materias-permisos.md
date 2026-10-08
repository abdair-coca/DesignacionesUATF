# BUG-2026-09-10: permisos para oferta curricular

Estado: **PARCIALMENTE MITIGADO; PENDIENTE ADMINISTRACION DE BD**

## Reproduccion segura

Con la conexion de la aplicacion, usando solo consultas de lectura:

- `academico.f_oferta_materias(programa, gestion, periodo)` responde.
- La consulta de grupos que lee `academico.dct_asignaciones` es rechazada por
  permisos.
- La consulta del contexto vigente que lee `public.gestion_periodo_directores`
  es rechazada por permisos; el fallback actual deriva el ultimo contexto de
  `f_asignaciones`.
- La funcion existente para asignaciones falla al intentar acceder a sus
  fuentes internas con el mismo usuario.

No se ejecutaron `INSERT`, `UPDATE`, `DELETE`, migraciones ni cambios de
permisos.

## Impacto residual

El detalle ya no responde HTTP 503 cuando solo falla la fuente de grupos: carga
las materias y horas oficiales en modo degradado y permite reasignar docente o
materia, conservando el grupo actual. No se puede cambiar el grupo ni validar
en Jachasun real la lista completa de grupos o el contexto vigente para crear
una designacion. Crear/copiar funciona cuando existe una designacion publicada
para inferir el ultimo contexto; sin filas publicadas, la operacion se rechaza.

## Resolucion requerida

Administracion debe exponer una funcion PostgreSQL de lectura que devuelva el
contexto y los grupos necesarios, o conceder los permisos minimos de solo
lectura al usuario de la aplicacion. El fallback permite operar mientras exista
historial publicado, pero la fuente canonica debe habilitarse para eliminar esa
dependencia. Luego debe repetirse el smoke test sin escrituras y verificarse que
la respuesta mantenga solo las columnas necesarias.

## Regresion

La cobertura automatizada esta en:

- `tests/Unit/JachasunDesignacionesServiceTest.php`
- `tests/Feature/JachasunDesignacionesDetailTest.php`
- `tests/Feature/JachasunDesignacionesEscrituraTest.php`
