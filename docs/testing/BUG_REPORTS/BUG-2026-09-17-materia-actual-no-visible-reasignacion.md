# BUG-2026-09-17: materia actual no visible al reasignar

## Estado

Corregido en la aplicación; pendiente verificación manual con navegador en el
entorno de ejecución.

## Síntoma

Al abrir el modal de reasignación de una fila, la materia actualmente asignada
aparece vacía si esa materia ya no forma parte de la oferta académica vigente.

## Reproducción

1. Abrir una designación con una fila de detalle.
2. Abrir la acción de reasignación de esa fila.
3. Observar el campo Materia.

La lectura autorizada de `INF / 2026 / 1` devolvió materias de la oferta, pero
la materia de una fila existente no estaba incluida en esa oferta. El
controlador solo pasaba la oferta al modal; por eso `textoMateria()` no podía
resolver el identificador actual.

## Causa

La oferta vigente y las filas ya asignadas no siempre contienen exactamente los
mismos identificadores de materia. El modal requería la oferta para nuevas
asignaciones, pero también necesitaba conservar la materia actual para editar
una fila existente.

## Corrección

- `DesignacionController` agrega al catálogo del modal las materias actuales
  que falten en la oferta, incluyendo sus grupos ya asignados.
- Esas materias llevan `solo_edicion = true` y no aparecen al crear una nueva
  designación.
- La disponibilidad global de grupos se calcula únicamente con la oferta
  autorizada, sin degradarla por la opción de edición agregada.
- Se agregó una prueba feature que reproduce y verifica la materia fuera de la
  oferta.

## Evidencia

- Antes de corregir: la regresión fallaba porque la materia no estaba en
  `materiasOferta`.
- Después de corregir: la regresión pasa con 8 aserciones.
- La suite del detalle queda en `19/20`; el único error es una expectativa de
  log baseline no relacionada.

## Riesgos pendientes

- No se ejecutó una escritura real en producción.
- La validación manual del modal requiere un navegador disponible.
