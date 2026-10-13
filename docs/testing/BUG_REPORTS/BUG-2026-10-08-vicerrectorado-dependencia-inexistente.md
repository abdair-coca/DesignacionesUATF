# BUG-2026-10-08: Error 500 al ingresar a Vicerrectorado

Estado: **CORREGIDO; verificación automatizada pendiente**

## Reproducción

Ingresar con una cuenta autorizada de Vicerrectorado a
`/vicerrectorado/designaciones`.

## Resultado

La pantalla respondía con HTTP 500 antes de consultar el listado.

## Causa y corrección

`VicerrectoradoDesignacionController` dependía de
`Modules\Designaciones\Services\DirectorDesignacionesService`, clase que no
existe en el código del proyecto. Se restauró la dependencia de
`App\Services\Jachasun\JachasunDesignacionesService`, usada por el flujo y sus
pruebas existentes.

## Regresión y verificación

- Regresión existente: `VicerrectoradoDesignacionesTest::test_vicerrectorado_ve_todas_las_designaciones_de_la_gestion` cubre la apertura del listado con el servicio simulado.
- La sintaxis PHP y la resolución del controlador desde el contenedor finalizaron correctamente. La prueba Feature no se ejecutó en esta solicitud; `git diff --check` pasó. Los resultados están en `docs/testing/STATUS.md`.
