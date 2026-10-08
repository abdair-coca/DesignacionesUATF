# BUG-2026-09-09-1: deriva de mensajes 503/log vs. contrato de pruebas

## Síntoma

- `JachasunDesignacionesDetailTest::test_fallo_del_detalle_no_filtra_datos_externos`
  fallaba: esperaba ver `No fue posible consultar el detalle de la
  designacion en Jachasun.` y el log `Detalle Jachasun no disponible.`.
- `DesignacionPdfTest::test_fallo_del_pdf_no_filtra_datos_externos` (regresión
  nueva de la fase PDF, espejo del anterior) fallaba igual: `Log::warning`
  esperado una vez, llamado cero veces.

## Reproducción

```bash
php artisan test tests/Feature/DesignacionPdfTest.php --env=testing --no-coverage
php artisan test tests/Feature/JachasunDesignacionesDetailTest.php --env=testing --no-coverage
```

## Causa raíz

El controlador exponía textos cortos (`'Detalle no disponible.'`, `'No fue
posible consultar el detalle de la designacion.'`, `'Lista no
disponible.'`, ...) mientras las pruebas preexistentes y `STATUS.md` fijan
el contrato largo con mención a Jachasun (`'Detalle Jachasun no
disponible.'`, `'No fue posible consultar el detalle de la designacion en
Jachasun.'`, ...). Deriva entre implementación y contrato; ninguna prueba
fue modificada para ocultarla.

## Corrección (cambio mínimo, sin debilitar pruebas)

`app/Http/Controllers/DesignacionController.php`: se alinearon el mensaje de
`Log::warning` y el mensaje 503 de `index`, `show` y `pdf()` al contrato
largo. Los flash de crear/actualizar (`store`, `update`) no se tocaron
(un reemplazo amplio los alcanzó por error y se revirtió de inmediato).

## Verificación

- `DesignacionPdfTest`: 5/5, 35 aserciones.
- `JachasunDesignacionesDetailTest`: 3/4 (solo el fallo preexistente del
  texto inexistente `Consulta de solo lectura`).
- Suite completa: 73/87; 13 fallos + 1 error, todos preexistentes y no
  relacionados (ver `STATUS.md` fase PDF).

## Pendiente (no introducido por esta fase)

`JachasunDesignacionesListTest::test_fallo_jachasun_bloquea_la_lista_con_mensaje_seguro`
sigue en error: su mock fija `listarPaginado` (paginación retirada del
`index`, que hoy llama a `listar`), por lo que la expectativa de `Log` no
puede cumplirse. Falla idénticamente sin los cambios de esta fase.
Requiere una fase propia (restaurar paginación o actualizar el mock).
