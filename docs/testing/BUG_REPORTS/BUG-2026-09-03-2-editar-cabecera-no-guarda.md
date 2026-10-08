# BUG-2026-09-03-2: Editar cabecera no guarda (validación `fecha` silenciosa)

## Estado

RESUELTO (2026-09-03)

## Sintoma

Al editar la cabecera de una designación (`POST /designaciones/{id}`) y guardar
los cambios (p. ej. la observación), la vista recargaba sin cambios y sin ningún
mensaje: parecía que "no guardaba".

## Reproduccion segura

Verificado llamando a `DesignacionController@update` con usuario demo autenticado
(carrera INF) contra la BD real, enviando `fecha` vacío:

```php
POST /designaciones/2131  ['fecha' => '', 'obs' => 'SOLO CAMBIO OBS']
```

Resultado:

```text
EXCEPTION Illuminate\Validation\ValidationException: The fecha field is required.
```

El `validate()` lanzaba `ValidationException`, que redirige `back()` con `$errors`
en sesión, pero `layouts/app.blade.php` no mostraba `$errors` — por eso el usuario
no veía ni el cambio ni un mensaje de error.

## Causa

- El controlador `update` declaraba `fecha` como `required`. Cuando el campo fecha
  viaja vacío (designaciones con `r_fecha = NULL`, o el usuario deja el campo sin
  tocar y el navegador no lo rellena), la validación falla.
- El layout no renderizaba los errores de validación (`$errors->any()`), ocultando
  la causa real.

## Correccion aplicada

1. `DesignacionController@update`: `fecha` pasa a `nullable`; si llega vacío, se
   conserva la fecha actual obtenida con `obtener($id)`.
2. `layouts/app.blade.php`: se renderizan los errores de validación (`$errors`)
   como alerta visible, para no volver a ocultar fallos.

## Prueba de regresion

- `test_actualizar_sin_fecha_conserva_la_fecha_actual` en
  `tests/Feature/JachasunDesignacionesEscrituraTest.php`: POST con `fecha=''`
  conserva la fecha actual y guarda la observación.
- Verificación manual contra la BD real (rollback): con `fecha=''` el update
  conserva la fecha existente y actualiza `obs`; con `fecha` válida y con `obs=''`
  funciona igual.
- Lint: `php -l` OK en controlador, test y layout; `php artisan view:cache` OK;
  `route:list` muestra las rutas correctas.

## Riesgos / pendientes

- La suite automatizada sigue bloqueada por dev-deps ausentes
  (`phpunit/phpunit`, `pint`).
- Si el usuario escribe una fecha en un formato que `strtotime` no reconoce,
  `JachasunDesignacionesService::validarFecha` lanza una excepción y el controlador
  la captura mostrando `No fue posible actualizar la designacion.` (ahora visible
  como flash de error).