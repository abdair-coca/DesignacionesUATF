# BUG-2026-10-01: Detalle y PDF de Vicerrectorado no encontraban designaciones

Estado: **RESUELTO**

## Reproducción

Desde el listado global, abrir `Detalles` o `Imprimir` en una designación del
período 1 o 2 no encontraba el registro y respondía como inexistente.

## Causa

El listado combina los períodos institucionales `1` y `2`, pero la búsqueda por
ID usada por las rutas de detalle y PDF llamaba a
`f_asignaciones('UATF', gestion, '0')`. La función institucional no devuelve
todas las filas al usar período `0`.

## Corrección

`JachasunDesignacionesService::obtenerUniversidad()` ahora busca el ID solicitado
entre los resultados de los períodos `1` y `2` dentro de una transacción
`READ ONLY`. Las rutas existentes de Detalles e Imprimir y el parámetro de
gestión se conservan.

## Regresión

- `VicerrectoradoDesignacionesServiceTest::test_obtener_designacion_universitaria_busca_en_todos_los_periodos`
  reproduce el fallo con una fila del período `2` y exige consultar ambos
  períodos.
- La prueba feature del listado comprueba las URLs de Detalles e Imprimir y que
  el PDF se abra en una pestaña nueva.

## Verificación

- Prueba previa a la corrección: falla porque el servicio consulta únicamente
  el período `0`.
- `php artisan test tests/Unit/VicerrectoradoDesignacionesServiceTest.php tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage`:
  OK; 7 pruebas, 59 aserciones.
- `php artisan view:cache --no-ansi`, `php -l` en los archivos PHP modificados y
  Pint dirigido: OK.
- Suite completa: 15 aprobadas, 121 errores por conexión rechazada al PostgreSQL
  de testing `127.0.0.1:55432`.
